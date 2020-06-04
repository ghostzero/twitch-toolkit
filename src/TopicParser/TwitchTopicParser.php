<?php

namespace GhostZero\LPTHOOT\TopicParser;

use Exception;
use GhostZero\LPTHOOT\TopicParser\Contracts\TopicParser;
use GhostZero\LPTHOOT\TopicParser\Parsers;

class TwitchTopicParser
{
    /**
     * @var array
     */
    protected $parsers = [
        'bits' => Parsers\BitsParser::class,
        'subscriptions' => Parsers\SubscriptionsParser::class,
        'follows' => Parsers\FollowsParser::class,
        'streams' => Parsers\StreamsParser::class,
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