<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('customer_account_id')->nullable()->after('id')->constrained('customer_accounts')->nullOnDelete();
            $table->foreignUuid('home_business_entity_id')->nullable()->after('customer_account_id')->constrained('business_entities')->nullOnDelete();
            $table->string('timezone', 64)->nullable()->after('contractor_type');
            $table->string('locale', 16)->nullable()->after('timezone');
            $table->jsonb('metadata')->nullable()->after('locale');
            $table->softDeletes();

            $table->index(['customer_account_id', 'status']);
            $table->index('home_business_entity_id');
            $table->index('person_type');
        });

        Schema::create('user_business_access', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission_role', 32)->default('worker');
            $table->string('access_status', 32)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignUuid('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_account_id', 'access_status'], 'uba_account_status_idx');
            $table->index(['business_entity_id', 'permission_role'], 'uba_business_role_idx');
            $table->index('user_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX uba_user_business_active_unique '.
            'ON user_business_access (user_id, business_entity_id) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('user_workplace_access', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission_role', 32)->default('worker');
            $table->string('access_status', 32)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignUuid('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_account_id', 'access_status'], 'uwa_account_status_idx');
            $table->index(['workplace_id', 'permission_role'], 'uwa_workplace_role_idx');
            $table->index('user_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX uwa_user_workplace_active_unique '.
            'ON user_workplace_access (user_id, workplace_id) '.
            'WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_workplace_access');
        Schema::dropIfExists('user_business_access');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['customer_account_id', 'status']);
            $table->dropIndex(['home_business_entity_id']);
            $table->dropIndex(['person_type']);
            $table->dropConstrainedForeignId('home_business_entity_id');
            $table->dropConstrainedForeignId('customer_account_id');
            $table->dropColumn(['timezone', 'locale', 'metadata', 'deleted_at']);
        });
    }
};
