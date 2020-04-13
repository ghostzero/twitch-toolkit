<?php

namespace GhostZero\LPTHOOT\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string id
 * @property bool is_online
 * @property CarbonInterface polled_at
 */
class Channel extends Model
{
    protected $table = 'lpthoot_channels';

    protected $casts = ['is_online' => 'bool'];

    protected $dates = ['changed_at'];

    public static function subscribe(string $id): self
    {
        /** @var self $self */
        $self = self::query()->firstOrCreate(['id' => $id], [
            'id' => $id,
        ]);

        return $self;
    }

    public static function unsubscribe(string $id)
    {
        return self::query()->whereKey($id)->delete();
    }
}
