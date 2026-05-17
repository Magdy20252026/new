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
    protected const TRAINER_PAYMENT_WEEK_DAYS = [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ];

    public static function siteSettings(): array
    {
        if (! Schema::hasTable('app_settings')) {
            return [
                'site_name' => 'لوحة التحكم',
                'site_logo_path' => '',
                'site_logo' => asset('assets/images/logo.png'),
                'theme_mode' => static::themeMode(),
            ];
        }

        $siteLogoPath = AppSetting::valueFor('site_logo', '');

        return [
            'site_name' => AppSetting::valueFor('site_name', 'لوحة التحكم'),
            'site_logo_path' => $siteLogoPath,
            'site_logo' => static::sanitizeLogoUrl($siteLogoPath),
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

    public static function trainerPaymentWeekDayOptions(): array
    {
        return self::TRAINER_PAYMENT_WEEK_DAYS;
    }

    public static function trainerPaymentWeek(): array
    {
        $defaultStartDay = 'saturday';
        $defaultEndDay = 'thursday';

        if (! Schema::hasTable('app_settings')) {
            return [
                'start_day' => $defaultStartDay,
                'end_day' => $defaultEndDay,
                'start_label' => self::TRAINER_PAYMENT_WEEK_DAYS[$defaultStartDay],
                'end_label' => self::TRAINER_PAYMENT_WEEK_DAYS[$defaultEndDay],
            ];
        }

        $startDay = static::normalizeTrainerPaymentWeekDay(
            AppSetting::valueFor('trainer_payment_week_start', $defaultStartDay),
            $defaultStartDay,
        );
        $endDay = static::normalizeTrainerPaymentWeekDay(
            AppSetting::valueFor('trainer_payment_week_end', $defaultEndDay),
            $defaultEndDay,
        );

        return [
            'start_day' => $startDay,
            'end_day' => $endDay,
            'start_label' => self::TRAINER_PAYMENT_WEEK_DAYS[$startDay],
            'end_label' => self::TRAINER_PAYMENT_WEEK_DAYS[$endDay],
        ];
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

        $query = User::query()->with('branches')->orderBy('username');

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
        return [
            static::menuItem('الرئيسية', 'bi-house-door-fill', route('dashboard'), $active === 'dashboard'),
            static::menuItem('الفروع', 'bi-diagram-3-fill', $user->isManager() ? route('branches.index') : null, $active === 'branches'),
            static::menuItem('المستخدمين', 'bi-people-fill', $user->isManager() ? route('users.index') : null, $active === 'users'),
            static::menuItem('صلاحيات المستخدمين', 'bi-shield-lock-fill'),
            static::menuItem('المجموعات', 'bi-collection-fill'),
            static::menuItem('السباحين', 'bi-person-arms-up'),
            static::menuItem('تسكين السباحين', 'bi-diagram-2-fill'),
            static::menuItem('حضور السباحين', 'bi-calendar2-check-fill'),
            static::menuItem('تجديد الاشتراكات', 'bi-arrow-repeat'),
            static::menuItem('تسديد الباقي', 'bi-cash-stack'),
            static::menuItem('المدربين', 'bi-person-workspace', $user->isManager() ? route('trainers.index') : null, $active === 'trainers'),
            static::menuItem('ساعات المدربين', 'bi-clock-history', route('trainer-hours.index'), $active === 'trainer-hours'),
            static::menuItem('سلف المدربين', 'bi-wallet2', route('trainer-advances.index'), $active === 'trainer-advances'),
            static::menuItem('قبض المدربين', 'bi-cash-stack', $user->isManager() ? route('trainer-payrolls.index') : null, $active === 'trainer-payrolls'),
            static::menuItem(
                'بداية أسبوع قبض المدربين',
                'bi-cash-coin',
                $user->isManager() ? route('trainer-payment-week.edit') : null,
                $active === 'trainer-payment-week',
            ),
            static::menuItem('الإداريين', 'bi-people', $user->isManager() ? route('administrators.index') : null, $active === 'administrators'),
            static::menuItem('قبض الإداريين', 'bi-credit-card-2-front-fill', $user->isManager() ? route('administrator-payrolls.index') : null, $active === 'administrator-payrolls'),
            static::menuItem('الأصناف', 'bi-box-seam-fill'),
            static::menuItem('المبيعات', 'bi-cart-check-fill'),
            static::menuItem('المتجر', 'bi-shop'),
            static::menuItem('طلب الكارنية', 'bi-person-vcard-fill'),
            static::menuItem('اشعارات السباح', 'bi-bell-fill'),
            static::menuItem('العروض', 'bi-tags-fill'),
            static::menuItem('اشعارات المدربين', 'bi-megaphone-fill'),
            static::menuItem('الاكاديميات', 'bi-buildings-fill'),
            static::menuItem('سباحين الأكاديميات', 'bi-people-fill'),
            static::menuItem('تقفيل يومي', 'bi-calendar-day-fill'),
            static::menuItem('تقفيل الأسبوعي', 'bi-calendar-week-fill'),
            static::menuItem('تقفيل شهري', 'bi-calendar-month-fill'),
            static::menuItem('الاحصائيات', 'bi-bar-chart-fill'),
            static::menuItem('إعدادات الموقع', 'bi-gear-fill', $user->isManager() ? route('site-settings.edit') : null, $active === 'site-settings'),
            static::menuItem('تسجيل الخروج', 'bi-box-arrow-right', route('logout'), false, 'form'),
        ];
    }

    protected static function sanitizeLogoUrl(?string $path): string
    {
        if (blank($path)) {
            return asset('assets/images/logo.png');
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return asset('assets/images/logo.png');
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return asset($path);
    }

    protected static function normalizeTrainerPaymentWeekDay(?string $day, string $default): string
    {
        return array_key_exists($day ?? '', self::TRAINER_PAYMENT_WEEK_DAYS)
            ? $day
            : $default;
    }

    protected static function menuItem(
        string $title,
        string $icon,
        ?string $route = null,
        bool $active = false,
        string $type = 'link',
    ): array {
        return [
            'title' => $title,
            'icon' => $icon,
            'route' => $route,
            'active' => $active,
            'type' => $type,
            'available' => filled($route) || $type === 'form',
        ];
    }

    public static function roleOptions(): array
    {
        return [
            User::ROLE_MANAGER => 'مدير',
            User::ROLE_SUPERVISOR => 'مشرف',
        ];
    }
}
