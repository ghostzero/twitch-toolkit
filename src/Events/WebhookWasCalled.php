<?php

namespace GhostZero\LPTHOOT\Events;

use GhostZero\LPTHOOT\Models\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookWasCalled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Channel
     */
    public $channel;

    /**
     * @var array
     */
    public $metadata;

    /**
     * @var string
     */
    public $topic;

    /**
     * @var string
     */
    public $messageId;

    public function __construct(Channel $channel, string $topic, array $metadata, string $messageId)
    {
        $this->channel = $channel;
        $this->topic = $topic;
        $this->metadata = $metadata;
        $this->messageId = $messageId;
    }
}
