<?php

namespace GhostZero\TwitchToolkit\Http\Controllers;

use GhostZero\TwitchToolkit\Events\WebhookWasCalled;
use GhostZero\TwitchToolkit\Jobs\SubscribeTwitchWebhooks;
use GhostZero\TwitchToolkit\TopicParser\Parsers\ParseException;
use GhostZero\TwitchToolkit\TopicParser\TwitchTopicParser;
use GhostZero\TwitchToolkit\WebSub\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use romanzipp\Twitch\Twitch;
use Throwable;

class WebhookController extends Controller
{
    private const HUB_MODE_SUBSCRIBE = 'subscribe';
    private const HUB_MODE_UNSUBSCRIBE = 'unsubscribe';
    private const HUB_MODE_DENIED = 'denied';

    /**
     * @param Request $request
     * @param Twitch $twitch
     * @return mixed
     * @throws Throwable
     */
    public function challenge(Request $request, Twitch $twitch)
    {
        $hubMode = $request->get('hub_mode');

        $feedUrl = Subscriber::getFeedUrl($twitch, $request->get('channel_id'), $request->get('activity'));

        $subscriber = new Subscriber();

        Log::info(sprintf('Got Twitch webhook, with %s as hub-mode for %s.', $hubMode, $feedUrl));

        switch ($hubMode) {
            case self::HUB_MODE_SUBSCRIBE:
                $subscriber->approve($feedUrl, $request);
                return response($request->get('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
            case self::HUB_MODE_UNSUBSCRIBE:
                $subscriber->unsubscribe($feedUrl, $request);
                return response($request->get('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
            case self::HUB_MODE_DENIED:
                $subscriber->deny($feedUrl, $request);
                return response('', 200, ['Content-Type' => 'text/plain']);
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
        $activity = $request->get('activity');

        Log::info(sprintf('Got webhook from twitch for channel %s. Suggestion: Got %s activity.', $channelId, $activity));

        $this->parseActivities($request, $channelId, $activity);

        return response('', 204, ['Content-Type' => 'text/plain']);
    }

    /**
     * @param Request $request
     * @param string|null $channelId
     * @param string $topic
     *
     * @throws ParseException
     */
    private function parseActivities(Request $request, ?string $channelId, string $topic): void
    {
        $parser = new TwitchTopicParser();
        $dispatched = 0;

        foreach ($request->get('data') as $item) {
            $parsedTopic = $parser->parse($topic, $item);
            event(new WebhookWasCalled($channelId, $topic, $parsedTopic->getResponse(), $this->getTwitchNotificationId($request)));
            $dispatched++;
        }

        if ($dispatched !== 1) {
            Log::info(sprintf(
                'Got %s %s data from twitch webhook for channel %s. Suggestion: Stream goes offline.',
                $dispatched,
                $topic,
                $channelId
            ));
        }

        // handle special event for stream down
        if ($dispatched === 0 && $topic === 'streams') {
            event(new WebhookWasCalled($channelId, $topic, [
                'id' => null,
                'name' => null,
                'revenue' => null,
                'description' => null,
                'data' => [],
            ], $this->getTwitchNotificationId($request)));
        }
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