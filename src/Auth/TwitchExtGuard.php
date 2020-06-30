<?php

namespace GhostZero\TwitchToolkit\Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Auth\RequestGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwitchExtGuard
{
    /**
     * @var string
     */
    public static $CACHE_KEY = 'twitch:auth.%s';

    /**
     * @var bool
     */
    public static $WITH_CACHE = false;

    /**
     * @var UserProvider
     */
    protected $userProvider;

    /**
     * The secrets of the twitch extension guard.
     * @var array
     */
    private static $extSecrets = [];

    /**
     * Create a new authentication guard.
     *
     * @param UserProvider $userProvider
     */
    public function __construct(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Adds a secret for the twitch extension guard.
     *
     * @param string $secret
     */
    public static function addExtSecret(string $secret)
    {
        static::$extSecrets[] = $secret;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function user(Request $request)
    {

        if (!$request->headers->has('Authorization')) {
            return null;
        }

        try {
            $token = explode(' ', $request->headers->get('Authorization'))[1] ?? null;

            $fn = function () use ($token) {
                $decoded = $this->decodeAuthorizationToken($token);
                return $this->resolveUser($decoded);
            };

            if (!self::$WITH_CACHE) {
                return $fn();
            }

            return cache()->remember($this->getCacheKey($token), now()->addMinutes(5), $fn);
        } catch (Exception $exception) {
            return null;
        }
    }

    private function getCacheKey($token): string
    {
        return sprintf(self::$CACHE_KEY, sha1($token));
    }

    private function decodeAuthorizationToken(string $token)
    {
        foreach (self::$extSecrets as $extSecret) {
            try {
                return JWT::decode($token, base64_decode($extSecret), ['HS256']);
            } catch (SignatureInvalidException $exception) {
                // do nothing
            }
        }

        throw new SignatureInvalidException('Twitch extension sSignature verification failed.');
    }

    /**
     * Registers the twitch extension guard as new auth guard.
     *
     * Add this to your AuthServiceProvider::boot() method.
     *
     * @param string $secret
     * @param UserProvider $twitchUserProvider
     * @param string $driver
     */
    public static function register(string $secret, UserProvider $twitchUserProvider, $driver = 'twitch'): void
    {
        self::addExtSecret($secret);
        Auth::extend($driver, function ($app, $name, array $config) use ($twitchUserProvider) {
            return new RequestGuard(function ($request) use ($config, $twitchUserProvider) {
                return (new self($twitchUserProvider))->user($request);
            }, app('request'));
        });
    }

    /**
     * @param $decoded
     * @return HasTwitchToken|Builder|Model|object|null
     */
    private function resolveUser($decoded)
    {
        $user = $this->userProvider->retrieveById($decoded->user_id);
        $user = $user ?? $this->userProvider->createFromTwitchToken($decoded);

        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'withTwitchToken')) {
            $user = $user->withTwitchToken($decoded);
            $user->convertAnonymousAccount();
        }

        $user->cached_at = now();

        return $user;
    }

}
