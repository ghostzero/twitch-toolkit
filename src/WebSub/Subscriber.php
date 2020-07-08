<?php

namespace GhostZero\TwitchToolkit\WebSub;

use Carbon\Carbon;
use Exception;
use GhostZero\TwitchToolkit\Enums\ActivityTopic;
use GhostZero\TwitchToolkit\Jobs\WebSubSubscriber;
use GhostZero\TwitchToolkit\Models\Channel;
use GhostZero\TwitchToolkit\Models\WebSub;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use romanzipp\Twitch\Twitch;
use RuntimeException;

class Subscriber
{
    /**
     * @param string $callbackUrl
     * @param string $feedUrl
     * @param string|null $channelId if given, this WebSub will associated with a channel.
     * @return WebSub
     * @throws Exception
     */
    public function subscribe(string $callbackUrl, string $feedUrl, ?string $channelId = null): WebSub
    {
        $lease = random_int(3600, 21600); // lease between 1-6 hours
        $leasedAt = Carbon::now();
        $expiresAt = Carbon::now()->addSeconds($lease);

        /** @var WebSub $subscription */
        $subscription = WebSub::query()
            ->updateOrCreate(['feed_url' => $feedUrl], [
                'channel_id' => $channelId,
                'callback_url' => $callbackUrl,
                'secret' => Str::random(),
                'lease_seconds' => $lease,
                'expires_at' => $expiresAt,
                'leased_at' => $leasedAt,
            ]);

        dispatch(new WebSubSubscriber($subscription));

        return $subscription;
    }

    public function approve(string $feedUrl, Request $request): ?WebSub
    {
        if ($webSub = $this->firstByFeedUrl($feedUrl)) {
            $lease = $request->get('hub_lease_seconds', $webSub->lease_seconds); // lease between 1-6 hours
            $confirmedAt = Carbon::now();
            $expiresAt = $webSub->leased_at->clone()->addSeconds($lease);

            $webSub->update([
                'active' => true,
                'denied' => false,
                'confirmed_at' => $confirmedAt,
                'lease_seconds' => $lease,
                'expires_at' => $expiresAt,
                'last_response' => $request->toArray(),
            ]);
        }

        return $webSub;
    }

    public function deny(string $feedUrl, Request $request): ?WebSub
    {
        if ($webSub = $this->firstByFeedUrl($feedUrl)) {
            $webSub->update([
                'active' => false,
                'denied' => true,
                'denied_at' => Carbon::now(),
                'denied_reason' => $request->get('hub_reason'),
                'last_response' => $request->toArray(),
            ]);
        }

        return $webSub;
    }

    public function unsubscribe(string $feedUrl, Request $request): ?WebSub
    {
        if ($webSub = $this->firstByFeedUrl($feedUrl)) {
            $webSub->update([
                'active' => false,
                'denied' => false,
                'last_response' => $request->toArray(),
            ]);
        }

        return $webSub;
    }

    private function firstByFeedUrl(string $feedUrl): ?WebSub
    {
        /** @var WebSub|null $webSub */
        $webSub = WebSub::query()
            ->where(['feed_url' => $feedUrl])
            ->first();

        return $webSub;
    }

    /**
     * Returns a hub.topic url for the twitch api.
     *
     * @param Twitch $twitch
     * @param string $channelId
     * @param string $activity
     *
     * @return string
     */
    public static function getFeedUrl(Twitch $twitch, string $channelId, $activity): string
    {
        switch ($activity) {
            case ActivityTopic::FOLLOWS:
                return $twitch->webhookTopicUserGainsFollower($channelId);
            case ActivityTopic::STREAMS:
                return $twitch->webhookTopicStreamMonitor($channelId);
            case ActivityTopic::SUBSCRIPTIONS:
                return $twitch::BASE_URI . 'subscriptions/events?broadcaster_id=' . $channelId . '&first=1';
            default:
                throw new RuntimeException(sprintf('Cannot find hub.topic url by `%s` activity.', $activity));
        }
    }
}