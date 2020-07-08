<?php

namespace GhostZero\TwitchToolkit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookWasCalled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?string $channelId;
    public string $topic;
    public array $metadata;
    public string $messageId;

    public function __construct(?string $channelId, string $topic, array $metadata, string $messageId)
    {
        $this->channelId = $channelId;
        $this->topic = $topic;
        $this->metadata = $metadata;
        $this->messageId = $messageId;
    }
}
