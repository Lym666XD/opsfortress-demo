<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->nullable()->constrained('customer_accounts')->nullOnDelete();
            $table->string('import_type', 64)->default('content_workbook');
            $table->string('status', 32)->default('pending');
            $table->foreignUuid('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->jsonb('summary')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index('import_type');
        });

        Schema::create('import_source_files', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('file_hash', 128);
            $table->string('file_hash_algorithm', 32)->default('sha256');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('workbook_type', 64)->nullable();
            $table->string('status', 32)->default('pending');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->index('file_hash');
        });

        DB::statement(
            'CREATE UNIQUE INDEX import_source_files_batch_hash_unique '.
            'ON import_source_files (import_batch_id, file_hash)',
        );

        Schema::create('import_validation_results', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignUuid('import_source_file_id')->nullable()->constrained('import_source_files')->cascadeOnDelete();
            $table->string('severity', 16)->default('error');
            $table->string('rule_code', 128);
            $table->text('message');
            $table->string('source_sheet_name')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->string('source_column_name')->nullable();
            $table->string('target_table')->nullable();
            $table->string('target_column')->nullable();
            $table->text('raw_value')->nullable();
            $table->jsonb('context')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['import_batch_id', 'severity']);
            $table->index(['source_sheet_name', 'source_row_number'], 'ivr_source_location_idx');
            $table->index('rule_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_validation_results');
        Schema::dropIfExists('import_source_files');
        Schema::dropIfExists('import_batches');
    }
};
