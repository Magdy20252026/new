<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ControlPanel
{
    public static function siteSettings(): array
    {
        return [
            'site_name' => 'لوحة التحكم',
            'theme_mode' => static::themeMode(),
        ];
    }

    public static function themeMode(): string
    {
        if (! Schema::hasTable('app_settings')) {
            return 'light';
        }

        return static::normalizeTheme(AppSetting::valueFor('theme_mode', 'light'));
    }

    public static function normalizeTheme(?string $theme): string
    {
        return $theme === 'dark' ? 'dark' : 'light';
    }

    public static function accessibleBranchesQuery(User $user): Builder
    {
        $query = Branch::query()->orderBy('name');

        if ($user->access_all_branches) {
            return $query;
        }

        return $query->whereIn('id', $user->branches()->select('branches.id'));
    }

    public static function accessibleBranches(User $user): Collection
    {
        return static::accessibleBranchesQuery($user)->get();
    }

    public static function currentBranch(User $user): ?Branch
    {
        $branchId = session('current_branch_id');

        if ($branchId && $user->canAccessBranch((int) $branchId)) {
            $branch = Branch::query()->find($branchId);

            if ($branch) {
                return $branch;
            }
        }

        $branch = static::accessibleBranchesQuery($user)->first();

        if ($branch) {
            session(['current_branch_id' => $branch->id]);
        }

        return $branch;
    }

    public static function visibleUsersQuery(User $user, ?Branch $branch = null): Builder
    {
        $branch ??= static::currentBranch($user);

        $query = User::query()->with('branches')->orderBy('name');

        if (! $branch) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $builder) use ($branch): void {
            $builder
                ->where('access_all_branches', true)
                ->orWhereHas('branches', fn (Builder $branchQuery) => $branchQuery->whereKey($branch->id));
        });
    }

    public static function menuItems(string $active, User $user): array
    {
        $items = [
            [
                'title' => 'الرئيسية',
                'icon' => 'bi-grid-1x2-fill',
                'route' => route('dashboard'),
                'active' => $active === 'dashboard',
            ],
        ];

        if ($user->isManager()) {
            $items[] = [
                'title' => 'الفروع',
                'icon' => 'bi-diagram-3-fill',
                'route' => route('branches.index'),
                'active' => $active === 'branches',
            ];

            $items[] = [
                'title' => 'المستخدمين',
                'icon' => 'bi-people-fill',
                'route' => route('users.index'),
                'active' => $active === 'users',
            ];
        }

        return $items;
    }

    public static function roleOptions(): array
    {
        return [
            User::ROLE_MANAGER => 'مدير',
            User::ROLE_SUPERVISOR => 'مشرف',
        ];
    }
}
