<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'siteSettings' => ControlPanel::siteSettings(),
            'themeMode' => ControlPanel::themeMode(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'branch_id.required' => 'الفرع مطلوب',
            'branch_id.exists' => 'الفرع غير متاح',
            'username.required' => 'اسم المستخدم مطلوب',
            'password.required' => 'كلمة السر مطلوبة',
        ]);

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('branch_id', 'username'))
                ->withErrors(['username' => 'بيانات الدخول غير صحيحة']);
        }

        $request->session()->regenerate();

        $branch = Branch::query()->findOrFail($credentials['branch_id']);
        $user = $request->user();

        if (! $user->canAccessBranch($branch)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('branch_id', 'username'))
                ->withErrors(['branch_id' => 'هذا المستخدم لا يمكنه فتح الفرع المحدد']);
        }

        session(['current_branch_id' => $branch->id]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
