<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('hours', 8, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamps();

            $table->unique(['trainer_id', 'period_start', 'period_end']);
            $table->index(['branch_id', 'status']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_payrolls');
    }
};
