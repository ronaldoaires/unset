<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasPremium;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasPremium;
    use HasApiTokens, Notifiable;

    // ─── Roles ──────────────────────────────────────────────────────
    const ROLE_USER       = 'user';
    const ROLE_ADMIN      = 'admin';
    const ROLE_SUPERADMIN = 'superadmin';

    const ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
        self::ROLE_SUPERADMIN,
    ];

    // Roles that can access the admin panel
    const ADMIN_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_SUPERADMIN,
    ];

    protected $fillable = [
        'name',
        'email',
        'pending_email',
        'pending_email_token',
        'password',
        'cpf',
        'birth_date',
        'phone',
        'avatar',
        'role',
        'is_active',
        'last_login_at',
        'delete_requested_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pending_email_token',
        'cpf',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'last_login_at'       => 'datetime',
        'birth_date'          => 'date',
        'is_active'           => 'boolean',
        'password'            => 'hashed',
        'delete_requested_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────────
    // NOTE: subscriptions() and activeSubscription() live in the
    // HasPremium trait — not redefined here, to avoid two conflicting
    // definitions of the same relationship drifting apart over time
    // (that happened in the old project).

    public function security(): HasOne
    {
        return $this->hasOne(UserSecurity::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function approvedFamilies(): HasMany
    {
        return $this->hasMany(Family::class, 'approved_by');
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    public function isSubscribedTo(string $planSlug): bool
    {
        return $this->activeSubscription?->plan?->slug === $planSlug;
    }

    /**
     * Whether the subscription still has download quota this month.
     * Only paid families count: free ones and purchased ones never use the
     * quota, and downloading the same family again in the month is free.
     */
    public function canDownload(?Family $family = null): bool
    {
        // activeSubscription() already filters out expired/ended subscriptions.
        $sub = $this->activeSubscription;

        if (! $sub) {
            return false;
        }

        $limit = $sub->plan?->max_downloads_per_month;

        // null = unlimited
        if (is_null($limit)) {
            return true;
        }

        $usedFamilyIds = $this->downloads()
            ->where('downloaded_at', '>=', now()->startOfMonth())
            ->whereHas('family', fn ($query) => $query->where('is_free', false))
            ->whereNotIn('family_id', $this->purchasedFamilyIds())
            ->pluck('family_id')
            ->unique();

        if ($family && $usedFamilyIds->contains($family->id)) {
            return true;
        }

        return $usedFamilyIds->count() < $limit;
    }

    // ─── Role checks ──────────────────────────────────────────────────

    /**
     * Superadmin inherits every admin permission.
     * Use this method to guard any admin panel route.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES);
    }

    /**
     * Superadmin only — use for approving/rejecting and managing admins.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    // ─── Accessor ───────────────────────────────────────────────────

    /**
     * Avatar URL with a name-generated fallback.
     * Usage: $user->avatar_url
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->avatar) {
                    return $this->avatar;
                }

                $name = urlencode($this->name);
                return "https://ui-avatars.com/api/?name={$name}&background=random";
            }
        );
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', self::ADMIN_ROLES);
    }

    public function scopeSuperAdmins($query)
    {
        return $query->where('role', self::ROLE_SUPERADMIN);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePendingDeletion($query)
    {
        return $query->whereNotNull('delete_requested_at')
            ->where('delete_requested_at', '>', now()->subDays(30));
    }

    /**
     * Checks whether the user has access to a module or permission.
     * Usage: auth()->user()->hasAccess('families_advanced')
     *        auth()->user()->hasAccess('can_favorite')
     */
    public function hasAccess(string $key): bool
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return false;
        }

        return $plan->can($key);
    }
}
