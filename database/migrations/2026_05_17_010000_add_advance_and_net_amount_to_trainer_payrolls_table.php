<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_payrolls', function (Blueprint $table) {
            $table->decimal('advance_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('net_amount', 12, 2)->default(0)->after('advance_amount');
        });

        DB::table('trainer_payrolls')->update([
            'advance_amount' => 0,
            'net_amount' => DB::raw('total_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('trainer_payrolls', function (Blueprint $table) {
            $table->dropColumn(['advance_amount', 'net_amount']);
        });
    }
};
