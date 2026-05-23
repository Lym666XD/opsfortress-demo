<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE import_validation_results ADD CONSTRAINT import_validation_results_rule_code_prefix_check '.
            "CHECK (rule_code ~ '^(schema|structure|fk|business|dup)(:[A-Za-z0-9_.-]+)+$')",
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE import_validation_results DROP CONSTRAINT IF EXISTS import_validation_results_rule_code_prefix_check');
    }
};
