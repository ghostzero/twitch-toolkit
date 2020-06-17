<?php

namespace GhostZero\TwitchToolkit\Exceptions;

use DomainException;
use GhostZero\TwitchToolkit\Models\Channel;

class AccessTokenExpired extends DomainException
{
    public static function fromChannel(Channel $channel): self
    {
        return new self("The oauth access token from {$channel->getKey()} has been expired.");
    }
}