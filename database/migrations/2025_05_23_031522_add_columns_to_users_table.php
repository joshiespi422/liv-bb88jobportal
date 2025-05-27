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
        Schema::table('users', function (Blueprint $table) {
            $table->unique('name');
            $table->foreignId('user_type_id')->after('id')->constrained('user_types')->onDelete('restrict');
            $table->string('qr_code')->after('name')->nullable()->unique();
            $table->string('position')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('address')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('bday')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_name_unique');
            $table->dropUnique('users_qr_code_unique');
            $table->dropForeign(['user_type_id']);
            $table->dropColumn([
                'user_type_id',
                'qr_code',
                'position',
                'profile_picture',
                'address',
                'gender',
                'bday'
            ]);
        });
    }
};
