<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('abbreviations', function (Blueprint $table) {
            $table->id();
            $table->string('abbreviation', 50)->index();
            $table->string('meaning');
            $table->text('description')->nullable();
            $table->string('department')->nullable();
            $table->string('category')->nullable();
            $table->json('suggested_meanings')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['abbreviation', 'department']);
            $table->index(['status']);

            // Only add fulltext index for MySQL/PostgreSQL, not SQLite
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['abbreviation', 'meaning', 'description']);
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('abbreviations');
    }
};
