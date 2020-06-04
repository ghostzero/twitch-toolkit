<?php

namespace GhostZero\LPTHOOT\TopicParser\Parsers;

use GhostZero\LPTHOOT\TopicParser\Contracts\TopicParser;
use GhostZero\LPTHOOT\TopicParser\TwitchParsedTopic;
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