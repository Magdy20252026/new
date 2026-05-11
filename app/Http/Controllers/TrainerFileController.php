<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\TrainerFile;
use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainerFileController extends Controller
{
    protected const IMAGE_EXTENSIONS = [
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

    public function index(Request $request, Trainer $trainer)
    {
        $trainer = $this->scopedTrainer($request, $trainer);

        return $this->trainerFilesView($request, $trainer);
    }

    public function store(Request $request, Trainer $trainer): RedirectResponse
    {
        $trainer = $this->scopedTrainer($request, $trainer);
        $data = $this->validatedPayload($request, true);

        $trainer->trainerFiles()->create([
            'title' => $data['title'],
            'file_path' => $this->storeImage($data['image'], $trainer),
        ]);

        return redirect()
            ->route('trainers.files.index', $trainer)
            ->with('status', 'تم رفع ملف المدرب');
    }

    public function edit(Request $request, Trainer $trainer, TrainerFile $trainerFile)
    {
        $trainer = $this->scopedTrainer($request, $trainer);
        $trainerFile = $this->scopedTrainerFile($trainer, $trainerFile);

        return $this->trainerFilesView($request, $trainer, $trainerFile);
    }

    public function update(Request $request, Trainer $trainer, TrainerFile $trainerFile): RedirectResponse
    {
        $trainer = $this->scopedTrainer($request, $trainer);
        $trainerFile = $this->scopedTrainerFile($trainer, $trainerFile);
        $data = $this->validatedPayload($request, false);

        $payload = ['title' => $data['title']];

        if (isset($data['image'])) {
            $payload['file_path'] = $this->replaceImage($trainerFile, $data['image'], $trainer);
        }

        $trainerFile->update($payload);

        return redirect()
            ->route('trainers.files.index', $trainer)
            ->with('status', 'تم تحديث ملف المدرب');
    }

    public function destroy(Request $request, Trainer $trainer, TrainerFile $trainerFile): RedirectResponse
    {
        $trainer = $this->scopedTrainer($request, $trainer);
        $trainerFile = $this->scopedTrainerFile($trainer, $trainerFile);
        $trainerFile->delete();

        return redirect()
            ->route('trainers.files.index', $trainer)
            ->with('status', 'تم حذف ملف المدرب');
    }

    protected function trainerFilesView(Request $request, Trainer $trainer, ?TrainerFile $editedTrainerFile = null)
    {
        return $this->dashboardView($request, 'trainers.files', [
            'pageTitle' => 'ملفات المدرب',
            'trainer' => $trainer,
            'trainerFiles' => $trainer->trainerFiles()->get(),
            'editedTrainerFile' => $editedTrainerFile,
        ], 'trainers');
    }

    protected function scopedTrainer(Request $request, Trainer $trainer): Trainer
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);

        return Trainer::query()
            ->where('branch_id', $currentBranch->id)
            ->findOrFail($trainer->id);
    }

    protected function scopedTrainerFile(Trainer $trainer, TrainerFile $trainerFile): TrainerFile
    {
        return $trainer->trainerFiles()->findOrFail($trainerFile->id);
    }

    protected function validatedPayload(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => [
                $imageRequired ? 'required' : 'nullable',
                'file',
                'extensions:'.implode(',', self::IMAGE_EXTENSIONS),
                function (string $attribute, UploadedFile $value, \Closure $fail): void {
                    if (! str_starts_with((string) $value->getMimeType(), 'image/')) {
                        $fail('ملف المدرب يجب أن يكون صورة صالحة.');
                    }
                },
            ],
        ], [
            'title.required' => 'اسم الملف مطلوب',
            'image.required' => 'صورة الملف مطلوبة',
        ]);
    }

    protected function storeImage(UploadedFile $uploadedFile, Trainer $trainer): string
    {
        $directory = public_path('uploads/trainers/'.$trainer->id);
        File::ensureDirectoryExists($directory);

        $filename = 'trainer-file-'.Str::uuid().'.'.$this->resolveImageExtension($uploadedFile);
        $uploadedFile->move($directory, $filename);

        return 'uploads/trainers/'.$trainer->id.'/'.$filename;
    }

    protected function replaceImage(TrainerFile $trainerFile, UploadedFile $uploadedFile, Trainer $trainer): string
    {
        $path = $this->storeImage($uploadedFile, $trainer);

        if (
            $this->isManagedImagePath($trainerFile->file_path, $trainer)
            && $trainerFile->file_path !== $path
            && File::exists(public_path($trainerFile->file_path))
        ) {
            File::delete(public_path($trainerFile->file_path));
        }

        return $path;
    }

    protected function isManagedImagePath(?string $path, Trainer $trainer): bool
    {
        return filled($path) && str_starts_with($path, 'uploads/trainers/'.$trainer->id.'/');
    }

    protected function resolveImageExtension(UploadedFile $uploadedFile): string
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return $extension;
        }

        throw ValidationException::withMessages([
            'image' => 'امتداد ملف المدرب غير مدعوم.',
        ]);
    }
}
