<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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

    public function test_manager_can_create_update_and_delete_trainer(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('trainers.store'), [
                'name' => 'محمد',
                'phone' => '01000000000',
                'password' => '123456',
                'hourly_rate' => '150',
                'transfer_number' => '777888',
                'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
            ])
            ->assertRedirect(route('trainers.index'));

        $trainer = Trainer::query()->where('phone', '01000000000')->firstOrFail();

        $this->assertSame('محمد', $trainer->name);
        $this->assertSame($branch->id, $trainer->branch_id);
        $this->assertSame(Trainer::TRANSFER_TYPE_WALLET, $trainer->transfer_type);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('trainers.update', $trainer), [
                'name' => 'محمد أحمد',
                'phone' => '01000000001',
                'password' => '',
                'hourly_rate' => '175',
                'transfer_number' => '999000',
                'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
            ])
            ->assertRedirect(route('trainers.index'));

        $trainer->refresh();

        $this->assertSame('محمد أحمد', $trainer->name);
        $this->assertSame('01000000001', $trainer->phone);
        $this->assertSame('175.00', $trainer->hourly_rate);
        $this->assertSame(Trainer::TRANSFER_TYPE_INSTAPAY, $trainer->transfer_type);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('trainers.destroy', $trainer))
            ->assertRedirect(route('trainers.index'));

        $this->assertDatabaseMissing('trainers', ['id' => $trainer->id]);
    }

    public function test_trainer_page_shows_only_trainers_for_current_branch(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع 2']);

        $trainerOne = Trainer::query()->create([
            'branch_id' => $branchOne->id,
            'name' => 'مدرب الفرع الأول',
            'phone' => '01000000010',
            'password' => '123456',
            'hourly_rate' => '120',
            'transfer_number' => '111',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        $trainerTwo = Trainer::query()->create([
            'branch_id' => $branchTwo->id,
            'name' => 'مدرب الفرع الثاني',
            'phone' => '01000000011',
            'password' => '123456',
            'hourly_rate' => '140',
            'transfer_number' => '222',
            'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('trainers.index'))
            ->assertOk()
            ->assertSee($trainerOne->name)
            ->assertDontSee($trainerTwo->name);
    }

    public function test_branch_scoped_user_sees_only_current_branch_users(): void
    {
        $this->seed();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع 2']);

        $scopedUser = User::factory()->create([
            'username' => 'scoped',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $scopedUser->branches()->sync([$branchOne->id]);

        $otherUser = User::factory()->create([
            'username' => 'other',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $otherUser->branches()->sync([$branchTwo->id]);

        $response = $this->actingAs($scopedUser)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('dashboard'));

        $response->assertOk()->assertSee('scoped')->assertDontSee('other');
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

    public function test_dashboard_shows_requested_control_panel_buttons(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $response = $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('الرئيسية')
            ->assertSee('الفروع')
            ->assertSee('المستخدمين')
            ->assertSee('صلاحيات المستخدمين')
            ->assertSee('السباحين')
            ->assertSee('المدربين')
            ->assertSee(route('trainers.index'))
            ->assertSee('الاحصائيات')
            ->assertDontSee('روابط سريعة')
            ->assertSee('sidebar-nav-link sidebar-nav-button is-disabled', false)
            ->assertSee('تسجيل الخروج');
    }

    public function test_manager_pages_include_home_navigation_link(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $response = $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('users.index'));

        $response->assertOk()
            ->assertSee(route('dashboard'))
            ->assertSee('الرئيسية');
    }

    public function test_manager_can_update_site_settings_with_logo(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $logo = UploadedFile::fake()->image('academy-logo.png', 1200, 1200);

        $this->actingAs($manager)
            ->put(route('site-settings.update'), [
                'site_name' => 'أكاديمية السباحة',
                'site_logo' => $logo,
            ])
            ->assertRedirect(route('site-settings.edit'));

        $this->assertSame('أكاديمية السباحة', AppSetting::valueFor('site_name'));

        $path = AppSetting::valueFor('site_logo');

        $this->assertStringStartsWith('uploads/settings/site-logo-', $path);
        $this->assertFileExists(public_path($path));

        if (File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    public function test_login_page_loads_when_branches_table_is_missing(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('قاعدة البيانات غير مهيأة بعد');
    }

    public function test_login_request_does_not_crash_when_branches_table_is_missing(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');

        $this->from(route('login'))
            ->post(route('login.submit'), [
                'branch_id' => 1,
                'username' => 'magdy',
                'password' => '123456',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username']);
    }
}
