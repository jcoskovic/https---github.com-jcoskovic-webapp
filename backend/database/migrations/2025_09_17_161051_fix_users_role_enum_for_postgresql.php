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
        // For PostgreSQL, we need to handle ENUM changes differently
        // First, add the new role column as string temporarily
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_temp')->nullable();
        });
        
        // Copy existing data to the temporary column
        DB::statement("UPDATE users SET role_temp = role::text");
        
        // Drop the old role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        
        // Add the new role column with updated enum values
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'moderator', 'admin'])->default('user');
        });
        
        // Copy data back from temporary column
        DB::statement("UPDATE users SET role = role_temp WHERE role_temp IN ('user', 'admin')");
        DB::statement("UPDATE users SET role = 'user' WHERE role_temp NOT IN ('user', 'admin') OR role_temp IS NULL");
        
        // Drop the temporary column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_temp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For rollback, revert to the original enum values
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_temp')->nullable();
        });
        
        DB::statement("UPDATE users SET role_temp = role::text");
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user');
        });
        
        DB::statement("UPDATE users SET role = CASE WHEN role_temp = 'moderator' THEN 'user' ELSE role_temp END");
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_temp');
        });
    }
};
