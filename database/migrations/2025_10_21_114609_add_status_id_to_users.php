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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('status_id')->after('user_type_id')
            ->nullable() // must be nullable to add to existing rows
            ->constrained('statuses')->onDelete('restrict');
        });

        // populate the data for existing rows
        DB::table('users')
            ->whereIn('user_type_id', [1, 2])
            ->update(['status_id' => 10]); // active
        DB::table('users')
            ->where('user_type_id', 3)
            ->update(['status_id' => 13]); // ongoing
            
        // make it non-nullable AFTER it's populated.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
