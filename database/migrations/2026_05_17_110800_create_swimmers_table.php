<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swimmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('serial_number')->unique();
            $table->string('barcode');
            $table->string('name');
            $table->unsignedSmallInteger('birth_year');
            $table->string('father_phone');
            $table->string('mother_phone');
            $table->date('subscription_start_date');
            $table->date('subscription_end_date');
            $table->decimal('group_price', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swimmers');
    }
};
