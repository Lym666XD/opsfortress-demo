<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documents\Models;

use App\Domain\OpsFortress\Workplaces\Models\WorkplaceExternalParty;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDeliveryEvent extends Model
{
    use UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function generatedDocument(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class);
    }

    public function workplaceExternalParty(): BelongsTo
    {
        return $this->belongsTo(WorkplaceExternalParty::class);
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
