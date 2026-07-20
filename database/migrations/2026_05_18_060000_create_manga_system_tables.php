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
        // 1. Create Mangas Table
        Schema::create('mangas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('ongoing'); // ongoing, completed, hiatus
            $table->string('type')->default('manga'); // manga, manhwa, manhua
            $table->string('author')->nullable();
            $table->string('artist')->nullable();
            $table->integer('release_year')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->bigInteger('views_count')->default(0);
            $table->timestamps();
        });

        // 2. Create Chapters Table
        Schema::create('chapters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('manga_id')->constrained('mangas')->onDelete('cascade');
            $table->decimal('chapter_number', 6, 2);
            $table->string('title');
            $table->string('slug')->unique();
            $table->bigInteger('views_count')->default(0);
            $table->timestamps();
        });

        // 3. Create Genres Table
        Schema::create('genres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 4. Create Manga-Genre Pivot Table
        Schema::create('manga_genre', function (Blueprint $table) {
            $table->foreignUuid('manga_id')->constrained('mangas')->onDelete('cascade');
            $table->foreignUuid('genre_id')->constrained('genres')->onDelete('cascade');
            $table->primary(['manga_id', 'genre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manga_genre');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('mangas');
    }
};
