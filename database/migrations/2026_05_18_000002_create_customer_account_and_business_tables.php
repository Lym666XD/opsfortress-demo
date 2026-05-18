<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('status', 32)->default('onboarding');
            $table->string('timezone', 64)->default('UTC');
            $table->string('locale', 16)->default('en');
            $table->string('billing_email')->nullable();
            $table->jsonb('settings')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('business_entities', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('legal_name');
            $table->string('trading_name')->nullable();
            $table->string('business_type', 64)->nullable();
            $table->string('entity_status', 32)->default('onboarding');
            $table->string('primary_email')->nullable();
            $table->string('primary_phone', 32)->nullable();
            $table->string('website')->nullable();
            $table->jsonb('registered_address')->nullable();
            $table->jsonb('postal_address')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'entity_status']);
        });

        Schema::create('account_businesses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->string('relationship_type', 32)->default('owned');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'relationship_type'], 'account_businesses_rel_idx');
            $table->index('business_entity_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX account_businesses_active_unique '.
            'ON account_businesses (account_id, business_entity_id) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('business_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('identifier_type_id')->constrained('business_identifier_types')->restrictOnDelete();
            $table->string('identifier_value');
            $table->string('normalised_identifier_value');
            $table->string('status', 32)->default('active');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_entity_id', 'status']);
            $table->index('identifier_type_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX business_identifiers_active_unique '.
            'ON business_identifiers (identifier_type_id, normalised_identifier_value) '.
            'WHERE deleted_at IS NULL AND normalised_identifier_value IS NOT NULL',
        );

        Schema::create('workplaces', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('workplace_type', 64)->nullable();
            $table->string('status', 32)->default('active');
            $table->string('street_address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('city')->nullable();
            $table->string('state_region', 64)->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_meters')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'status']);
            $table->index(['business_entity_id', 'status']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX workplaces_business_code_unique '.
            'ON workplaces (business_entity_id, code) '.
            'WHERE deleted_at IS NULL AND code IS NOT NULL',
        );

        Schema::create('workplace_environments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->string('environment_code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workplace_id', 'is_active']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX workplace_envs_active_unique '.
            'ON workplace_environments (workplace_id, environment_code) '.
            'WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('workplace_environments');
        Schema::dropIfExists('workplaces');
        Schema::dropIfExists('business_identifiers');
        Schema::dropIfExists('account_businesses');
        Schema::dropIfExists('business_entities');
        Schema::dropIfExists('customer_accounts');
    }
};
