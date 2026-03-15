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
        Schema::create('attendance_report_holiday', function (Blueprint $table) {
            $table->foreignId('attendance_report_id')
                ->constrained('attendance_reports')
                ->onDelete('cascade');
            $table->foreignId('holiday_id')
                ->constrained('holidays')
                ->onDelete('cascade');

            $table->primary(['attendance_report_id', 'holiday_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_report_holiday');
    }
};
