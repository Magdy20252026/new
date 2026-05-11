<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $mainBranch = Branch::query()->firstOrCreate(['name' => 'الفرع الرئيسي']);

        $manager = User::query()->updateOrCreate(
            ['username' => 'magdy'],
            [
                'password' => '123456',
                'role' => User::ROLE_MANAGER,
                'access_all_branches' => true,
            ]
        );

        $manager->branches()->syncWithoutDetaching([$mainBranch->id]);

        AppSetting::putValue('theme_mode', 'light');
    }
}
