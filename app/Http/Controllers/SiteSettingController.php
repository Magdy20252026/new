<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiteSettingController extends Controller
{
    protected const LOGO_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp',
        'svg',
        'webp',
        'avif',
        'tiff',
        'tif',
        'ico',
        'heic',
        'heif',
    ];

    public function edit(Request $request)
    {
        return $this->dashboardView($request, 'settings.edit', [
            'pageTitle' => 'إعدادات الموقع',
        ], 'site-settings');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_logo' => [
                'nullable',
                'file',
                'extensions:'.implode(',', self::LOGO_EXTENSIONS),
                function (string $attribute, UploadedFile $value, \Closure $fail): void {
                    if (! str_starts_with((string) $value->getMimeType(), 'image/')) {
                        $fail('ملف الشعار يجب أن يكون صورة صالحة.');
                    }
                },
            ],
        ], [
            'site_name.required' => 'اسم الأكاديمية مطلوب',
        ]);

        AppSetting::putValue('site_name', $data['site_name']);

        if ($request->hasFile('site_logo')) {
            AppSetting::putValue('site_logo', $this->storeLogo($request->file('site_logo')));
        }

        return redirect()
            ->route('site-settings.edit')
            ->with('status', 'تم حفظ إعدادات الموقع');
    }

    protected function storeLogo(UploadedFile $uploadedFile): string
    {
        $directory = public_path('uploads/settings');
        File::ensureDirectoryExists($directory);

        $filename = 'site-logo-'.Str::uuid().'.'.$this->resolveLogoExtension($uploadedFile);
        $uploadedFile->move($directory, $filename);

        $path = 'uploads/settings/'.$filename;
        $currentPath = AppSetting::valueFor('site_logo');

        if (
            $this->isManagedLogoPath($currentPath)
            && $currentPath !== $path
            && File::exists(public_path($currentPath))
        ) {
            File::delete(public_path($currentPath));
        }

        return $path;
    }

    protected function isManagedLogoPath(?string $path): bool
    {
        return filled($path) && str_starts_with($path, 'uploads/settings/');
    }

    protected function resolveLogoExtension(UploadedFile $uploadedFile): string
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (in_array($extension, self::LOGO_EXTENSIONS, true)) {
            return $extension;
        }

        throw ValidationException::withMessages([
            'site_logo' => 'امتداد ملف الشعار غير مدعوم.',
        ]);
    }
}
