<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The names the organizer puts in the roulette, each in a group
     * ("matanda" or "bata"). Each group has its own wheel; a participant's
     * number is their position within their group (ordered by id).
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('group', 10)->default('matanda')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
