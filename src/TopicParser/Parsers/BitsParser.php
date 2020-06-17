<?php

namespace GhostZero\LPTHOOT\TopicParser\Parsers;

use GhostZero\LPTHOOT\TopicParser\Contracts\TopicParser;
use GhostZero\LPTHOOT\TopicParser\TwitchParsedTopic;

class BitsParser extends TopicParser
{
    /**
     * @inheritdoc
     */
    public function parse(array $data): TwitchParsedTopic
    {
        return (new TwitchParsedTopic('bits', $this))
            ->withMessageId($data['userstate']['id'])
            ->withResponse($this->response(
                $data['userstate']['user-id'],
                $data['userstate']['username'],
                $data['userstate']['bits'],
                $data['message'],
                $data
            ));
    }
}