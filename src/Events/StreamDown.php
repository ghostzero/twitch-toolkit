<?php

namespace GhostZero\TwitchToolkit\Events;

use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamDown
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Channel
     */
    public $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }
}
