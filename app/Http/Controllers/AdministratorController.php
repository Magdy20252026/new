<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Branch;
use App\Support\ControlPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdministratorController extends Controller
{
    public function index(Request $request)
    {
        return $this->administratorsView($request);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($setupError = $this->administratorsSetupError()) {
            return back()->withErrors(['name' => $setupError]);
        }

        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $data = $this->validatedPayload($request, $currentBranch);

        Administrator::query()->create($data);

        return redirect()->route('administrators.index')->with('status', 'تم إضافة الإداري');
    }

    public function edit(Request $request, Administrator $administrator)
    {
        $administrator = $this->scopedAdministratorsQuery($request)->findOrFail($administrator->id);

        return $this->administratorsView($request, $administrator);
    }

    public function update(Request $request, Administrator $administrator): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $administrator = $this->scopedAdministratorsQuery($request)->findOrFail($administrator->id);
        $data = $this->validatedPayload($request, $currentBranch, $administrator);

        $administrator->update($data);

        return redirect()->route('administrators.index')->with('status', 'تم تحديث الإداري');
    }

    public function destroy(Request $request, Administrator $administrator): RedirectResponse
    {
        $administrator = $this->scopedAdministratorsQuery($request)->findOrFail($administrator->id);
        $administrator->delete();

        return redirect()->route('administrators.index')->with('status', 'تم حذف الإداري');
    }

    protected function administratorsView(Request $request, ?Administrator $editedAdministrator = null)
    {
        $setupError = $this->administratorsSetupError();

        return $this->dashboardView($request, 'administrators.index', [
            'pageTitle' => 'الإداريين',
            'setupError' => $setupError,
            'administrators' => $setupError ? collect() : $this->scopedAdministratorsQuery($request)->get(),
            'editedAdministrator' => $setupError ? null : $editedAdministrator,
        ], 'administrators');
    }

    protected function scopedAdministratorsQuery(Request $request): Builder
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        $query = Administrator::query()->orderBy('name');

        if (! $currentBranch) {
            return $query->whereNull('id');
        }

        return $query->where('branch_id', $currentBranch->id);
    }

    protected function validatedPayload(Request $request, Branch $branch, ?Administrator $administrator = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('administrators', 'phone')->ignore($administrator?->id)],
            'job_title' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'اسم الإداري مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'job_title.required' => 'الوظيفة مطلوبة',
            'salary.required' => 'الراتب مطلوب',
            'salary.numeric' => 'الراتب غير صحيح',
        ]) + ['branch_id' => $branch->id];
    }

    protected function administratorsSetupError(): ?string
    {
        if (! Schema::hasTable('administrators')) {
            return 'جدول الإداريين غير مهيأ بعد. شغل php artisan migrate ثم أعد تحميل الصفحة.';
        }

        return null;
    }
}
