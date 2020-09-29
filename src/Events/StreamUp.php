<?php

namespace GhostZero\TwitchToolkit\Events;

use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamUp
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Channel $channel;
    public array $metadata;

    public function __construct(Channel $channel, array $metadata)
    {
        $this->channel = $channel;
        $this->metadata = $metadata;
    }
}
