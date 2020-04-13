<?php

namespace GhostZero\LPTHOOT\Events;

use GhostZero\LPTHOOT\Models\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamUp
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
