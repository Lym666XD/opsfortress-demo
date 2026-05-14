<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('blockchain_id', 16)->unique();
            $table->string('legal_name');
            $table->string('trading_name');
            $table->string('abn', 32)->nullable();
            $table->string('business_type')->default('company');
            $table->string('logo_path')->nullable();
            $table->string('primary_email')->nullable();
            $table->string('primary_phone', 32)->nullable();
            $table->string('status')->default('onboarding');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('workplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('classification')->default('standard');
            $table->string('street_address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 64)->nullable();
            $table->string('postcode', 16)->nullable();
            $table->string('country', 2)->default('AU');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_meters')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('occupations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('occupations')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('scope')->default('platform');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('workplaces');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('tenants');
    }
};
