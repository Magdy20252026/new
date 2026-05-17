<?php

use App\Models\Administrator;
use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrator_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Branch::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Administrator::class)->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['administrator_id', 'period_start', 'period_end']);
            $table->index(['branch_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrator_payrolls');
    }
};
