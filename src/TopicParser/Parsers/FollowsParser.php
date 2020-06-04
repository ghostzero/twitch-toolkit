<?php

namespace GhostZero\LPTHOOT\TopicParser\Parsers;

use GhostZero\LPTHOOT\TopicParser\Contracts\TopicParser;
use GhostZero\LPTHOOT\TopicParser\TwitchParsedTopic;

class FollowsParser extends TopicParser
{
    /**
     * @inheritdoc
     */
    public function parse(array $data): TwitchParsedTopic
    {
        return (new TwitchParsedTopic('follows', $this))
            ->withMessageId(sprintf('%s:%s:%s', $data['from_id'], $data['to_id'], $data['followed_at']))
            ->withResponse($this->response(
                $data['from_id'], $data['from_name'], null, null, $data
            ));
    }
}