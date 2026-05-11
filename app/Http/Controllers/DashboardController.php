<?php

namespace App\Http\Controllers;

use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currentBranch = ControlPanel::currentBranch($user);
        abort_unless($currentBranch, 403);

        $visibleUsers = ControlPanel::visibleUsersQuery($user, $currentBranch)->get();

        $stats = [
            ['label' => 'الفرع الحالي', 'value' => $currentBranch->name, 'icon' => 'bi-building'],
            ['label' => 'المستخدمون', 'value' => (string) $visibleUsers->count(), 'icon' => 'bi-people-fill'],
            ['label' => 'المديرون', 'value' => (string) $visibleUsers->where('role', 'manager')->count(), 'icon' => 'bi-shield-fill-check'],
            ['label' => 'المشرفون', 'value' => (string) $visibleUsers->where('role', 'supervisor')->count(), 'icon' => 'bi-person-badge-fill'],
        ];

        return $this->dashboardView($request, 'dashboard.index', [
            'pageTitle' => 'الرئيسية',
            'stats' => $stats,
        ]);
    }

    public function switchBranch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        abort_unless($request->user()->canAccessBranch((int) $data['branch_id']), 403);

        session(['current_branch_id' => (int) $data['branch_id']]);

        return back();
    }
}
