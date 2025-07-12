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
        Schema::create('leave_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->string('name')->unique();
            $table->integer('days')->nullable();
        });

        DB::table('leave_categories')->insert([
            ['id' => 1, 'leave_type_id' => 1, 'name' => 'Vacation', 'days' => null], 
            ['id' => 2, 'leave_type_id' => 1, 'name' => 'Sick', 'days' => null],
            ['id' => 3, 'leave_type_id' => 2, 'name' => 'Bereavement', 'days' => 3],
            ['id' => 4, 'leave_type_id' => 2, 'name' => 'Maternity', 'days' => 105],
            ['id' => 5, 'leave_type_id' => 2, 'name' => 'Paternity', 'days' => 14],
            ['id' => 6, 'leave_type_id' => 2, 'name' => 'Solo Parent', 'days' => 7],
            ['id' => 7, 'leave_type_id' => 2, 'name' => 'VAWC', 'days' => 10],
            ['id' => 8, 'leave_type_id' => 2, 'name' => 'Magna Carta', 'days' => 60],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_categories');
    }
};
