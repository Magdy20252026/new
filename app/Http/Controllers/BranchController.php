<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = ControlPanel::accessibleBranchesQuery($request->user())
            ->withCount('users')
            ->get();

        return $this->dashboardView($request, 'branches.index', [
            'pageTitle' => 'الفروع',
            'branches' => $branches,
        ], 'branches');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')],
        ], [
            'name.required' => 'اسم الفرع مطلوب',
            'name.unique' => 'اسم الفرع مسجل بالفعل',
        ]);

        $branch = Branch::query()->create($data);

        if (! session('current_branch_id')) {
            session(['current_branch_id' => $branch->id]);
        }

        return redirect()->route('branches.index')->with('status', 'تم إضافة الفرع');
    }

    public function edit(Request $request, Branch $branch)
    {
        $branch = ControlPanel::accessibleBranchesQuery($request->user())->findOrFail($branch->id);

        return $this->dashboardView($request, 'branches.edit', [
            'pageTitle' => 'تعديل الفرع',
            'branch' => $branch,
        ], 'branches');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch = ControlPanel::accessibleBranchesQuery($request->user())->findOrFail($branch->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($branch->id)],
        ], [
            'name.required' => 'اسم الفرع مطلوب',
            'name.unique' => 'اسم الفرع مسجل بالفعل',
        ]);

        $branch->update($data);

        return redirect()->route('branches.index')->with('status', 'تم تحديث الفرع');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $branch = ControlPanel::accessibleBranchesQuery($request->user())->findOrFail($branch->id);

        if (Branch::query()->count() === 1) {
            return redirect()->route('branches.index')->withErrors(['name' => 'يجب بقاء فرع واحد على الأقل']);
        }

        if ($branch->users()->where('access_all_branches', false)->exists()) {
            return redirect()->route('branches.index')->withErrors(['name' => 'لا يمكن حذف فرع مرتبط بمستخدمين']);
        }

        $branch->users()->detach();
        $branch->delete();

        if ((int) session('current_branch_id') === $branch->id) {
            session(['current_branch_id' => ControlPanel::accessibleBranchesQuery($request->user())->value('id')]);
        }

        return redirect()->route('branches.index')->with('status', 'تم حذف الفرع');
    }
}
