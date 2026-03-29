<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSubscription extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'package_id',
        'package_name',
        'package_price',
        'subscribed_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'package_price' => 'decimal:2',
            'subscribed_at' => 'datetime',
            'expires_at'    => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Whether the subscription is currently active (not yet expired).
     */
    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }
}
