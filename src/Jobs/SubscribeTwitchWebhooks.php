<?php

namespace GhostZero\TwitchToolkit\Jobs;

use DateTime;
use Exception;
use GhostZero\TwitchToolkit\Enums\ActivityTopic;
use GhostZero\TwitchToolkit\Models\Channel;
use GhostZero\TwitchToolkit\WebSub\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use romanzipp\Twitch\Twitch;

class SubscribeTwitchWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const AFFILIATE_AND_PARTNER_ONLY_WEBHOOKS = [
        ActivityTopic::SUBSCRIPTIONS,
    ];

    public const OPTIONS_ONLY = 'subscribe_only';

    public Channel $channel;
    public array $options;

    public function __construct(Channel $channel, array $options = [])
    {
        $this->channel = $channel;

        $this->options = array_merge([
            self::OPTIONS_ONLY => null,
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
     */
    public function handle(Twitch $twitch): void
    {
        try {
            // skip streams webhooks, if we already poll them
            if (!in_array(Channel::TYPE_POLLING, $this->channel->capabilities, true)) {
                $this->subscribe($twitch, $this->channel, ActivityTopic::STREAMS);
            }
            $this->subscribe($twitch, $this->channel, ActivityTopic::FOLLOWS);
            $this->subscribe($twitch, $this->channel, ActivityTopic::SUBSCRIPTIONS);
        } catch (Exception $exception) {
            Log::critical('Creating twitch webhook failed for ' . $this->channel->getKey() . '.', ['exception' => $exception]);
            $this->release(WebSubSubscriber::TWITCH_WEBHOOK_DECAY);
        }
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

        $subscriber = new Subscriber();
        $callbackUrl = $this->getCallbackUrl($channel->getKey(), $activity);
        $feedUrl = Subscriber::getFeedUrl($twitch, $channel->getKey(), $activity);

        if ($requiresUserAccessToken) {
            // prevent subscribe of affiliate & partner-only webhooks for normal broadcasters
            if (empty($channel->broadcaster_type)) {
                $this->skip($subscriber, $feedUrl, 'The broadcaster type is empty.');
                return;
            } elseif (empty($channel->oauth_access_token)) {
                $this->skip($subscriber, $feedUrl, 'The oauth access token is empty.');
                return;
            } else {
                $twitch->setToken($channel->oauth_access_token);
            }
        }

        $subscriber->subscribe($callbackUrl, $feedUrl, $channel->getKey());
    }

    protected function getCallbackUrl(string $channelId, string $activity): string
    {
        return route('twitch-toolkit.webhooks.twitch.callback', [
            'channel_id' => $channelId,
            'activity' => $activity,
        ]);
    }

    private function skip(Subscriber $subscriber, string $feedUrl, string $reason): void
    {
        Log::warning("Skipped to subscribe $feedUrl. Reason: {$reason}");
        $subscriber->deny($feedUrl, 'The broadcaster type is empty.');
    }
}
