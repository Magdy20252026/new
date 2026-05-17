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
        Schema::table('training_groups', function (Blueprint $table) {
            $table->unsignedInteger('available_training_days')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_groups', function (Blueprint $table) {
            $table->unsignedTinyInteger('available_training_days')->change();
        });
    }
};
