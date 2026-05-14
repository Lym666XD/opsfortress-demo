<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Permissions\Models\UserRole;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Note on tenancy: User is intentionally NOT using BelongsToTenant trait.
 * User is the bootstrap of tenant context — a user has a tenant_id, but
 * queries against the users table happen during login when no tenant
 * context exists yet. The global scope would prevent login itself.
 *
 * Cross-tenant user listings (admin dashboards) are guarded at the
 * Policy / controller layer, not via global scope.
 */
#[Fillable([
    'tenant_id',
    'business_id',
    'first_name',
    'last_name',
    'name',
    'email',
    'mobile',
    'employee_code',
    'status',
    'person_type',
    'contractor_type',
    'last_signed_in_at',
    'password',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'blockchain_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * K1: User.blockchain_id is immutable once set. Same Kevin-driven rule as
     * Business — internal-only audit identifier that must never change.
     * (We don't auto-generate one on creation because user blockchain_id is
     * nullable and not currently in use; if a future flow assigns one,
     * after that point it's frozen.)
     */
    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            $original = $user->getOriginal('blockchain_id');
            if ($original !== null && $user->isDirty('blockchain_id')) {
                throw new \RuntimeException(
                    'User.blockchain_id is immutable once set (K1).',
                );
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // FK int casts — PG's PDO driver returns BIGINT as string when
            // the model is created from form input (not refreshed from DB).
            'tenant_id' => 'integer',
            'business_id' => 'integer',
            'email_verified_at' => 'datetime',
            'last_signed_in_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Check if the user holds a given role by code (e.g. 'admin', 'worker').
     *
     * Audit hardening A4: the query explicitly filters user_roles.tenant_id
     * to match this user's tenant_id. This defends against the edge case
     * where a corrupted user_roles row links a user to a role in a different
     * tenant — the global scope on UserRole is bypassed here (we go through
     * raw DB queries because login & permission checks must work BEFORE
     * TenantContext is set), so without this explicit filter the attacker-
     * inserted row would pass.
     *
     * Pre-spatie helper. If permission complexity grows (custom permissions,
     * cached lookups, multiple guards), revisit the "install spatie/laravel-
     * permission" decision in MILESTONE.md.
     */
    public function hasRole(string $code): bool
    {
        if ($this->tenant_id === null) {
            return false;
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $this->id)
            ->where('user_roles.tenant_id', $this->tenant_id)
            ->where('roles.code', $code)
            ->exists();
    }

    /**
     * All role codes held by this user within their own tenant.
     * Used by HandleInertiaRequests to expose roles to the frontend for
     * conditional nav rendering. Backend authorization checks should go
     * through Policies, not by reading this list.
     *
     * @return array<int, string>
     */
    public function roleCodes(): array
    {
        if ($this->tenant_id === null) {
            return [];
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $this->id)
            ->where('user_roles.tenant_id', $this->tenant_id)
            ->pluck('roles.code')
            ->unique()
            ->values()
            ->all();
    }
}
