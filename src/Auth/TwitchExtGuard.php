<?php

namespace GhostZero\TwitchToolkit\Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwitchExtGuard
{
    /**
     * @var string
     */
    public static $USER_CLASS = User::class;

    /**
     * @var string
     */
    public static $CACHE_KEY = 'twitch:auth.%s';

    /**
     * @var UserProvider
     */
    protected $provider;

    /**
     * The secrets of the twitch extension guard.
     * @var string
     */
    private static $extSecrets = [];

    /**
     * Create a new authentication guard.
     *
     * @param UserProvider $provider
     *
     * @return void
     */
    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
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

            return cache()->remember($this->getCacheKey($token), now()->addMinutes(5), function () use ($token) {
                $decoded = $this->decodeAuthorizationToken($token);
                return $this->resolveUser($decoded);
            });
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @return string|User|HasTwitchToken
     */
    public function getUserClass()
    {
        return self::$USER_CLASS;
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
     * @param string $userClass
     * @param string $driver
     */
    public static function register(string $secret, string $userClass, $driver = 'twitch'): void
    {
        self::$USER_CLASS = $userClass;
        self::addExtSecret($secret);
        Auth::extend($driver, function ($app, $name, array $config) {
            return new RequestGuard(function ($request) use ($config) {
                return (new self(
                    Auth::createUserProvider($config['provider'])
                ))->user($request);
            }, app('request'));
        });
    }

    /**
     * @param $decoded
     * @return HasTwitchToken|Builder|Model|object|null
     */
    private function resolveUser($decoded)
    {
        $user = $this->getUserClass()::query()->where(['platform_id' => $decoded->user_id])->first();
        if ($user !== null) {
            if (method_exists($user, 'withTwitchToken')) {
                $user = $user->withTwitchToken($decoded);
                $user->convertAnonymousAccount();
            }
        } elseif (method_exists($user, 'createFromTwitchToken')) {
            $user = $this->getUserClass()::createFromTwitchToken($decoded);
        }

        if (!$user) {
            return null;
        }

        $user->cached_at = now();

        return $user;
    }

}
