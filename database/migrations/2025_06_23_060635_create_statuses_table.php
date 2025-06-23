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
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status_name')->unique();
        });

        DB::table('statuses')->insert([
            ['id' => 1, 'status_name' => 'in progress'], 
            ['id' => 2, 'status_name' => 'for approval'],
            ['id' => 3, 'status_name' => 'done'], 
            ['id' => 4, 'status_name' => 'revision'],
            ['id' => 5, 'status_name' => 'pending'], 
            ['id' => 6, 'status_name' => 'approved'],
            ['id' => 7, 'status_name' => 'rejected'], 
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
