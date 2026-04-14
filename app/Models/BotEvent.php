<?php

namespace App\Models;

use Database\Factories\BotEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'short_link_id',
    'endpoint',
    'action',
    'reason',
    'ip_hash',
    'user_agent',
    'referrer',
    'device_type',
    'browser',
    'os',
    'country_code',
    'region',
    'city',
    'created_at',
])]
class BotEvent extends Model
{
    /** @use HasFactory<BotEventFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ShortLink, $this>
     */
    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }
}
