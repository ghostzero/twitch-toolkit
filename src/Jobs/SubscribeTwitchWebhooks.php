<?php

namespace GhostZero\TwitchToolkit\Jobs;

use DateTime;
use Exception;
use GhostZero\TwitchToolkit\Enums\ActivityTopic;
use GhostZero\TwitchToolkit\Models\Channel;
use GhostZero\TwitchToolkit\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use romanzipp\Twitch\Twitch;
use RuntimeException;

class SubscribeTwitchWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LEASE_PRODUCTION = 691200; // 8 days
    private const LEASE_DEVELOPMENT = 3600; // 1 day

    private const AFFILIATE_AND_PARTNER_ONLY_WEBHOOKS = [
        ActivityTopic::SUBSCRIPTIONS,
    ];

    public const OPTIONS_LEASE_TIME = 'lease_time';
    public const OPTIONS_DEBUG = 'debug_mode';
    public const OPTIONS_ONLY = 'subscribe_only';

    private const TWITCH_WEBHOOK_MAX_LOCKS = 60;
    private const TWITCH_WEBHOOK_DECAY = 60;

    /**
     * @var Channel
     */
    public $channel;

    /**
     * @var array
     */
    public $options;

    public function __construct(Channel $channel, array $options = [])
    {
        $this->channel = $channel;

        $this->options = array_merge([
            self::OPTIONS_ONLY => null,
            self::OPTIONS_LEASE_TIME => config('app.env') === 'production'
                ? self::LEASE_PRODUCTION : self::LEASE_DEVELOPMENT,
        ], $options);
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return DateTime
     */
    public function retryUntil(): DateTime
    {
        return now()->addHours(3);
    }

    /**
     * @param Twitch $twitch
     * @throws LimiterTimeoutException
     */
    public function handle(Twitch $twitch): void
    {
        Redis::throttle('throttle:api.twitch.tv/webhooks')
            ->allow(self::TWITCH_WEBHOOK_MAX_LOCKS)
            ->every(self::TWITCH_WEBHOOK_DECAY)
            ->then(function () use ($twitch) {
                try {
                    // skip streams webhooks, if we already poll them
                    if (!in_array(Channel::TYPE_POLLING, $this->channel->capabilities, true)) {
                        $this->subscribe($twitch, $this->channel, ActivityTopic::STREAMS);
                    }
                    $this->subscribe($twitch, $this->channel, ActivityTopic::FOLLOWS);
                    $this->subscribe($twitch, $this->channel, ActivityTopic::SUBSCRIPTIONS);
                } catch (Exception $exception) {
                    Log::critical('Creating twitch subscription failed for ' . $this->channel->getKey() . '.', ['exception' => $exception]);
                    $this->release(150);
                }
            }, function () {
                $this->release(150);
            });
    }

    /**
     * Here we call the twitch subscribe endpoint. Later we confirm.
     *
     * @param Twitch $twitch
     * @param Channel $channel
     * @param string $activity
     *
     * @throws Exception
     */
    private function subscribe(Twitch $twitch, Channel $channel, string $activity): void
    {
        if ($this->options[self::OPTIONS_ONLY] && !in_array($activity, $this->options[self::OPTIONS_ONLY], true)) {
            return;
        }

        $requiresUserAccessToken = in_array($activity, self::AFFILIATE_AND_PARTNER_ONLY_WEBHOOKS, true);

        // prevent subscribe of affiliate & partner-only webhooks for normal broadcasters
        if (empty($channel->broadcaster_type) && $requiresUserAccessToken) {
            Log::info("Skipped to subscribe {$channel->id}:{$activity}, because the broadcaster type is empty.");
            return;
        }

        if ($requiresUserAccessToken) {
            if (empty($channel->oauth_access_token)) {
                Log::info("Skipped to subscribe {$channel->id}:{$activity}, because the oauth access token is empty.");
                return;
            } else {
                $twitch->setToken($channel->oauth_access_token);
            }
        }

        $topic = $this->getHubTopic($twitch, $channel, $activity);
        $lease = $this->options[self::OPTIONS_LEASE_TIME];
        $leaseInformation = [
            'leased_at' => now()->format('Y-m-d H:i:s'),
            'lease' => $lease,
        ];

        /** @var WebhookSubscription $subscription */
        $subscription = WebhookSubscription::query()
            ->firstOrCreate([
                'channel_id' => $channel->getKey(),
                'activity' => $activity,
            ], $leaseInformation);

        $subscription->forceFill($leaseInformation)->save();

        $callback = $this->getCallbackUrl($subscription);

        $response = $twitch->subscribeWebhook($callback, $topic, $lease);

        $context = [
            'channel_id' => $channel->getQueueableId(),
            'platform_id' => $channel->getKey(),
            'callback_url' => $callback,
            'topic_url' => $topic,
            'activity' => $activity,
            'accepted' => $response->success(),
        ];

        Log::info(sprintf('Sent %s@%s stored webhook subscription request to twitch.', $activity, $channel->getKey()), $context);
    }

    private function getCallbackUrl(WebhookSubscription $subscription): string
    {
        return route('twitch-toolkit.webhooks.twitch.callback', [
            'channel_id' => $subscription->channel_id,
            'activity' => $subscription->activity,
        ]);
    }

    /**
     * Returns a hub.topic url for the twitch api.
     *
     * @param Twitch $twitch
     * @param Channel $channel
     * @param string $activity
     *
     * @return string
     * @throws Exception
     */
    private function getHubTopic(Twitch $twitch, Channel $channel, $activity): ?string
    {
        switch ($activity) {
            case ActivityTopic::FOLLOWS:
                return $twitch->webhookTopicUserGainsFollower($channel->getKey());
            case ActivityTopic::STREAMS:
                return $twitch->webhookTopicStreamMonitor($channel->getKey());
            case ActivityTopic::SUBSCRIPTIONS:
                return $twitch::BASE_URI . 'subscriptions/events?broadcaster_id=' . $channel->getKey() . '&first=1';
            default:
                throw new RuntimeException(sprintf('Cannot find hub.topic url by `%s` activity.', $activity));
        }
    }
}
