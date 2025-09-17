<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Optimiziraj postojeće indexe
        Schema::table('abbreviations', function (Blueprint $table) {
            // Composite index za search i filtering
            $table->index(['status', 'category', 'created_at'], 'idx_abbreviations_search');

            // Index za trending algoritam
            $table->index(['created_at', 'status'], 'idx_abbreviations_trending');

            // Full-text index za pretraživanje - samo za MySQL/PostgreSQL
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['abbreviation', 'meaning', 'description'], 'idx_abbreviations_fulltext');
            }
        });

        Schema::table('votes', function (Blueprint $table) {
            // Composite index za glasovanje analytics
            $table->index(['abbreviation_id', 'type', 'created_at'], 'idx_votes_analytics');

            // Index za trending calculation
            $table->index(['created_at', 'type'], 'idx_votes_trending');
        });

        Schema::table('comments', function (Blueprint $table) {
            // Index za comment ordering
            $table->index(['abbreviation_id', 'created_at'], 'idx_comments_order');
        });

        Schema::table('user_interactions', function (Blueprint $table) {
            // Composite index za ML algoritam
            $table->index(['user_id', 'interaction_type', 'created_at'], 'idx_interactions_ml');

            // Index za personalized recommendations
            $table->index(['abbreviation_id', 'interaction_type'], 'idx_interactions_abbr');
        });

        // Optimiziraj user tabelu
        Schema::table('users', function (Blueprint $table) {
            // Index za admin queries
            $table->index(['role', 'created_at'], 'idx_users_admin');
        });
    }

    public function down()
    {
        Schema::table('abbreviations', function (Blueprint $table) {
            $table->dropIndex('idx_abbreviations_search');
            $table->dropIndex('idx_abbreviations_trending');
            // Only drop fulltext index if it exists (non-SQLite)
            if (config('database.default') !== 'sqlite') {
                $table->dropIndex('idx_abbreviations_fulltext');
            }
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex('idx_votes_analytics');
            $table->dropIndex('idx_votes_trending');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_order');
        });

        Schema::table('user_interactions', function (Blueprint $table) {
            $table->dropIndex('idx_interactions_ml');
            $table->dropIndex('idx_interactions_abbr');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_admin');
        });
    }
};
