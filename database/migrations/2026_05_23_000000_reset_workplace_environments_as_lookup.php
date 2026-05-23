<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('workplace_environments');

        Schema::create('workplace_environments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('environment_code', 64)->unique();
            $table->string('environment_name');
            $table->boolean('active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('active');
        });

        Schema::table('workplaces', function (Blueprint $table) {
            $table->foreignUuid('environment_id')
                ->nullable()
                ->after('country_id')
                ->constrained('workplace_environments')
                ->restrictOnDelete();

            $table->index('environment_id');
        });
    }

    public function down(): void
    {
        Schema::table('workplaces', function (Blueprint $table) {
            $table->dropIndex(['environment_id']);
            $table->dropConstrainedForeignId('environment_id');
        });

        Schema::dropIfExists('workplace_environments');

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
};
