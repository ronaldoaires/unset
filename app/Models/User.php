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
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
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

    // Days between a deletion request and the permanent closing of the account.
    const DELETION_GRACE_DAYS = 30;

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
        'cpf_hash',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'last_login_at'       => 'datetime',
        'birth_date'          => 'date',
        'is_active'           => 'boolean',
        'password'            => 'hashed',
        'delete_requested_at' => 'datetime',
        'cpf'                 => 'encrypted',
        'terms_accepted_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        // Keeps the searchable hash in sync with the encrypted CPF, including when it is cleared.
        static::saving(function (User $user) {
            if ($user->isDirty('cpf')) {
                $user->cpf_hash = filled($user->cpf) ? static::hashCpf($user->cpf) : null;
            }
        });
    }

    /**
     * Keyed hash of the CPF digits, used to detect duplicates without decrypting anything.
     */
    public static function hashCpf(string $digits): string
    {
        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }

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

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
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
     * Ids of the paid families that count against this month's quota: free
     * ones and purchased ones never use it. Shared by the download rule and
     * the account page, so both always agree.
     *
     * @return Collection<int, int>
     */
    public function monthlyQuotaFamilyIds(): Collection
    {
        return $this->downloads()
            ->where('downloaded_at', '>=', now()->startOfMonth())
            ->whereHas('family', fn($query) => $query->where('is_free', false))
            ->whereNotIn('family_id', $this->purchasedFamilyIds())
            ->pluck('family_id')
            ->unique();
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

        $usedFamilyIds = $this->monthlyQuotaFamilyIds();

        if ($family && $usedFamilyIds->contains($family->id)) {
            return true;
        }

        return $usedFamilyIds->count() < $limit;
    }

    /**
     * Download quota of the current month, or null without an active subscription.
     * Needs "activeSubscription.plan" loaded (lazy loading is blocked outside production).
     *
     * @return array{limit: ?int, used: int, resets_at: \Illuminate\Support\Carbon}|null
     */
    public function monthlyQuota(): ?array
    {
        $plan = $this->activeSubscription?->plan;

        if (! $plan) {
            return null;
        }

        return [
            'limit'     => $plan->max_downloads_per_month, // null = unlimited
            'used'      => $this->monthlyQuotaFamilyIds()->count(),
            'resets_at' => now()->addMonthNoOverflow()->startOfMonth(),
        ];
    }

    /**
     * Memoized per request: a page with many cards (each with a heart button)
     * costs a single query instead of one per card.
     */
    protected ?array $favoriteIdsCache = null;

    /**
     * Ids of the families this user has favorited.
     *
     * @return array<int, int>
     */
    public function favoriteFamilyIds(): array
    {
        return $this->favoriteIdsCache ??= $this->favorites()
            ->pluck('family_id')
            ->map(fn($id) => (int) $id)
            ->all();
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
     * URL of the profile picture, or null when the user has none (views show
     * the initials instead). An external URL is returned as-is (legacy or
     * Google picture); an uploaded file is served through the public disk.
     * Usage: $user->avatar_url
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (blank($this->avatar)) {
                    return null;
                }

                return $this->hasUploadedAvatar()
                    ? Storage::disk('public')->url($this->avatar)
                    : $this->avatar;
            }
        );
    }

    /**
     * First letter of the first and last names, e.g. "Maria da Silva" -> "MS".
     * Usage: $user->initials
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function () {
                $words = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);

                if (! $words) {
                    return '?';
                }

                $first = mb_substr($words[0], 0, 1);
                $last  = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';

                return mb_strtoupper($first . $last);
            }
        );
    }

    /**
     * True when the avatar is a file stored by the app (not an external URL).
     */
    public function hasUploadedAvatar(): bool
    {
        return filled($this->avatar) && ! Str::startsWith($this->avatar, ['http://', 'https://']);
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
            ->where('delete_requested_at', '>', now()->subDays(self::DELETION_GRACE_DAYS));
    }

    /**
     * Accounts whose grace period is over. Anonymized accounts have no e-mail
     * anymore, which is how they are told apart from the ones still to process.
     */
    public function scopeDueForDeletion($query)
    {
        return $query->whereNotNull('delete_requested_at')
            ->where('delete_requested_at', '<=', now()->subDays(self::DELETION_GRACE_DAYS))
            ->whereNotNull('email');
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

    /**
     * Only active moderators and superadmins may enter the admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && $this->is_active;
    }
}
