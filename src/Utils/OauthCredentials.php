<?php

namespace GhostZero\LPTHOOT\Utils;

use Carbon\CarbonInterface;

class OauthCredentials
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var CarbonInterface
     */
    private $expiresAt;

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