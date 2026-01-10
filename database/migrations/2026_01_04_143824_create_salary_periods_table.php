<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salary_periods', function (Blueprint $table) {
            $table->id();
            $table->string('month');
            $table->enum('cycle', ['1st', '2nd']);
        });

        DB::table('salary_periods')->insert([
            ['id' => 1, 'month' => 'january', 'cycle' => '1st'], 
            ['id' => 2, 'month' => 'january', 'cycle' => '2nd'],
            ['id' => 3, 'month' => 'february', 'cycle' => '1st'], 
            ['id' => 4, 'month' => 'february', 'cycle' => '2nd'],
            ['id' => 5, 'month' => 'march', 'cycle' => '1st'], 
            ['id' => 6, 'month' => 'march', 'cycle' => '2nd'],
            ['id' => 7, 'month' => 'april', 'cycle' => '1st'], 
            ['id' => 8, 'month' => 'april', 'cycle' => '2nd'],
            ['id' => 9, 'month' => 'may', 'cycle' => '1st'],
            ['id' => 10, 'month' => 'may', 'cycle' => '2nd'],
            ['id' => 11, 'month' => 'june', 'cycle' => '1st'],
            ['id' => 12, 'month' => 'june', 'cycle' => '2nd'],
            ['id' => 13, 'month' => 'july', 'cycle' => '1st'],
            ['id' => 14, 'month' => 'july', 'cycle' => '2nd'],
            ['id' => 15, 'month' => 'august', 'cycle' => '1st'],
            ['id' => 16, 'month' => 'august', 'cycle' => '2nd'],
            ['id' => 17, 'month' => 'september', 'cycle' => '1st'],
            ['id' => 18, 'month' => 'september', 'cycle' => '2nd'],
            ['id' => 19, 'month' => 'october', 'cycle' => '1st'],
            ['id' => 20, 'month' => 'october', 'cycle' => '2nd'],
            ['id' => 21, 'month' => 'november', 'cycle' => '1st'],
            ['id' => 22, 'month' => 'november', 'cycle' => '2nd'],
            ['id' => 23, 'month' => 'december', 'cycle' => '1st'],
            ['id' => 24, 'month' => 'december', 'cycle' => '2nd'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_periods');
    }
};
