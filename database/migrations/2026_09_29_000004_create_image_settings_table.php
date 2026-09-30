<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How each uploaded picture is fitted into its frame on the website. Keyed by
        // the picture's stored path, so the choice travels with the picture wherever
        // it is shown, and no other table needs a column per image field.
        Schema::create('image_settings', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->string('fit', 12)->nullable();            // null = automatic (show the whole picture)
            $table->unsignedTinyInteger('focus_x')->default(50); // 0-100, % from the left
            $table->unsignedTinyInteger('focus_y')->default(50); // 0-100, % from the top
            $table->unsignedSmallInteger('zoom')->default(100);  // 100-400, %
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_settings');
    }
};
