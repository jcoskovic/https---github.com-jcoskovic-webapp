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
        Schema::dropIfExists('user_interactions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('abbreviation_id')->constrained()->onDelete('cascade');
            $table->string('interaction_type'); // 'view', 'search', 'vote', 'comment'
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'interaction_type']);
            $table->index(['abbreviation_id', 'interaction_type']);
            $table->index('created_at');
        });
    }
};
