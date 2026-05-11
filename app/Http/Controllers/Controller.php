<?php

namespace App\Http\Controllers;

use App\Support\ControlPanel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function dashboardView(Request $request, string $view, array $data = [], string $active = 'dashboard')
    {
        $user = $request->user();
        $currentBranch = ControlPanel::currentBranch($user);

        return view($view, array_merge($data, [
            'siteSettings' => ControlPanel::siteSettings(),
            'themeMode' => ControlPanel::themeMode(),
            'menuItems' => ControlPanel::menuItems($active, $user),
            'currentBranch' => $currentBranch,
            'accessibleBranches' => ControlPanel::accessibleBranches($user),
            'authUser' => $user,
        ]));
    }
}
