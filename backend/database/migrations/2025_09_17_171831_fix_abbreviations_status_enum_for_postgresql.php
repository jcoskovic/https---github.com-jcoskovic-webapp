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
        // First, add the new status column as string temporarily
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->string('status_temp')->nullable();
        });
        
        // Copy existing data to the temporary column
        DB::statement("UPDATE abbreviations SET status_temp = status::text");
        
        // Drop the old status column
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        // Add the new status column with updated enum values and default
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        });
        
        // Copy data back from temporary column, set default for invalid values
        DB::statement("UPDATE abbreviations SET status = CASE 
            WHEN status_temp IN ('pending', 'approved', 'rejected') THEN status_temp 
            ELSE 'pending' 
        END");
        
        // Drop the temporary column
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->dropColumn('status_temp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For rollback, revert to the original enum values
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->string('status_temp')->nullable();
        });
        
        DB::statement("UPDATE abbreviations SET status_temp = status::text");
        
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
        });
        
        DB::statement("UPDATE abbreviations SET status = status_temp");
        
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->dropColumn('status_temp');
        });
    }
};
