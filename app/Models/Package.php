<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_months',
        'chatbot_access',
        'accessible_category_ids',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'                    => 'decimal:2',
            'duration_months'          => 'integer',
            'chatbot_access'           => 'boolean',
            'accessible_category_ids'  => 'array',
            'is_active'                => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }
}
