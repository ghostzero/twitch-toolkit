<?php

namespace GhostZero\TwitchToolkit\Models;

use Carbon\CarbonInterface;
use Closure;
use GhostZero\TwitchToolkit\Exceptions\AccessTokenExpired;
use GhostZero\TwitchToolkit\Jobs\SubscribeTwitchWebhooks;
use GhostZero\TwitchToolkit\Utils\OauthCredentials;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string id
 * @property bool is_online
 * @property CarbonInterface polled_at
 * @property array capabilities
 * @property string oauth_access_token
 * @property string oauth_refresh_token
 * @property CarbonInterface oauth_expires_at
 * @property string broadcaster_type
 */
class Channel extends Model
{
    public const TYPE_POLLING = 'polling';
    public const TYPE_WEBHOOK = 'webhook';

    public const OPTION_CAPABILITIES = 'capabilities';
    public const OPTION_BROADCASTER_TYPE = 'broadcaster_type';

    private const TWITCH_TOOLKIT_REQUIRES_FRESH_OAUTH_CREDENTIALS = 'twitch-toolkit:requiresFreshOauthCredentials';

    protected $table = 'twitch_toolkit_channels';

    protected $casts = [
        'is_online' => 'bool',
        'capabilities' => 'array',
    ];

    protected $dates = ['changed_at'];

    protected $guarded = [];

    public static function subscribe(string $id, array $options = []): self
    {
        $self = self::updateSubscription($id, $options);

        if (in_array(self::TYPE_WEBHOOK, $self->capabilities)) {
            dispatch(new SubscribeTwitchWebhooks($self));
        }

        return $self;
    }

    public static function unsubscribe(string $id): ?bool
    {
        return self::updateSubscription($id, [])->exists;
    }

    private static function updateSubscription(string $id, array $options): Channel
    {
        $options = self::mergeDefaultOptions($options);

        /** @var self $self */
        $self = self::query()->firstOrCreate(['id' => $id], [
            'id' => $id,
            'capabilities' => $options[self::OPTION_CAPABILITIES],
            'broadcaster_type' => $options[self::OPTION_BROADCASTER_TYPE],
        ]);

        $self->forceFill([
            'capabilities' => $options[self::OPTION_CAPABILITIES],
            'broadcaster_type' => $options[self::OPTION_BROADCASTER_TYPE],
        ])->save();

        return $self;
    }

    /**
     * Register a requires fresh oauth credentials event with the dispatcher.
     *
     * @param Closure|string $callback
     * @return void
     */
    public static function requiresFreshOauthCredentials($callback)
    {
        static::getEventDispatcher()
            ->listen(self::TWITCH_TOOLKIT_REQUIRES_FRESH_OAUTH_CREDENTIALS, $callback);
    }

    private static function mergeDefaultOptions(array $options)
    {
        return array_replace_recursive([
            self::OPTION_CAPABILITIES => [
                self::TYPE_POLLING,
            ],
            self::OPTION_BROADCASTER_TYPE => null,
        ], $options);
    }

    /**
     * Update the model oauth credentials.
     *
     * @param OauthCredentials|null $credentials
     * @return bool
     */
    public function updateOauthCredentials(?OauthCredentials $credentials): bool
    {
        if (!$credentials) {
            throw AccessTokenExpired::fromChannel($this);
        }

        return $this->forceFill([
            'oauth_access_token' => $credentials->getAccessToken(),
            'oauth_refresh_token' => $credentials->getRefreshToken(),
            'oauth_expires_at' => $credentials->getExpiresAt(),
        ])->save();
    }

    public function getOauthAccessTokenAttribute($value)
    {
        if (!$this->oauth_access_token || $this->oauth_expires_at->isPast()) {
            static::getEventDispatcher()->dispatch(self::TWITCH_TOOLKIT_REQUIRES_FRESH_OAUTH_CREDENTIALS, [$this]);
        }

        return $value;
    }
}
