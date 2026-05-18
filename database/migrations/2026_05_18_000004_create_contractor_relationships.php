<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('host_business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('contractor_business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->string('relationship_type', 64)->default('contractor');
            $table->string('relationship_status', 32)->default('pending');
            $table->jsonb('access_scope')->nullable();
            $table->jsonb('insurance_metadata')->nullable();
            $table->jsonb('compliance_metadata')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'relationship_status'], 'contractors_account_status_idx');
            $table->index('host_business_entity_id', 'contractors_host_idx');
            $table->index('contractor_business_entity_id', 'contractors_contractor_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX contractor_relationships_active_unique '.
            'ON contractor_relationships (host_business_entity_id, contractor_business_entity_id, relationship_type) '.
            'WHERE deleted_at IS NULL',
        );

        DB::statement(
            'ALTER TABLE contractor_relationships '.
            'ADD CONSTRAINT contractor_relationships_distinct_businesses_check '.
            'CHECK (host_business_entity_id <> contractor_business_entity_id)',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_relationships');
    }
};
