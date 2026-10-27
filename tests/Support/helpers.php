<?php

use App\Models\Category;
use App\Models\Family;
use App\Models\FamilyFile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Shared helpers for the API feature tests
|--------------------------------------------------------------------------
|
| Loaded once from tests/Pest.php. Pest helpers are global functions, so
| every name here must stay unique across the whole test suite.
|
*/

/**
 * Logs in through the API. Expects $this->user to be the account of
 * ana@example.com (see AuthTest).
 */
function apiLogin(array $overrides = [])
{
    return test()->postJson('/api/v1/auth/login', array_merge([
        'email'       => 'ana@example.com',
        'password'    => 'password',
        'device_name' => 'revit-plugin@PC-01',
    ], $overrides));
}

/**
 * Authenticates the next requests as the given user (default: $this->user).
 */
function signIn(?User $user = null): User
{
    $user ??= test()->user;

    Sanctum::actingAs($user);

    return $user;
}

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

function subscribe(User $user, ?Plan $plan = null, string $status = 'active'): Subscription
{
    $plan ??= makePlan();

    return Subscription::create([
        'user_id'       => $user->id,
        'plan_id'       => $plan->id,
        'billing_cycle' => 'monthly',
        'status'        => $status,
        'started_at'    => now(),
        'renews_at'     => now()->addMonth(),
    ]);
}

function makeCategory(string $slug = 'portas', array $overrides = []): Category
{
    return Category::firstOrCreate(
        ['slug' => $slug],
        array_merge(['name' => ucfirst($slug)], $overrides),
    );
}

/**
 * Published free family owned by $this->user, unless overridden.
 */
function makeFamily(array $overrides = []): Family
{
    static $n = 0;
    $n++;

    return Family::create(array_merge([
        'user_id'       => test()->user->id,
        'category_id'   => makeCategory()->id,
        'name'          => "Familia {$n}",
        'slug'          => "familia-{$n}",
        'revit_version' => 2025,
        'is_free'       => true,
        'status'        => Family::STATUS_PUBLISHED,
    ], $overrides));
}

/**
 * Stores a fake .rfa on the (faked) private disk and registers it for the family.
 * The test must call Storage::fake('local') first.
 */
function attachRfaFile(Family $family): Family
{
    $path = "families/rfa/{$family->slug}.rfa";

    Storage::disk('local')->put($path, 'fake rfa content');

    $family->files()->create([
        'type'          => FamilyFile::TYPE_RFA,
        'disk'          => 'local',
        'path'          => $path,
        'original_name' => "{$family->slug}.rfa",
        'size_bytes'    => 16,
        'is_primary'    => true,
    ]);

    return $family;
}