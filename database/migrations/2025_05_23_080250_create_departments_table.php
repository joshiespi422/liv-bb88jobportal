<?php

use Illuminate\Support\Facades\DB;
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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('dept_name')->unique();
        });

        DB::table('departments')->insert([
            ['id' => 1, 'dept_name' => 'Admin'], 
            ['id' => 2, 'dept_name' => 'Creative'],
            ['id' => 3, 'dept_name' => 'Developer'],
            ['id' => 4, 'dept_name' => 'Technical'],
            ['id' => 5, 'dept_name' => 'IT Support'],
            ['id' => 6, 'dept_name' => 'Architecture'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
