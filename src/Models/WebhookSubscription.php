<?php

namespace GhostZero\LPTHOOT\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string id
 */
class WebhookSubscription extends Model
{
    protected $table = 'lpthoot_webhook_subscriptions';

    protected $guarded = [];
}
