<?php

namespace GhostZero\TwitchToolkit\TopicParser;

use GhostZero\TwitchToolkit\TopicParser\Contracts\TopicParser;

class TwitchParsedTopic
{
    private TopicParser $parser;
    private string $messageId;
    private array $response;
    private string $topic;

    public function __construct(string $topic, TopicParser $parser)
    {
        $this->topic = $topic;
        $this->parser = $parser;
    }

    public function withMessageId(string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function withResponse(array $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getMessageId(): string
    {
        return sha1(class_basename($this->parser) . ':' .$this->messageId);
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}
