<?php

namespace GhostZero\TwitchToolkit\TopicParser\Parsers;

use GhostZero\TwitchToolkit\TopicParser\Contracts\TopicParser;
use GhostZero\TwitchToolkit\TopicParser\TwitchParsedTopic;
use Illuminate\Support\Str;

class StreamsParser extends TopicParser
{

    /**
     * @inheritdoc
     */
    public function parse(array $data): TwitchParsedTopic
    {
        return (new TwitchParsedTopic('streams', $this))
            ->withMessageId(Str::uuid())
            ->withResponse($this->response(
                null, null, null, null, $data
            ));
    }
}