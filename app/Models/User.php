<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo_url',
        'role',
        'role_id',
        'user_type',
        'is_active',
        'gender',
        'phone',
        'institution',
        'work_study_status',
        'country',
        'status',
        'is_bypassed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_bypassed' => 'boolean',
        ];
    }

    /**
     * Get the role model that the user belongs to (for RBAC permission checks).
     */
    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if the user's role has a given permission.
     */
    public function hasPermission(string $permission): bool
    {
        $roleModel = $this->roleModel;

        return $roleModel
            && is_array($roleModel->permissions)
            && in_array($permission, $roleModel->permissions);
    }

    /**
     * Scope: only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: only suspended users.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope: only admin panel users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('user_type', 'admin');
    }

    /**
     * Scope: only mobile app members.
     */
    public function scopeMembers($query)
    {
        return $query->where('user_type', 'member');
    }

    /**
     * Get the member's current subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(MemberSubscription::class);
    }
}
