<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $siteSettings = [
            'site_name' => 'Swim Academy',
            'site_logo' => asset('assets/images/logo.png'),
        ];

        $user = [
            'name' => 'Magdy',
            'role' => 'مدير النظام',
        ];

        $menuItems = [
            'الفروع',
            'المستخدمين',
            'صلاحيات المستخدمين',
            'المجموعات',
            'السباحين',
            'تسكين السباحين',
            'حضور السباحين',
            'تجديد الاشتراكات',
            'تسديد الباقي',
            'المدربين',
            'ساعات المدربين',
            'سلف المدربين',
            'قبض المدربين',
            'الإداريين',
            'قبض الإداريين',
            'الأصناف',
            'المبيعات',
            'المتجر',
            'طلب الكارنية',
            'اشعارات السباح',
            'العروض',
            'اشعارات المدربين',
            'الاكاديميات',
            'سباحين الأكاديميات',
            'تقفيل يومي',
            'تقفيل الأسبوعي',
            'تقفيل شهري',
            'الاحصائيات',
            'إعدادات الموقع',
            'تسجيل الخروج',
        ];

        return view('dashboard.index', compact('siteSettings', 'user', 'menuItems'));
    }
}