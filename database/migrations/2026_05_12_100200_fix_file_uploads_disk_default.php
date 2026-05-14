<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes file_uploads.disk default from 's3' to 'local'.
 *
 * Why: dev .env uses FILESYSTEM_DISK=local. With s3 hardcoded as the column
 * default, any file inserted without explicitly setting `disk` would write
 * the string 's3' but actually be stored locally — breaking later reads.
 *
 * After this migration, code is responsible for explicitly passing the disk
 * (preferred) or relying on the now-correct local default in dev. Production
 * will pass 's3' explicitly via the upload service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->string('disk')->default('local')->change();
        });
    }

    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->string('disk')->default('s3')->change();
        });
    }
};
