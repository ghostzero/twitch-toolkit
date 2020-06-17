<?php

namespace GhostZero\LPTHOOT\Auth;

use Illuminate\Foundation\Auth\User;

/**
 * @mixin User
 */
trait HasTwitchToken
{

    /**
     * @var string|null
     */
    protected $twitchToken;

    public function getTwitchToken(): ?string
    {
        return $this->twitchToken;
    }

    public function withTwitchToken($decoded): self
    {
        $this->twitchToken = $decoded;

        return $this;
    }

    public function convertAnonymousAccount(): void
    {
        //
    }

    public static function createFromTwitchToken($decoded, array $attributes = []): ?self
    {
        return null;
    }
}