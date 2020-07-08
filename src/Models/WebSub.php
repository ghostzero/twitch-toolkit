<?php

namespace GhostZero\TwitchToolkit\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null channel_id
 * @property string feed_url
 * @property string callback_url
 * @property string|null secret
 * @property bool active
 * @property int lease_seconds
 * @property CarbonInterface expires_at
 * @property CarbonInterface leased_at
 * @property CarbonInterface|null confirmed_at
 * @property bool denied
 * @property CarbonInterface|null denied_at
 * @property string|null denied_reason
 * @property array|null last_response
 */
class WebSub extends Model
{
    protected $table = 'twitch_toolkit_web_subs';

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'accepted' => 'boolean',
        'expires_at' => 'datetime',
        'leased_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'denied_at' => 'datetime',
        'last_response' => 'array',
    ];
}
