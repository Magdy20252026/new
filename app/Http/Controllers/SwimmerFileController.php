<?php

namespace App\Http\Controllers;

use App\Models\Swimmer;
use App\Models\SwimmerFile;
use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SwimmerFileController extends Controller
{
    public function index(Request $request, Swimmer $swimmer)
    {
        $swimmer = $this->scopedSwimmer($request, $swimmer);

        return $this->swimmerFilesView($request, $swimmer);
    }

    public function store(Request $request, Swimmer $swimmer): RedirectResponse
    {
        $swimmer = $this->scopedSwimmer($request, $swimmer);
        $data = $this->validatedStorePayload($request);
        $images = Collection::wrap($data['images']);

        $images->values()->each(function (UploadedFile $image, int $index) use ($swimmer, $data, $images): void {
            $swimmer->swimmerFiles()->create([
                'type' => $data['type'],
                'title' => $images->count() > 1 ? $data['title'].' '.($index + 1) : $data['title'],
                'file_path' => $this->storeImage($image, $swimmer),
            ]);
        });

        return redirect()
            ->route('swimmers.files.index', $swimmer)
            ->with('status', 'تم رفع ملفات السباح');
    }

    public function edit(Request $request, Swimmer $swimmer, SwimmerFile $swimmerFile)
    {
        $swimmer = $this->scopedSwimmer($request, $swimmer);
        $swimmerFile = $this->scopedSwimmerFile($swimmer, $swimmerFile);

        return $this->swimmerFilesView($request, $swimmer, $swimmerFile);
    }

    public function update(Request $request, Swimmer $swimmer, SwimmerFile $swimmerFile): RedirectResponse
    {
        $swimmer = $this->scopedSwimmer($request, $swimmer);
        $swimmerFile = $this->scopedSwimmerFile($swimmer, $swimmerFile);
        $data = $this->validatedUpdatePayload($request);

        $payload = [
            'type' => $data['type'],
            'title' => $data['title'],
        ];

        if (isset($data['image'])) {
            $payload['file_path'] = $this->replaceImage($swimmerFile, $data['image'], $swimmer);
        }

        $swimmerFile->update($payload);

        return redirect()
            ->route('swimmers.files.index', $swimmer)
            ->with('status', 'تم تحديث ملف السباح');
    }

    public function destroy(Request $request, Swimmer $swimmer, SwimmerFile $swimmerFile): RedirectResponse
    {
        $swimmer = $this->scopedSwimmer($request, $swimmer);
        $swimmerFile = $this->scopedSwimmerFile($swimmer, $swimmerFile);
        $swimmerFile->delete();

        return redirect()
            ->route('swimmers.files.index', $swimmer)
            ->with('status', 'تم حذف ملف السباح');
    }

    protected function swimmerFilesView(Request $request, Swimmer $swimmer, ?SwimmerFile $editedSwimmerFile = null)
    {
        $swimmerFiles = $swimmer->swimmerFiles()->get();

        return $this->dashboardView($request, 'swimmers.files', [
            'pageTitle' => 'ملفات السباح',
            'swimmer' => $swimmer,
            'swimmerFiles' => $swimmerFiles,
            'editedSwimmerFile' => $editedSwimmerFile,
            'fileTypeLabels' => SwimmerFile::typeOptions(),
            'groupedSwimmerFiles' => collect(SwimmerFile::typeOptions())
                ->mapWithKeys(fn (string $label, string $type) => [$type => $swimmerFiles->where('type', $type)->values()]),
        ], 'swimmers');
    }

    protected function scopedSwimmer(Request $request, Swimmer $swimmer): Swimmer
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);

        return Swimmer::query()
            ->where('branch_id', $currentBranch->id)
            ->findOrFail($swimmer->id);
    }

    protected function scopedSwimmerFile(Swimmer $swimmer, SwimmerFile $swimmerFile): SwimmerFile
    {
        return $swimmer->swimmerFiles()->findOrFail($swimmerFile->id);
    }

    protected function validatedStorePayload(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(SwimmerFile::typeOptions()))],
            'title' => ['required', 'string', 'max:255'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'file', function (string $attribute, UploadedFile $value, \Closure $fail): void {
                if (! str_starts_with((string) $value->getMimeType(), 'image/')) {
                    $fail('ملفات السباح يجب أن تكون صورًا صالحة.');
                }
            }],
        ], [
            'type.required' => 'نوع الملف مطلوب',
            'title.required' => 'اسم الملف مطلوب',
            'images.required' => 'يجب اختيار ملف واحد على الأقل',
        ]);

        if ($validated['type'] !== SwimmerFile::TYPE_MEDICAL_REPORT && count($validated['images']) > 1) {
            throw ValidationException::withMessages([
                'images' => 'يمكن رفع أكثر من صورة فقط للتقرير الطبي.',
            ]);
        }

        return $validated;
    }

    protected function validatedUpdatePayload(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(SwimmerFile::typeOptions()))],
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'file', function (string $attribute, UploadedFile $value, \Closure $fail): void {
                if (! str_starts_with((string) $value->getMimeType(), 'image/')) {
                    $fail('ملف السباح يجب أن يكون صورة صالحة.');
                }
            }],
        ], [
            'type.required' => 'نوع الملف مطلوب',
            'title.required' => 'اسم الملف مطلوب',
        ]);
    }

    protected function storeImage(UploadedFile $uploadedFile, Swimmer $swimmer): string
    {
        $directory = public_path('uploads/swimmers/'.$swimmer->id);
        File::ensureDirectoryExists($directory);

        $filename = 'swimmer-file-'.Str::uuid().'.'.strtolower($uploadedFile->getClientOriginalExtension() ?: 'img');
        $uploadedFile->move($directory, $filename);

        return 'uploads/swimmers/'.$swimmer->id.'/'.$filename;
    }

    protected function replaceImage(SwimmerFile $swimmerFile, UploadedFile $uploadedFile, Swimmer $swimmer): string
    {
        $path = $this->storeImage($uploadedFile, $swimmer);

        if (
            $this->isManagedImagePath($swimmerFile->file_path, $swimmer)
            && $swimmerFile->file_path !== $path
            && File::exists(public_path($swimmerFile->file_path))
        ) {
            File::delete(public_path($swimmerFile->file_path));
        }

        return $path;
    }

    protected function isManagedImagePath(?string $path, Swimmer $swimmer): bool
    {
        return filled($path) && str_starts_with($path, 'uploads/swimmers/'.$swimmer->id.'/');
    }
}
