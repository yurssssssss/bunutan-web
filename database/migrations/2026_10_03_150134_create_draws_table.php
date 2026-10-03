<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per spin: who spun and which name they got.
     *
     * The unique keys enforce the rules even if two people spin at once:
     * - spinner_key: each person spins only once
     * - picked_participant_id: each name is picked only once
     */
    public function up(): void
    {
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->string('spinner_name', 60);
            $table->string('group', 10); // the wheel they spun: "matanda" or "bata"
            // "p:<participant id>" when the typed name matched the list, otherwise "n:<normalized name>"
            $table->string('spinner_key', 100)->unique();
            $table->foreignId('spinner_participant_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->foreignId('picked_participant_id')->nullable()->unique()->constrained('participants')->nullOnDelete();
            // copies kept so results still read correctly if the organizer edits the list later
            $table->string('picked_name', 60);
            $table->unsignedInteger('picked_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
