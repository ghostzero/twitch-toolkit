<?php

namespace GhostZero\LPTHOOT\Models;

use Carbon\CarbonInterface;
use Closure;
use GhostZero\LPTHOOT\Exceptions\AccessTokenExpired;
use GhostZero\LPTHOOT\Jobs\SubscribeTwitchWebhooks;
use GhostZero\LPTHOOT\Utils\OauthCredentials;
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

    private const LPTHOOT_REQUIRES_FRESH_OAUTH_CREDENTIALS = 'lpthoot:requiresFreshOauthCredentials';

    protected $table = 'lpthoot_channels';

    protected $casts = [
        'is_online' => 'bool',
        'capabilities' => 'array',
    ];

    protected $dates = ['changed_at'];

    protected $guarded = [];

    public static function subscribe(string $id, array $capabilities = [self::TYPE_POLLING]): self
    {
        $self = self::updateSubscription($id, $capabilities);

        if (in_array(self::TYPE_WEBHOOK, $self->capabilities)) {
            dispatch(new SubscribeTwitchWebhooks($self));
        }

        return $self;
    }

    public static function unsubscribe(string $id): ?bool
    {
        return self::updateSubscription($id, [])->exists;
    }

    private static function updateSubscription(string $id, array $capabilities): Channel
    {
        /** @var self $self */
        $self = self::query()->firstOrCreate(['id' => $id], [
            'id' => $id,
            'capabilities' => $capabilities,
        ]);

        $self->forceFill([
            'capabilities' => $capabilities,
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
            ->listen(self::LPTHOOT_REQUIRES_FRESH_OAUTH_CREDENTIALS, $callback);
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
            static::getEventDispatcher()->dispatch(self::LPTHOOT_REQUIRES_FRESH_OAUTH_CREDENTIALS, [$this]);
        }

        return $value;
    }
}
