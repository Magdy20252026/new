<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        $siteSettings = [
            'site_name' => 'Swim Academy',
            'site_logo' => asset('assets/images/logo.png'),
        ];

        $branches = [
            ['id' => 1, 'name' => 'الفرع الرئيسي'],
            ['id' => 2, 'name' => 'فرع التجمع'],
            ['id' => 3, 'name' => 'فرع أكتوبر'],
        ];

        return view('auth.login', compact('siteSettings', 'branches'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'branch_id' => ['required'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'branch_id.required' => 'الفرع مطلوب',
            'username.required' => 'اسم المستخدم مطلوب',
            'password.required' => 'كلمة السر مطلوبة',
        ]);

        return back();
    }
}