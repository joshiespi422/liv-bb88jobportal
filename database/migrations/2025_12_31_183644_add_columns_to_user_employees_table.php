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
        Schema::table('user_employees', function (Blueprint $table) {
            $table->boolean('is_head')->default(0)->after('department_id');
            $table->decimal('current_salary', 10, 2)->nullable()->after('hierarchy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_employees', function (Blueprint $table) {
            $table->dropColumn('is_head');
            $table->dropColumn('current_salary');
        });
    }
};
