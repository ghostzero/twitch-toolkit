<?php

namespace GhostZero\TwitchToolkit\TopicParser;

use Exception;
use GhostZero\TwitchToolkit\Enums\ActivityTopic;
use GhostZero\TwitchToolkit\TopicParser\Contracts\TopicParser;
use GhostZero\TwitchToolkit\TopicParser\Parsers;

class TwitchTopicParser
{
    /**
     * @var array
     */
    protected $parsers = [
        ActivityTopic::BITS => Parsers\BitsParser::class,
        ActivityTopic::SUBSCRIPTIONS => Parsers\SubscriptionsParser::class,
        ActivityTopic::FOLLOWS => Parsers\FollowsParser::class,
        ActivityTopic::STREAMS => Parsers\StreamsParser::class,
    ];

    /**
     * @param string $topic
     * @param array $message
     *
     * @return TwitchParsedTopic
     * @throws Parsers\ParseException
     */
    public function parse(string $topic, array $message): TwitchParsedTopic
    {
        if (!isset($this->parsers[$topic])) {
            throw Parsers\ParseException::fromTopic($topic);
        }

        $parser = $this->parsers[$topic];
        /** @var TopicParser $parser */
        $parser = new $parser();

        try {
            return $parser->parse($message);
        } catch (Exception $exception) {
            throw Parsers\ParseException::fromParser($parser, $exception);
        }
    }
}