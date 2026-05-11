<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentBranch = ControlPanel::currentBranch($request->user());

        $users = ControlPanel::visibleUsersQuery($request->user(), $currentBranch)
            ->get();

        return $this->dashboardView($request, 'users.index', [
            'pageTitle' => 'المستخدمين',
            'users' => $users,
            'branches' => ControlPanel::accessibleBranches($request->user()),
            'roles' => ControlPanel::roleOptions(),
        ], 'users');
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $branchIds] = $this->validatedPayload($request);

        $user = User::query()->create($data);
        $user->branches()->sync($data['access_all_branches'] ? [] : $branchIds);

        return redirect()->route('users.index')->with('status', 'تم إضافة المستخدم');
    }

    public function edit(Request $request, User $user)
    {
        $currentBranch = ControlPanel::currentBranch($request->user());

        $user = ControlPanel::visibleUsersQuery($request->user(), $currentBranch)->findOrFail($user->id);

        return $this->dashboardView($request, 'users.edit', [
            'pageTitle' => 'تعديل المستخدم',
            'editedUser' => $user,
            'branches' => ControlPanel::accessibleBranches($request->user()),
            'roles' => ControlPanel::roleOptions(),
        ], 'users');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        $editedUser = ControlPanel::visibleUsersQuery($request->user(), $currentBranch)->findOrFail($user->id);
        [$data, $branchIds] = $this->validatedPayload($request, $editedUser);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $editedUser->update($data);
        $editedUser->branches()->sync($data['access_all_branches'] ? [] : $branchIds);

        if ($editedUser->is($request->user()) && ! $editedUser->access_all_branches && ! $editedUser->canAccessBranch((int) session('current_branch_id'))) {
            session(['current_branch_id' => $editedUser->branches()->value('branches.id')]);
        }

        return redirect()->route('users.index')->with('status', 'تم تحديث المستخدم');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        $deletedUser = ControlPanel::visibleUsersQuery($request->user(), $currentBranch)->findOrFail($user->id);

        if ($deletedUser->is($request->user())) {
            return redirect()->route('users.index')->withErrors(['username' => 'لا يمكن حذف المستخدم الحالي']);
        }

        $deletedUser->branches()->detach();
        $deletedUser->delete();

        return redirect()->route('users.index')->with('status', 'تم حذف المستخدم');
    }

    protected function validatedPayload(Request $request, ?User $user = null): array
    {
        $branchIds = ControlPanel::accessibleBranches($request->user())->pluck('id')->all();
        $passwordRules = $user ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'];

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'password' => $passwordRules,
            'role' => ['required', Rule::in(array_keys(ControlPanel::roleOptions()))],
            'scope' => ['required', Rule::in(['all', 'selected'])],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds)],
        ], [
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم مسجل بالفعل',
            'password.required' => 'كلمة السر مطلوبة',
            'password.min' => 'كلمة السر يجب أن تكون 6 أحرف على الأقل',
            'role.required' => 'الصلاحية مطلوبة',
            'scope.required' => 'تحديد الفروع مطلوب',
        ]);

        $selectedBranches = array_values(array_unique(array_map('intval', $data['branch_ids'] ?? [])));

        if ($data['scope'] === 'selected' && $selectedBranches === []) {
            throw ValidationException::withMessages([
                'branch_ids' => 'اختر فرعًا واحدًا على الأقل',
            ]);
        }

        return [[
            'username' => $data['username'],
            'password' => $data['password'] ?? '',
            'role' => $data['role'],
            'access_all_branches' => $data['scope'] === 'all',
        ], $selectedBranches];
    }
}
