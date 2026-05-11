<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->date('worked_on');
            $table->decimal('hours', 8, 2);
            $table->timestamps();
            $table->unique(['trainer_id', 'worked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_hours');
    }
};
