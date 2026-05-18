<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\OpsFortress\Access\Models\UserBusinessAccess;
use App\Domain\OpsFortress\Access\Models\UserWorkplaceAccess;
use App\Domain\OpsFortress\Accounts\Models\CustomerAccount;
use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * User is intentionally not globally account-scoped.
 *
 * Login must be able to find the user before AccountContext exists. Account
 * boundaries for user management are enforced by policy/controller queries.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable, UsesUuidPrimaryKey;

    protected $fillable = [
        'customer_account_id',
        'home_business_entity_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'mobile',
        'employee_code',
        'status',
        'person_type',
        'contractor_type',
        'timezone',
        'locale',
        'metadata',
        'last_signed_in_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'blockchain_id',
    ];

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
            'email_verified_at' => 'datetime',
            'last_signed_in_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function homeBusinessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class, 'home_business_entity_id');
    }

    public function businessAccesses(): HasMany
    {
        return $this->hasMany(UserBusinessAccess::class);
    }

    public function workplaceAccesses(): HasMany
    {
        return $this->hasMany(UserWorkplaceAccess::class);
    }

    public function hasRole(string $code): bool
    {
        if ($this->customer_account_id === null) {
            return false;
        }

        foreach (['user_business_access', 'user_workplace_access'] as $table) {
            $hasRole = DB::table($table)
                ->where('customer_account_id', $this->customer_account_id)
                ->where('user_id', $this->id)
                ->where('access_status', 'active')
                ->where('permission_role', $code)
                ->exists();

            if ($hasRole) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function roleCodes(): array
    {
        if ($this->customer_account_id === null) {
            return [];
        }

        return DB::table('user_business_access')
            ->where('customer_account_id', $this->customer_account_id)
            ->where('user_id', $this->id)
            ->where('access_status', 'active')
            ->pluck('permission_role')
            ->merge(
                DB::table('user_workplace_access')
                    ->where('customer_account_id', $this->customer_account_id)
                    ->where('user_id', $this->id)
                    ->where('access_status', 'active')
                    ->pluck('permission_role'),
            )
            ->unique()
            ->values()
            ->all();
    }
}
