<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $defaultBranchId = Branch::query()->orderBy('id')->value('id');

        if ($defaultBranchId) {
            DB::table('trainers')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        }

        Schema::table('trainers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
