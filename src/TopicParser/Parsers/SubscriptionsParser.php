<?php

namespace GhostZero\LPTHOOT\TopicParser\Parsers;

use GhostZero\LPTHOOT\TopicParser\Contracts\TopicParser;
use GhostZero\LPTHOOT\TopicParser\TwitchParsedTopic;

/**
 * New Twitch Subscriptions TopicParser via Webhooks
 * @see https://dev.twitch.tv/docs/api/webhooks-reference/#topic-subscription-events
 */
class SubscriptionsParser extends TopicParser
{

    /**
     * @inheritdoc
     */
    public function parse(array $data): TwitchParsedTopic
    {
        return (new TwitchParsedTopic('subscriptions', $this))
            ->withMessageId($data['id'])
            ->withResponse($this->response(
                $data['event_data']['user_id'],
                $data['event_data']['user_name'],
                $this->getRevenueBySubscriptionPlan($data['event_data']['tier']),
                $data['event_data']['message'] ?? null,
                $data
            ));
    }

    /**
     * Determines an estimated revenue value which the streamer receives with this subscription.
     *
     * @param string $plan
     *
     * @return int|null
     * @todo determinate revenue by used currency; not supported by twitch at the moment
     */
    private function getRevenueBySubscriptionPlan(?string $plan): ?int
    {
        switch ($plan) {
            case 'prime':
            case '1000':
                return 499;
            case '2000':
                return 999;
            case '3000':
                return 2499;
            default:
                return null;
        }
    }
}