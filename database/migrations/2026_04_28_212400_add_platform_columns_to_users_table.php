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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->string('blockchain_id', 16)->nullable()->unique()->after('business_id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('mobile', 32)->nullable()->after('email');
            $table->string('employee_code')->nullable()->after('mobile');
            $table->string('status')->default('invited')->after('employee_code');
            $table->timestamp('last_signed_in_at')->nullable()->after('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('business_id');
            $table->dropUnique('users_blockchain_id_unique');
            $table->dropColumn([
                'blockchain_id',
                'first_name',
                'last_name',
                'mobile',
                'employee_code',
                'status',
                'last_signed_in_at',
            ]);
        });
    }
};
