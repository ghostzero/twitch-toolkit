<?php

namespace GhostZero\TwitchToolkit\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string activity
 * @property int channel_id
 * @property Channel channel
 * @property int lease
 * @property CarbonInterface confirmed_at
 * @property CarbonInterface leased_at
 * @property CarbonInterface updated_at
 * @property CarbonInterface created_at
 */
class WebhookSubscription extends Model
{
    protected $table = 'twitch_toolkit_webhook_subscriptions';

    protected $guarded = [];

    protected $dates = [
        'confirmed_at',
        'leased_at',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
