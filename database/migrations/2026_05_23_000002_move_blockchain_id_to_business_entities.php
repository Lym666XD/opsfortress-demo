<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_entities', function (Blueprint $table) {
            $table->uuid('blockchain_id')
                ->nullable()
                ->default(DB::raw('gen_random_uuid()'))
                ->after('id');

            $table->unique('blockchain_id');
        });

        DB::statement('UPDATE business_entities SET blockchain_id = gen_random_uuid() WHERE blockchain_id IS NULL');
        DB::statement('ALTER TABLE business_entities ALTER COLUMN blockchain_id SET NOT NULL');

        if (Schema::hasColumn('users', 'blockchain_id')) {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_blockchain_id_unique');

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('blockchain_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'blockchain_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('blockchain_id', 26)->nullable()->unique()->after('id');
            });
        }

        Schema::table('business_entities', function (Blueprint $table) {
            $table->dropUnique('business_entities_blockchain_id_unique');
            $table->dropColumn('blockchain_id');
        });
    }
};
