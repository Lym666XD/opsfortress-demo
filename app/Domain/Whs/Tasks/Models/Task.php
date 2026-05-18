<?php

declare(strict_types=1);

namespace App\Domain\Whs\Tasks\Models;

use App\Domain\Whs\Swms\Models\PrestartQuestion;
use App\Domain\Whs\Swms\Models\SwmsVersion;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
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

    public function swmsVersions(): HasMany
    {
        return $this->hasMany(SwmsVersion::class);
    }

    public function prestartQuestions(): HasMany
    {
        return $this->hasMany(PrestartQuestion::class);
    }
}
