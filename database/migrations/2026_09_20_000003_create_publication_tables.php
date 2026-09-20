<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Research reports, policy briefs, working papers, journal issues, newsletters.
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->string('type')->default('Research Report');
            $table->string('collection')->default('collection'); // collection, journal, newsletter
            $table->string('authors')->nullable();
            $table->text('abstract')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('theme')->nullable();
            $table->string('issue')->nullable();
            $table->date('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['collection', 'published_at']);
        });

        // Blogs, articles and press releases.
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('blog'); // blog, article, press_release
            $table->string('author')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('image')->nullable();
            $table->string('tags')->nullable();
            $table->date('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['category', 'published_at']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('event_type')->nullable(); // Policy Dialogue, Roundtable, Webinar...
            $table->text('excerpt')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('mode')->default('In-Person'); // In-Person, Hybrid Event, Online
            $table->string('registration_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('publications');
    }
};
