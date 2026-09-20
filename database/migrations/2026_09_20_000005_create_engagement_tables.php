<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('organisation')->nullable();
            $table->string('status')->default('subscribed'); // subscribed, unsubscribed
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('organisation')->nullable();
            $table->string('subject')->nullable();
            $table->longText('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('volunteer_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('interest')->nullable();
            $table->string('availability')->nullable();
            $table->longText('message')->nullable();
            $table->string('cv_path')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_applications');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('subscribers');
    }
};
