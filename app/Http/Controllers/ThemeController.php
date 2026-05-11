<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Support\ControlPanel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string'],
        ]);

        $theme = ControlPanel::normalizeTheme($data['theme']);
        AppSetting::putValue('theme_mode', $theme);

        return response()->json(['theme' => $theme]);
    }
}
