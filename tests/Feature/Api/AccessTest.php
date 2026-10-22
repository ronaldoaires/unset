<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function makePlan(array $overrides = []): Plan
{
    return Plan::create(array_merge([
        'name'                    => 'Pro',
        'slug'                    => 'pro',
        'price_monthly'           => 49.90,
        'price_yearly'            => 399,
        'max_downloads_per_month' => 5,
        'features'                => [
            'modules'     => ['architectural'],
            'permissions' => ['can_rate'],
        ],
    ], $overrides));
}

function subscribe(User $user, Plan $plan, string $status = 'active'): Subscription
{
    return Subscription::create([
        'user_id'       => $user->id,
        'plan_id'       => $plan->id,
        'billing_cycle' => 'monthly',
        'status'        => $status,
        'started_at'    => now(),
        'renews_at'     => now()->addMonth(),
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('returns an empty access block for a user without a plan', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.access.has_access', false)
        ->assertJsonPath('data.access.modules', [])
        ->assertJsonPath('data.access.subscription', null)
        ->assertJsonPath('data.access.quota', null);
});

it('returns plan, modules, permissions and quota for an active subscriber', function () {
    subscribe($this->user, makePlan());

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.access.has_access', true)
        ->assertJsonPath('data.access.modules', ['architectural'])
        ->assertJsonPath('data.access.permissions', ['can_rate'])
        ->assertJsonPath('data.access.subscription.plan.slug', 'pro')
        ->assertJsonPath('data.access.quota.limit', 5)
        ->assertJsonPath('data.access.quota.used', 0);
});

it('shows a past due subscription without granting access', function () {
    subscribe($this->user, makePlan(), 'past_due');

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.access.has_access', false)
        ->assertJsonPath('data.access.modules', [])
        ->assertJsonPath('data.access.subscription.status', 'past_due');
});

it('includes the access block in the login response', function () {
    subscribe($this->user, makePlan());

    $this->postJson('/api/v1/auth/login', [
        'email'       => $this->user->email,
        'password'    => 'password',
        'device_name' => 'revit-plugin@PC-01',
    ])
        ->assertOk()
        ->assertJsonPath('access.has_access', true)
        ->assertJsonPath('access.modules', ['architectural']);
});