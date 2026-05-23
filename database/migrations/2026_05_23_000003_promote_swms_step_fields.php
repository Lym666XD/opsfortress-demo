<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('swms_activity_steps', function (Blueprint $table) {
            $table->string('initial_risk_level')->nullable()->after('minimum_read_seconds');
            $table->string('residual_risk_level')->nullable()->after('initial_risk_level');
            $table->text('residual_risk_reason')->nullable()->after('residual_risk_level');
            $table->boolean('stop_work_trigger')->nullable()->after('residual_risk_reason');
            $table->boolean('evidence_required')->nullable()->after('stop_work_trigger');
            $table->text('evidence_prompt')->nullable()->after('evidence_required');
            $table->text('quick_view_summary')->nullable()->after('evidence_prompt');
            $table->string('primary_task_performer')->nullable()->after('quick_view_summary');
            $table->string('supervisory_verification')->nullable()->after('primary_task_performer');
        });
    }

    public function down(): void
    {
        Schema::table('swms_activity_steps', function (Blueprint $table) {
            $table->dropColumn([
                'initial_risk_level',
                'residual_risk_level',
                'residual_risk_reason',
                'stop_work_trigger',
                'evidence_required',
                'evidence_prompt',
                'quick_view_summary',
                'primary_task_performer',
                'supervisory_verification',
            ]);
        });
    }
};
