<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the person-identity dimension to the users table.
 *
 * Per Role_Architecture_Notes.md, "permission role" (Worker/Supervisor/Manager/Admin)
 * and "person identity type" (Employee/Contractor/Labour Hire/Other) are independent.
 * Permission role lives in user_roles (and later in spatie/laravel-permission).
 * Person identity type lives directly on users.
 *
 *   person_type     : enum-like string  (employee | labour_hire | contractor | other)
 *   contractor_type : nullable string   (e.g. cleaning, construction, maintenance)
 *                     populated only when person_type = 'contractor'
 *
 * NOTE on cross-business contractor access (the "ABC Cleaning works at XYZ site"
 * scenario from Role_Architecture_Notes.md): no extra column is needed. The
 * existing workplace_user_assignments.business_id semantically represents the
 * HOST business of the assignment, while users.business_id represents the
 * worker's HOME (employer) business. The model already supports the case;
 * we just need to interpret it correctly in code (see comment on
 * WorkplaceUserAssignment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('person_type', 32)->nullable()->after('status');
            $table->string('contractor_type', 64)->nullable()->after('person_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['person_type', 'contractor_type']);
        });
    }
};
