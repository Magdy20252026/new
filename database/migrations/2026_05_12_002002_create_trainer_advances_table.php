<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->date('advanced_on');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->index('advanced_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_advances');
    }
};
