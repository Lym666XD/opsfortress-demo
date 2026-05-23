<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\People\Models;

use App\Domain\OpsFortress\Accounts\Models\CustomerAccount;
use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserOccupation extends Model
{
    use BelongsToAccount, SoftDeletes, UsesUuidPrimaryKey;

    protected $table = 'user_occupations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'metadata' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
