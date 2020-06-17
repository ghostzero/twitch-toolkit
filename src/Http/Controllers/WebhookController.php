<?php

namespace GhostZero\TwitchToolkit\Http\Controllers;

use Exception;
use GhostZero\TwitchToolkit\Events\WebhookWasCalled;
use GhostZero\TwitchToolkit\Models\Channel;
use GhostZero\TwitchToolkit\Models\WebhookSubscription;
use GhostZero\TwitchToolkit\TopicParser\Parsers\ParseException;
use GhostZero\TwitchToolkit\TopicParser\TwitchTopicParser;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    private const HUB_MODE_SUBSCRIBE = 'subscribe';
    private const HUB_MODE_UNSUBSCRIBE = 'unsubscribe';
    private const HUB_MODE_DENIED = 'denied';

    /**
     * @param Request $request
     * @return mixed
     * @throws Throwable
     */
    public function challenge(Request $request)
    {
        $hubMode = $request->get('hub_mode');

        /** @var WebhookSubscription $subscription */
        $subscription = WebhookSubscription::query()
            ->where($request->only('activity', 'channel_id'))
            ->first();

        switch ($hubMode) {
            case self::HUB_MODE_SUBSCRIBE:
                return $this->approveSubscription($subscription, $request);
            case self::HUB_MODE_DENIED:
                return $this->denySubscription($subscription, $request);
            case self::HUB_MODE_UNSUBSCRIBE:
                return $this->unsubscribeSubscription($subscription, $request);
            default:
                return abort(500, sprintf('The given hub mode `%s` is unknown.', $hubMode));
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ParseException
     */
    public function store(Request $request): Response
    {
        $channelId = $request->get('channel_id');
        /** @var Channel|null $channel */
        $channel = Channel::query()->whereKey($channelId)->first();

        if ($channel === null) {
            Log::info(sprintf('Got webhook from twitch for a non-existing channel %s.', $channelId));

            return response('', 204, ['Content-Type' => 'text/plain']);
        }

        $activity = $request->get('activity');

        Log::info(sprintf(
            'Got webhook from twitch for channel %s. Suggestion: Got %s activity.',
            $channel->getKey(),
            $activity
        ));

        $this->parseActivities($request, $channel, $activity);

        return response('', 204, ['Content-Type' => 'text/plain']);
    }

    /**
     * @param Request $request
     * @param Channel $channel
     * @param string $topic
     *
     * @throws ParseException
     */
    private function parseActivities(Request $request, Channel $channel, string $topic): void
    {
        $parser = new TwitchTopicParser();
        $dispatched = 0;

        foreach ($request->get('data') as $item) {
            $parsedTopic = $parser->parse($topic, $item);
            event(new WebhookWasCalled($channel, $topic, $parsedTopic->getResponse(), $this->getTwitchNotificationId($request)));
            $dispatched++;
        }

        if ($dispatched !== 1) {
            Log::info(sprintf(
                'Got %s %s data from twitch webhook for channel %s. Suggestion: Stream goes offline.',
                $dispatched,
                $topic,
                $channel->getKey()
            ));
        }

        // handle special event for stream down
        if ($dispatched === 0 && $topic === 'streams') {
            event(new WebhookWasCalled($channel, $topic, [
                'id' => null,
                'name' => null,
                'revenue' => null,
                'description' => null,
                'data' => [],
            ], $this->getTwitchNotificationId($request)));
        }
    }

    /**
     * @param WebhookSubscription $subscription
     * @param Request $request
     *
     * @return ResponseFactory|Response|mixed
     * @throws Throwable
     */
    private function approveSubscription(WebhookSubscription $subscription, Request $request)
    {
        if ($subscription === null) {
            return abort(404, 'Stored subscription is required but null.');
        }

        $subscription->forceFill([
            'confirmed_at' => now()->format('Y-m-d H:i:s'),
            'lease' => $request->get('hub_lease_seconds', $subscription->lease),
        ])->saveOrFail();

        Log::info(sprintf(
            'Got webhook verify request for stored subscription %s. Sending challenge as response...',
            $subscription->getKey()
        ), ['subscription' => $subscription->toArray()]);

        return response($request->get('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @param WebhookSubscription $subscription
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws Exception
     */
    private function denySubscription($subscription, Request $request)
    {
        if ($subscription !== null) {
            $subscription->delete();

            Log::critical(sprintf(
                'Got webhook denied request for stored subscription %s. Stored subscription was deleted successfully.',
                $subscription->getKey()
            ), [
                'subscription' => $subscription->toArray(),
                'hub_reason' => $request->get('hub_reason'),
            ]);
        } else {
            Log::critical('Got webhook denied request without stored subscription.');
        }

        return response('', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @param WebhookSubscription $subscription
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws Exception
     */
    private function unsubscribeSubscription($subscription, Request $request)
    {
        if ($subscription !== null) {
            $subscription->delete();

            Log::info(sprintf(
                'Got webhook unsubscribe request for stored subscription %s.',
                $subscription->getKey()
            ), ['subscription' => $subscription->toArray()]);
        } else {
            Log::critical('Got webhook unsubscribe request without stored subscription.');
        }

        return response($request->get('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Implements the Twitch-Notification-Id to ensure,
     * that we dont get a event twice in our database.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getTwitchNotificationId(Request $request): string
    {
        return $request->headers->get('twitch-notification-id');
    }
}