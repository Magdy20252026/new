<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $siteSettings = [
            'site_name' => 'أكاديمية السباحة',
            'site_logo' => asset('assets/images/logo.png'),
        ];

        $user = [
            'name' => 'Magdy',
            'role' => 'مدير النظام',
        ];

        $currentBranch = [
            'name' => 'الفرع الرئيسي',
        ];

        $stats = [
            ['label' => 'إجمالي السباحين', 'value' => '0', 'icon' => 'bi-people-fill'],
            ['label' => 'إجمالي المدربين', 'value' => '0', 'icon' => 'bi-person-badge-fill'],
            ['label' => 'إجمالي الإداريين', 'value' => '0', 'icon' => 'bi-person-workspace'],
        ];

        $menuItems = [
            ['title' => 'الفروع', 'icon' => 'bi-diagram-3-fill', 'route' => '#', 'active' => false],
            ['title' => 'المستخدمين', 'icon' => 'bi-people-fill', 'route' => '#', 'active' => false],
            ['title' => 'صلاحيات المستخدمين', 'icon' => 'bi-shield-lock-fill', 'route' => '#', 'active' => false],
            ['title' => 'المجموعات', 'icon' => 'bi-collection-fill', 'route' => '#', 'active' => false],
            ['title' => 'السباحين', 'icon' => 'bi-person-hearts', 'route' => '#', 'active' => false],
            ['title' => 'تسكين السباحين', 'icon' => 'bi-grid-fill', 'route' => '#', 'active' => false],
            ['title' => 'حضور السباحين', 'icon' => 'bi-calendar-check-fill', 'route' => '#', 'active' => false],
            ['title' => 'تجديد الاشتراكات', 'icon' => 'bi-arrow-repeat', 'route' => '#', 'active' => false],
            ['title' => 'تسديد الباقي', 'icon' => 'bi-wallet2', 'route' => '#', 'active' => false],
            ['title' => 'المدربين', 'icon' => 'bi-person-badge-fill', 'route' => '#', 'active' => false],
            ['title' => 'ساعات المدربين', 'icon' => 'bi-clock-history', 'route' => '#', 'active' => false],
            ['title' => 'سلف المدربين', 'icon' => 'bi-cash-coin', 'route' => '#', 'active' => false],
            ['title' => 'قبض المدربين', 'icon' => 'bi-currency-dollar', 'route' => '#', 'active' => false],
            ['title' => 'الإداريين', 'icon' => 'bi-person-workspace', 'route' => '#', 'active' => false],
            ['title' => 'قبض الإداريين', 'icon' => 'bi-currency-exchange', 'route' => '#', 'active' => false],
            ['title' => 'الأصناف', 'icon' => 'bi-box-seam-fill', 'route' => '#', 'active' => false],
            ['title' => 'المبيعات', 'icon' => 'bi-bag-check-fill', 'route' => '#', 'active' => false],
            ['title' => 'المتجر', 'icon' => 'bi-shop', 'route' => '#', 'active' => false],
            ['title' => 'طلب الكارنية', 'icon' => 'bi-card-heading', 'route' => '#', 'active' => false],
            ['title' => 'اشعارات السباح', 'icon' => 'bi-bell-fill', 'route' => '#', 'active' => false],
            ['title' => 'العروض', 'icon' => 'bi-megaphone-fill', 'route' => '#', 'active' => false],
            ['title' => 'اشعارات المدربين', 'icon' => 'bi-broadcast-pin', 'route' => '#', 'active' => false],
            ['title' => 'الاكاديميات', 'icon' => 'bi-buildings-fill', 'route' => '#', 'active' => false],
            ['title' => 'سباحين الأكاديميات', 'icon' => 'bi-people', 'route' => '#', 'active' => false],
            ['title' => 'تقفيل يومي', 'icon' => 'bi-calendar-day-fill', 'route' => '#', 'active' => false],
            ['title' => 'تقفيل الأسبوعي', 'icon' => 'bi-calendar-week-fill', 'route' => '#', 'active' => false],
            ['title' => 'تقفيل شهري', 'icon' => 'bi-calendar-month-fill', 'route' => '#', 'active' => false],
            ['title' => 'الاحصائيات', 'icon' => 'bi-bar-chart-line-fill', 'route' => '#', 'active' => true],
            ['title' => 'إعدادات الموقع', 'icon' => 'bi-gear-fill', 'route' => '#', 'active' => false],
            ['title' => 'تسجيل الخروج', 'icon' => 'bi-box-arrow-right', 'route' => '#', 'active' => false],
        ];

        return view('dashboard.index', compact(
            'siteSettings',
            'user',
            'currentBranch',
            'stats',
            'menuItems'
        ));
    }
}