<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_seeded_manager_can_login_with_selected_branch(): void
    {
        $this->seed();
        $branch = Branch::query()->firstOrFail();

        $response = $this->post('/login', [
            'branch_id' => $branch->id,
            'username' => 'magdy',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame($branch->id, session('current_branch_id'));
    }

    public function test_manager_can_create_branch_and_user(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $currentBranch = Branch::query()->firstOrFail();

        $this->actingAs($manager)
            ->post(route('branches.store'), ['name' => 'فرع جديد'])
            ->assertRedirect(route('branches.index'));

        $branch = Branch::query()->where('name', 'فرع جديد')->firstOrFail();

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $currentBranch->id])
            ->post(route('users.store'), [
                'name' => 'Supervisor',
                'username' => 'supervisor',
                'password' => '123456',
                'role' => User::ROLE_SUPERVISOR,
                'scope' => 'selected',
                'branch_ids' => [$branch->id],
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('username', 'supervisor')->firstOrFail();

        $this->assertFalse($user->access_all_branches);
        $this->assertEquals([$branch->id], $user->branches()->pluck('branches.id')->all());
    }

    public function test_branch_scoped_user_sees_only_current_branch_users(): void
    {
        $this->seed();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع 2']);

        $scopedUser = User::factory()->create([
            'name' => 'Scoped User',
            'username' => 'scoped',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $scopedUser->branches()->sync([$branchOne->id]);

        $otherUser = User::factory()->create([
            'name' => 'Other User',
            'username' => 'other',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $otherUser->branches()->sync([$branchTwo->id]);

        $response = $this->actingAs($scopedUser)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('dashboard'));

        $response->assertOk()->assertSee('Scoped User')->assertDontSee('Other User');
    }

    public function test_authenticated_user_can_update_theme(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();

        $this->actingAs($manager)
            ->postJson(route('theme.update'), ['theme' => 'dark'])
            ->assertOk()
            ->assertJson(['theme' => 'dark']);

        $this->assertSame('dark', AppSetting::valueFor('theme_mode'));
    }
}
