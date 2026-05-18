<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->char('iso_alpha2', 2)->unique();
            $table->char('iso_alpha3', 3)->unique();
            $table->char('numeric_code', 3)->nullable()->unique();
            $table->string('name');
            $table->string('official_name')->nullable();
            $table->char('default_currency_code', 3)->nullable();
            $table->boolean('active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('business_identifier_types', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('country_id')->constrained('countries')->restrictOnDelete();
            $table->string('identifier_code', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('format_hint')->nullable();
            $table->string('validation_regex')->nullable();
            $table->boolean('active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['country_id', 'identifier_code'], 'bit_country_code_unique');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_identifier_types');
        Schema::dropIfExists('countries');
    }
};
