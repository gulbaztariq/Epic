<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Home page "Our Focus Areas" strip.
        Schema::create('focus_areas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('icon')->default('chart');
            $table->string('color')->default('navy'); // navy | green
            $table->string('url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Generic, grouped list content (principles, strengths, themes, project types,
        // event types, partner categories, MoU scope, memberships, pillars, highlights...).
        Schema::create('list_items', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group', 'sort']);
        });

        // Home page "Data & Insights" tiles.
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('value');
            $table->string('caption')->nullable();
            $table->string('icon')->default('chart');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // EPIC Team / Board of Governance / Advisory Council.
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('category')->default('team'); // team, board, advisory
            $table->string('photo')->nullable();
            $table->text('short_bio')->nullable();
            $table->longText('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'sort']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->string('status')->default('ongoing'); // ongoing, completed, upcoming
            $table->string('partners')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // International chapters.
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('status')->default('active'); // active, forming
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Partnerships, MoUs and memberships.
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('partnership'); // partnership, mou, membership
            $table->string('category')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->date('signed_on')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'sort']);
        });

        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('Full Time'); // Full Time, Part Time, Internship, Consultancy
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('requirements')->nullable();
            $table->date('deadline')->nullable();
            $table->string('apply_url')->nullable();
            $table->string('apply_email')->nullable();
            $table->boolean('is_open')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('stats');
        Schema::dropIfExists('list_items');
        Schema::dropIfExists('focus_areas');
    }
};
