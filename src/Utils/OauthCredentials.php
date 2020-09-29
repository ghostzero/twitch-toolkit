<?php

namespace GhostZero\TwitchToolkit\Utils;

use Carbon\CarbonInterface;

class OauthCredentials
{
    private string $accessToken;
    private string $refreshToken;
    private CarbonInterface $expiresAt;

    public function __construct(string $accessToken, string $refreshToken, CarbonInterface $expiresAt)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getExpiresAt(): CarbonInterface
    {
        return $this->expiresAt;
    }
}
