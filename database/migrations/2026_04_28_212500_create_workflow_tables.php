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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'business_id']);
        });

        Schema::create('user_occupations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'occupation_id', 'business_id']);
        });

        Schema::create('workplace_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workplace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_context')->nullable();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_to')->nullable();
            $table->timestamps();

            $table->unique(['workplace_id', 'user_id']);
        });

        Schema::create('task_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('category')->default('swms');
            $table->string('status')->default('draft');
            $table->string('version')->default('1.0.0');
            $table->text('summary')->nullable();
            $table->boolean('requires_swms_ack')->default(true);
            $table->boolean('requires_prestart')->default(true);
            $table->boolean('requires_posttask')->default(false);
            $table->boolean('requires_training')->default(false);
            $table->string('pdf_template')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('task_pack_occupations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->string('access_level')->default('full');
            $table->timestamps();

            $table->unique(['task_pack_id', 'occupation_id']);
        });

        Schema::create('task_pack_industries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->string('access_level')->default('full');
            $table->timestamps();

            $table->unique(['task_pack_id', 'industry_id']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workplace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_pack_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('blockchain_id', 16)->nullable()->unique();
            $table->string('original_hash', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workplace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('submission_type')->default('prestart');
            $table->string('status')->default('draft');
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedSmallInteger('critical_failures')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->string('blockchain_id', 16)->nullable()->unique();
            $table->string('original_hash', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type');
            $table->string('status')->default('queued');
            $table->string('disk')->default('s3');
            $table->string('path')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('file_uploads');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('task_pack_industries');
        Schema::dropIfExists('task_pack_occupations');
        Schema::dropIfExists('task_packs');
        Schema::dropIfExists('workplace_user_assignments');
        Schema::dropIfExists('user_occupations');
        Schema::dropIfExists('user_roles');
    }
};
