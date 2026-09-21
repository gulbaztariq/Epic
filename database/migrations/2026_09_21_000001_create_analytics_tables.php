<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per page view on the public website.
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_key', 64);
            $table->string('session_key', 64);
            $table->string('path', 191);
            $table->string('page_title')->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('referrer_host', 191)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('device_type', 20)->default('desktop');
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('language', 12)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_new_visitor')->default(true);
            $table->boolean('location_resolved')->default(false);
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index('visitor_key');
            $table->index('session_key');
            $table->index('ip_hash');
            $table->index('path');
            $table->index('country_code');
            $table->index('device_type');
            $table->index(['visited_at', 'is_bot']);
            $table->index(['is_bot', 'location_resolved']);
        });

        // Cache of IP -> location lookups, so each address is resolved once.
        Schema::create('ip_locations', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('organisation', 191)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_locations');
        Schema::dropIfExists('visits');
    }
};
