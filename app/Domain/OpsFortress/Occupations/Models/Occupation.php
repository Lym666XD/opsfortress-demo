<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Occupations\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Occupation extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active_status' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
