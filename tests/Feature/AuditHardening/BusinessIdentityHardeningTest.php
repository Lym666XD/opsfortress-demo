<?php

declare(strict_types=1);

namespace Tests\Feature\AuditHardening;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\Shared\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies K1 + K2 + K3 — Kevin-driven business identity hardening
 * (2026-05-13 reply).
 *
 *   K1: blockchain_id is auto-generated as ULID on Business creation and
 *       immutable thereafter.
 *   K2: businesses.abn is globally unique (partial index where not null);
 *       null abn is allowed many times.
 *   K3: blockchain_id is NOT included in default model serialization
 *       (Business / User toArray()).
 */
final class BusinessIdentityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $context;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = app(TenantContext::class);
        $this->context->clear();

        $this->tenant = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'k-test',
            'name' => 'K Test Tenant',
        ]));

        $this->context->set($this->tenant->id);
    }

    // ----- K1 -----

    public function test_business_blockchain_id_is_auto_generated_as_ulid_on_create(): void
    {
        $business = Business::create([
            'legal_name' => 'Auto Gen Pty Ltd',
            'trading_name' => 'Auto Gen',
        ]);

        $this->assertNotEmpty($business->blockchain_id);
        $this->assertSame(26, strlen($business->blockchain_id), 'ULID is 26 chars');

        // ULID = Crockford base32: digits + uppercase letters minus I, L, O, U.
        $this->assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/i',
            $business->blockchain_id,
        );
    }

    public function test_explicitly_passed_blockchain_id_is_preserved_on_create(): void
    {
        // The auto-generator only fires when blockchain_id is empty. Existing
        // seed data passes an explicit value; the observer must not clobber it.
        $explicit = (string) Str::ulid();

        $business = Business::create([
            'legal_name' => 'Explicit Pty Ltd',
            'trading_name' => 'Explicit',
            'blockchain_id' => $explicit,
        ]);

        $this->assertSame($explicit, $business->blockchain_id);
    }

    public function test_business_blockchain_id_is_immutable_after_creation(): void
    {
        $business = Business::create([
            'legal_name' => 'Immutable Pty Ltd',
            'trading_name' => 'Immutable',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('blockchain_id is immutable');

        $business->update(['blockchain_id' => (string) Str::ulid()]);
    }

    public function test_user_blockchain_id_is_immutable_after_being_set(): void
    {
        // blockchain_id is intentionally NOT in User's fillable list (it's a
        // system-internal field, never customer-entered). To set it we use
        // direct property assignment + save, which is how system code would
        // assign one in practice.
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'U',
            'email' => 'u@test',
            'password' => Hash::make('password'),
        ]);

        // First assignment (null → value) is allowed.
        $user->blockchain_id = (string) Str::ulid();
        $user->save();

        // Sanity: the first assignment really persisted.
        $this->assertNotNull($user->fresh()->blockchain_id);

        // Second assignment must throw.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('blockchain_id is immutable');

        $user->blockchain_id = (string) Str::ulid();
        $user->save();
    }

    public function test_user_blockchain_id_cannot_be_mass_assigned(): void
    {
        // K3 defense in depth: blockchain_id is not fillable on User, so a
        // controller that naively passes $request->all() into User::create()
        // cannot inject a custom blockchain_id from form input.
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'M',
            'email' => 'm@test',
            'password' => Hash::make('password'),
            'blockchain_id' => 'attacker-supplied-value',
        ]);

        $this->assertNull($user->blockchain_id);
    }

    // ----- K2 -----

    public function test_duplicate_abn_is_rejected_by_partial_unique_index(): void
    {
        Business::create([
            'legal_name' => 'First Pty Ltd',
            'trading_name' => 'First',
            'abn' => '12 345 678 901',
        ]);

        $this->expectException(QueryException::class);

        // Even across tenants this should fail — ABN is GLOBALLY unique
        // per Kevin's rule, not just per-tenant.
        Business::create([
            'legal_name' => 'Second Pty Ltd',
            'trading_name' => 'Second',
            'abn' => '12 345 678 901',
        ]);
    }

    public function test_null_abn_is_allowed_multiple_times(): void
    {
        Business::create([
            'legal_name' => 'No ABN Co A',
            'trading_name' => 'A',
        ]);

        $second = Business::create([
            'legal_name' => 'No ABN Co B',
            'trading_name' => 'B',
        ]);

        // No throw — null ABN is exempt from the partial unique index
        // (sole traders during onboarding may legitimately have no ABN yet).
        $this->assertNotNull($second->id);
    }

    // ----- K3 -----

    public function test_business_blockchain_id_is_hidden_from_array(): void
    {
        $business = Business::create([
            'legal_name' => 'Hidden Co',
            'trading_name' => 'Hidden',
        ]);

        $array = $business->toArray();

        $this->assertArrayNotHasKey('blockchain_id', $array);
        $this->assertArrayHasKey('legal_name', $array);  // sanity
    }

    public function test_user_blockchain_id_is_hidden_from_array(): void
    {
        // Set blockchain_id directly (not via mass assignment — see
        // test_user_blockchain_id_cannot_be_mass_assigned) so we can verify
        // the #[Hidden] attribute is what actually keeps it out of toArray,
        // not just the absence of a value.
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hidden U',
            'email' => 'hu@test',
            'password' => Hash::make('password'),
        ]);
        $user->blockchain_id = (string) Str::ulid();
        $user->save();

        $this->assertNotNull($user->fresh()->blockchain_id, 'value really persisted');

        $array = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('blockchain_id', $array);
        $this->assertArrayHasKey('email', $array);  // sanity
    }
}
