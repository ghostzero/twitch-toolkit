<?php

namespace GhostZero\TwitchToolkit\Utils;

use Closure;
use Exception;
use romanzipp\Twitch\Twitch;

class TwitchUserResolver
{
    public static $CACHE_KEY = 'twitch:user.%s';

    /**
     * Fetch the user information from twitch based on the login or id.
     * This response will be cached for 12h (because of rate limits).
     *
     * @param string $identifier can be a string or int
     * @param Twitch $twitch
     * @param bool $detailedStats
     *
     * @return null|object
     * @throws Exception
     */
    public static function fetchUser($identifier, Twitch $twitch, bool $detailedStats = false)
    {
        $identifier = strtolower($identifier);

        return cache()->remember(
            sprintf(self::$CACHE_KEY, $identifier),
            now()->addHours(12),
            self::resolver($identifier, $twitch, $detailedStats)
        );
    }

    private static function fetchDetailedUserStats($userObject)
    {
        return $userObject;
    }

    private static function resolver($identifier, Twitch $twitch, bool $detailedStats): Closure
    {
        return static function () use ($identifier, $twitch, $detailedStats) {
            if (is_numeric($identifier)) {
                $response = $twitch->getUserById($identifier);
            } else {
                $response = $twitch->getUserByName($identifier);
            }

            if (!$response->success()) {
                return null;
            }

            $userObject = $response->shift();

            if ($detailedStats) {
                $userObject = self::fetchDetailedUserStats($userObject);
            }

            return $userObject;
        };
    }
}