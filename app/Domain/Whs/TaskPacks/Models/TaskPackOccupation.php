<?php

declare(strict_types=1);

namespace App\Domain\Whs\TaskPacks\Models;

use Illuminate\Database\Eloquent\Model;

class TaskPackOccupation extends Model
{
    protected $table = 'task_pack_occupations';

    protected $fillable = [
        'task_pack_id',
        'occupation_id',
        'access_level',
    ];

    /**
     * FK int casts — PG's PDO driver returns BIGINT as string.
     */
    protected $casts = [
        'task_pack_id' => 'integer',
        'occupation_id' => 'integer',
    ];
}
