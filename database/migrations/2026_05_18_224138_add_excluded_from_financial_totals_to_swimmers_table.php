<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('swimmers', function (Blueprint $table) {
            $table->boolean('excluded_from_financial_totals')->default(false)->after('remaining_amount');
        });
    }

    public function down(): void
    {
        Schema::table('swimmers', function (Blueprint $table) {
            $table->dropColumn('excluded_from_financial_totals');
        });
    }
};
