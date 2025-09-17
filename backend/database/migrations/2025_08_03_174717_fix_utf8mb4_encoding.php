<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run UTF8 conversion for MySQL, not SQLite
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER DATABASE abbrevio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            $tables = [
                'users',
                'abbreviations',
                'votes',
                'comments',
                'user_interactions',
                'cache',
                'migrations',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback not implemented to avoid data corruption
    }
};
