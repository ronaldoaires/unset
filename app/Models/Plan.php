<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'badge_label',
        'description',
        'price_monthly',
        'price_yearly',
        'mp_plan_id_monthly',
        'mp_plan_id_yearly',
        'trial_days',
        'max_downloads_per_month',
        'has_plugin_access',
        'has_api_access',
        'has_priority_support',
        'has_exclusive_content',
        'features',
        'is_active',
        'sort_order',
        'is_highlighted',
    ];

    protected $casts = [
        'price_monthly'           => 'float',
        'price_yearly'            => 'float',
        'trial_days'              => 'integer',
        'max_downloads_per_month' => 'integer',
        'has_plugin_access'       => 'boolean',
        'has_api_access'          => 'boolean',
        'has_priority_support'    => 'boolean',
        'has_exclusive_content'   => 'boolean',
        'features'                => 'array',
        'is_active'               => 'boolean',
        'sort_order'              => 'integer',
        'is_highlighted'          => 'boolean',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Keep a single highlighted plan no matter where the flag is set
        // (table toggle, create/edit modal, seeders).
        static::saved(function (Plan $plan) {
            if ($plan->is_highlighted && $plan->wasChanged('is_highlighted')) {
                static::query()
                    ->whereKeyNot($plan->getKey())
                    ->where('is_highlighted', true)
                    ->update(['is_highlighted' => false]);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price_monthly');
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->price_monthly == 0;
    }

    public function isHighlighted(): bool
    {
        return (bool) $this->is_highlighted;
    }

    /**
     * Percentage discount of the yearly plan compared to the monthly plan.
     * E.g. monthly R$49 → yearly R$399/12 = R$33.25 → ~32% discount.
     */
    public function yearlyDiscountPercent(): int
    {
        if ($this->price_monthly <= 0 || $this->price_yearly <= 0) {
            return 0;
        }

        $monthlyEquivalent = $this->price_yearly / 12;
        $discount = (($this->price_monthly - $monthlyEquivalent) / $this->price_monthly) * 100;

        return (int) round($discount);
    }

    /**
     * Monthly-equivalent value of the yearly plan (for the pricing toggle UI).
     */
    public function yearlyMonthlyEquivalent(): string
    {
        if ($this->price_yearly <= 0) {
            return '0,00';
        }

        return number_format($this->price_yearly / 12, 2, ',', '.');
    }

    /**
     * Checks whether a granular feature is enabled.
     * E.g. $plan->hasFeature('arch_tools')
     */
    public function hasFeature(string $key): bool
    {
        $features = $this->features ?? [];
        return in_array($key, $features['tools'] ?? []) || ($features[$key] ?? false);
    }

    /**
     * User-friendly label for the download cycle.
     */
    public function downloadsLabel(): string
    {
        if (is_null($this->max_downloads_per_month)) {
            return 'Ilimitados';
        }

        return $this->max_downloads_per_month . '/mês';
    }

    /**
     * Short list of what the plan includes, for the account page.
     *
     * @return array<int, string>
     */
    public function benefits(): array
    {
        $benefits = [
            is_null($this->max_downloads_per_month)
                ? 'Downloads ilimitados de famílias pagas'
                : 'Até ' . $this->max_downloads_per_month . ' downloads de famílias pagas por mês',
        ];

        if ($this->hasModule('architectural')) {
            $benefits[] = 'Downloads ilimitados de famílias gratuitas';
            $benefits[] = 'Módulo Arquitetônico no plugin';
        }

        if ($this->hasModule('structural')) {
            $benefits[] = 'Módulo Estrutural no plugin';
        }

        if ($this->has_priority_support) {
            $benefits[] = 'Suporte prioritário';
        }

        if ($this->has_exclusive_content) {
            $benefits[] = 'Conteúdo exclusivo';
        }

        if ($this->has_api_access) {
            $benefits[] = 'Acesso à API';
        }

        return $benefits;
    }

    // ── Modules and permissions ──────────────────────────────

    /**
     * Returns the active modules for this plan.
     * E.g. ['families_basic', 'arch_basic']
     */
    public function activeModules(): array
    {
        return $this->features['modules'] ?? [];
    }

    /**
     * Returns the active permissions for this plan.
     * E.g. ['can_favorite', 'can_rate']
     */
    public function activePermissions(): array
    {
        return $this->features['permissions'] ?? [];
    }

    /**
     * Checks whether the plan has access to a specific module.
     */
    public function hasModule(string $key): bool
    {
        return in_array($key, $this->activeModules());
    }

    /**
     * Checks whether the plan has a specific permission.
     */
    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->activePermissions());
    }

    /**
     * Checks module OR permission by key — single entry point so callers
     * don't need to know whether something is modeled as a module or
     * a permission internally.
     */
    public function can(string $key): bool
    {
        return $this->hasModule($key) || $this->hasPermission($key);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
