<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Trainer;
use App\Models\TrainerAdvance;
use App\Models\TrainerFile;
use App\Models\TrainerHour;
use App\Models\TrainerPayroll;
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

    public function test_manager_can_manage_trainer_files(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب الصور',
            'phone' => '01000000022',
            'password' => '123456',
            'hourly_rate' => '150',
            'transfer_number' => '555666',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        $imageOne = UploadedFile::fake()->image('trainer-file-one.png', 1200, 1200);
        $imageTwo = UploadedFile::fake()->image('trainer-file-two.png', 1200, 1200);

        $response = $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainers.index'));

        $response->assertOk()
            ->assertSee(route('trainers.files.index', $trainer))
            ->assertSee('ملفات المدرب');

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('trainers.files.store', $trainer), [
                'title' => 'البطاقة الشخصية',
                'image' => $imageOne,
            ])
            ->assertRedirect(route('trainers.files.index', $trainer));

        $trainerFile = TrainerFile::query()->where('title', 'البطاقة الشخصية')->firstOrFail();

        $this->assertSame($trainer->id, $trainerFile->trainer_id);
        $this->assertStringStartsWith('uploads/trainers/'.$trainer->id.'/trainer-file-', $trainerFile->file_path);
        $this->assertFileExists(public_path($trainerFile->file_path));

        $oldPath = $trainerFile->file_path;

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('trainers.files.update', [$trainer, $trainerFile]), [
                'title' => 'صورة البطاقة',
                'image' => $imageTwo,
            ])
            ->assertRedirect(route('trainers.files.index', $trainer));

        $trainerFile->refresh();

        $this->assertSame('صورة البطاقة', $trainerFile->title);
        $this->assertNotSame($oldPath, $trainerFile->file_path);
        $this->assertFileDoesNotExist(public_path($oldPath));
        $this->assertFileExists(public_path($trainerFile->file_path));

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainers.files.index', $trainer))
            ->assertOk()
            ->assertSee('صورة البطاقة')
            ->assertSee($trainerFile->file_path);

        $currentPath = $trainerFile->file_path;

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('trainers.files.destroy', [$trainer, $trainerFile]))
            ->assertRedirect(route('trainers.files.index', $trainer));

        $this->assertDatabaseMissing('trainer_files', ['id' => $trainerFile->id]);
        $this->assertFileDoesNotExist(public_path($currentPath));

        File::deleteDirectory(public_path('uploads/trainers'));
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
            ->assertSee(route('trainer-hours.index'))
            ->assertSee(route('trainer-advances.index'))
            ->assertSee(route('trainer-payrolls.index'))
            ->assertSee(route('trainer-payment-week.edit'))
            ->assertSee('قبض المدربين')
            ->assertSee('بداية أسبوع قبض المدربين')
            ->assertSee(route('trainers.index'))
            ->assertSee('الاحصائيات')
            ->assertDontSee('إعدادات سريعة')
            ->assertSee('تسجيل الخروج');
    }

    public function test_manager_can_update_trainer_payment_week_settings(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('trainer-payment-week.update'), [
                'trainer_payment_week_start' => 'saturday',
                'trainer_payment_week_end' => 'thursday',
            ])
            ->assertRedirect(route('trainer-payment-week.edit'));

        $this->assertSame('saturday', AppSetting::valueFor('trainer_payment_week_start'));
        $this->assertSame('thursday', AppSetting::valueFor('trainer_payment_week_end'));

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainer-payment-week.edit'))
            ->assertOk()
            ->assertSee('بداية أسبوع قبض المدربين')
            ->assertSee('يوم بداية القبض')
            ->assertSee('يوم نهاية القبض')
            ->assertDontSee('الفترة الحالية');
    }

    public function test_manager_can_manage_trainer_hours_with_attendance_and_absence_by_date(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $presentTrainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب حاضر',
            'phone' => '01000000030',
            'password' => '123456',
            'hourly_rate' => '150',
            'transfer_number' => '123',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        $absentTrainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب غائب',
            'phone' => '01000000031',
            'password' => '123456',
            'hourly_rate' => '175',
            'transfer_number' => '456',
            'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('trainer-hours.store'), [
                'trainer_id' => $presentTrainer->id,
                'worked_on' => '2026-05-11',
                'hours' => '4',
            ])
            ->assertRedirect(route('trainer-hours.index', ['date' => '2026-05-11']));

        $trainerHour = TrainerHour::query()->where('trainer_id', $presentTrainer->id)->firstOrFail();

        $this->assertSame('4.00', $trainerHour->hours);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainer-hours.index', ['date' => '2026-05-11']))
            ->assertOk()
            ->assertSee($presentTrainer->name)
            ->assertSee($absentTrainer->name)
            ->assertSee('إجمالي الراتب')
            ->assertSee('إجمالي الرواتب')
            ->assertSee('جدول الحضور')
            ->assertSee('جدول الغياب');

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('trainer-hours.update', $trainerHour), [
                'trainer_id' => $presentTrainer->id,
                'worked_on' => '2026-05-11',
                'hours' => '5.5',
            ])
            ->assertRedirect(route('trainer-hours.index', ['date' => '2026-05-11']));

        $trainerHour->refresh();

        $this->assertSame('5.50', $trainerHour->hours);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('trainer-hours.destroy', $trainerHour), ['date' => '2026-05-11'])
            ->assertRedirect(route('trainer-hours.index', ['date' => '2026-05-11']));

        $this->assertDatabaseMissing('trainer_hours', ['id' => $trainerHour->id]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainer-hours.index', ['date' => '2026-05-11']))
            ->assertOk()
            ->assertSee($presentTrainer->name)
            ->assertSee($absentTrainer->name)
            ->assertSee('لا توجد سجلات');
    }

    public function test_supervisor_can_access_trainer_hours_without_salary_details(): void
    {
        $this->seed();
        $branch = Branch::query()->firstOrFail();

        $supervisor = User::factory()->create([
            'username' => 'trainer-hours-supervisor',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $supervisor->branches()->sync([$branch->id]);

        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب المشرف',
            'phone' => '01000000032',
            'password' => '123456',
            'hourly_rate' => '130',
            'transfer_number' => '789',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        TrainerHour::query()->create([
            'trainer_id' => $trainer->id,
            'worked_on' => '2026-05-12',
            'hours' => '3',
        ]);

        $this->actingAs($supervisor)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainer-hours.index', ['date' => '2026-05-12']))
            ->assertOk()
            ->assertSee($trainer->name)
            ->assertDontSee('إجمالي الراتب')
            ->assertDontSee('إجمالي الرواتب');
    }

    public function test_manager_can_manage_trainer_advances_by_date_and_branch(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع السلف الثاني']);

        $branchTrainer = Trainer::query()->create([
            'branch_id' => $branchOne->id,
            'name' => 'مدرب السلفة',
            'phone' => '01000000040',
            'password' => '123456',
            'hourly_rate' => '150',
            'transfer_number' => '135',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        $otherBranchTrainer = Trainer::query()->create([
            'branch_id' => $branchTwo->id,
            'name' => 'مدرب فرع آخر',
            'phone' => '01000000041',
            'password' => '123456',
            'hourly_rate' => '165',
            'transfer_number' => '246',
            'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->post(route('trainer-advances.store'), [
                'trainer_id' => $branchTrainer->id,
                'advanced_on' => '2026-05-12',
                'amount' => '250',
            ])
            ->assertRedirect(route('trainer-advances.index', ['date' => '2026-05-12']));

        $trainerAdvance = TrainerAdvance::query()->where('trainer_id', $branchTrainer->id)->firstOrFail();

        $this->assertSame('250.00', $trainerAdvance->amount);

        TrainerAdvance::query()->create([
            'trainer_id' => $otherBranchTrainer->id,
            'advanced_on' => '2026-05-12',
            'amount' => '375',
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('trainer-advances.index', ['date' => '2026-05-12']))
            ->assertOk()
            ->assertSee('جدول سلف المدربين')
            ->assertSee($branchTrainer->name)
            ->assertDontSee($otherBranchTrainer->name)
            ->assertSee('250');

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->put(route('trainer-advances.update', $trainerAdvance), [
                'trainer_id' => $branchTrainer->id,
                'advanced_on' => '2026-05-12',
                'amount' => '300',
            ])
            ->assertRedirect(route('trainer-advances.index', ['date' => '2026-05-12']));

        $trainerAdvance->refresh();

        $this->assertSame('300.00', $trainerAdvance->amount);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->delete(route('trainer-advances.destroy', $trainerAdvance), ['date' => '2026-05-12'])
            ->assertRedirect(route('trainer-advances.index', ['date' => '2026-05-12']));

        $this->assertDatabaseMissing('trainer_advances', ['id' => $trainerAdvance->id]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('trainer-advances.index', ['date' => '2026-05-12']))
            ->assertOk()
            ->assertDontSee($otherBranchTrainer->name)
            ->assertSee('لا توجد سلف في هذا اليوم');
    }

    public function test_manager_can_pay_current_trainer_salary(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        AppSetting::putValue('trainer_payment_week_start', 'sunday');
        AppSetting::putValue('trainer_payment_week_end', 'saturday');

        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب القبض',
            'phone' => '01000000050',
            'password' => '123456',
            'hourly_rate' => '150',
            'transfer_number' => '753',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        TrainerHour::query()->create([
            'trainer_id' => $trainer->id,
            'worked_on' => '2026-05-11',
            'hours' => '2',
        ]);

        TrainerHour::query()->create([
            'trainer_id' => $trainer->id,
            'worked_on' => '2026-05-12',
            'hours' => '3',
        ]);

        TrainerAdvance::query()->create([
            'trainer_id' => $trainer->id,
            'advanced_on' => '2026-05-12',
            'amount' => '100',
        ]);

        $this->travelTo('2026-05-13 12:00:00');

        try {
            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->assertOk()
                ->assertSee('قبض المدربين')
                ->assertSee($trainer->name)
                ->assertSee('5')
                ->assertSee('750')
                ->assertSee('100')
                ->assertSee('650');

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->post(route('trainer-payrolls.store'), [
                    'trainer_id' => $trainer->id,
                ])
                ->assertRedirect(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]));

            $trainerPayroll = TrainerPayroll::query()
                ->where('branch_id', $branch->id)
                ->where('trainer_id', $trainer->id)
                ->where('status', TrainerPayroll::STATUS_PAID)
                ->firstOrFail();

            $this->assertSame('2026-05-10', $trainerPayroll->period_start->toDateString());
            $this->assertSame('2026-05-16', $trainerPayroll->period_end->toDateString());
            $this->assertSame('5.00', $trainerPayroll->hours);
            $this->assertSame('150.00', $trainerPayroll->hourly_rate);
            $this->assertSame('750.00', $trainerPayroll->total_amount);
            $this->assertSame('100.00', $trainerPayroll->advance_amount);
            $this->assertSame('650.00', $trainerPayroll->net_amount);

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->assertOk()
                ->assertSee('جدول المرتبات المصروفة')
                ->assertSee($trainer->name)
                ->assertSee('650')
                ->assertSee('2026-05-13');
        } finally {
            $this->travelBack();
        }
    }

    public function test_unpaid_completed_trainer_salary_moves_to_held_table_and_can_be_released(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        AppSetting::putValue('trainer_payment_week_start', 'sunday');
        AppSetting::putValue('trainer_payment_week_end', 'saturday');

        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب راتب محجوز',
            'phone' => '01000000051',
            'password' => '123456',
            'hourly_rate' => '180',
            'transfer_number' => '852',
            'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
        ]);

        TrainerHour::query()->create([
            'trainer_id' => $trainer->id,
            'worked_on' => '2026-05-04',
            'hours' => '1.5',
        ]);

        TrainerHour::query()->create([
            'trainer_id' => $trainer->id,
            'worked_on' => '2026-05-05',
            'hours' => '2.5',
        ]);

        TrainerAdvance::query()->create([
            'trainer_id' => $trainer->id,
            'advanced_on' => '2026-05-05',
            'amount' => '120',
        ]);

        $this->travelTo('2026-05-13 12:00:00');

        try {
            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->assertOk()
                ->assertSee('جدول الرواتب المحجوزة')
                ->assertSee($trainer->name)
                ->assertSee('720')
                ->assertSee('120')
                ->assertSee('600');

            $heldPayroll = TrainerPayroll::query()
                ->where('trainer_id', $trainer->id)
                ->where('status', TrainerPayroll::STATUS_HELD)
                ->firstOrFail();

            $this->assertSame('2026-05-03', $heldPayroll->period_start->toDateString());
            $this->assertSame('2026-05-09', $heldPayroll->period_end->toDateString());
            $this->assertSame('120.00', $heldPayroll->advance_amount);
            $this->assertSame('600.00', $heldPayroll->net_amount);

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->post(route('trainer-payrolls.release', $heldPayroll))
                ->assertRedirect(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]));

            $this->assertDatabaseHas('trainer_payrolls', [
                'id' => $heldPayroll->id,
                'status' => TrainerPayroll::STATUS_PAID,
                'total_amount' => '720.00',
                'advance_amount' => '120.00',
                'net_amount' => '600.00',
            ]);
        } finally {
            $this->travelBack();
        }
    }

    public function test_current_trainer_salary_cannot_be_paid_when_advances_consume_the_salary(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        AppSetting::putValue('trainer_payment_week_start', 'sunday');
        AppSetting::putValue('trainer_payment_week_end', 'saturday');

        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب صافي سالب',
            'phone' => '01000000052',
            'password' => '123456',
            'hourly_rate' => '100',
            'transfer_number' => '951',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        TrainerHour::query()->create([
            'trainer_id' => $trainer->id,
            'worked_on' => '2026-05-11',
            'hours' => '2',
        ]);

        TrainerAdvance::query()->create([
            'trainer_id' => $trainer->id,
            'advanced_on' => '2026-05-12',
            'amount' => '250',
        ]);

        $this->travelTo('2026-05-13 12:00:00');

        try {
            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->assertOk()
                ->assertSee('200')
                ->assertSee('250')
                ->assertSee('-50');

            $this->from(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->post(route('trainer-payrolls.store'), [
                    'trainer_id' => $trainer->id,
                ])
                ->assertRedirect(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->assertSessionHasErrors(['trainer_id']);

            $this->assertDatabaseMissing('trainer_payrolls', [
                'trainer_id' => $trainer->id,
                'status' => TrainerPayroll::STATUS_PAID,
            ]);
        } finally {
            $this->travelBack();
        }
    }

    public function test_completed_period_with_only_trainer_advance_is_added_to_held_payrolls(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        AppSetting::putValue('trainer_payment_week_start', 'sunday');
        AppSetting::putValue('trainer_payment_week_end', 'saturday');

        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب سلفة فقط',
            'phone' => '01000000053',
            'password' => '123456',
            'hourly_rate' => '100',
            'transfer_number' => '357',
            'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
        ]);

        TrainerAdvance::query()->create([
            'trainer_id' => $trainer->id,
            'advanced_on' => '2026-05-05',
            'amount' => '80',
        ]);

        $this->travelTo('2026-05-13 12:00:00');

        try {
            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('trainer-payrolls.index', ['trainer_id' => $trainer->id]))
                ->assertOk()
                ->assertSee('جدول الرواتب المحجوزة')
                ->assertSee($trainer->name)
                ->assertSee('80')
                ->assertSee('-80');

            $this->assertDatabaseHas('trainer_payrolls', [
                'trainer_id' => $trainer->id,
                'status' => TrainerPayroll::STATUS_HELD,
                'total_amount' => '0.00',
                'advance_amount' => '80.00',
                'net_amount' => '-80.00',
            ]);
        } finally {
            $this->travelBack();
        }
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

    public function test_trainer_payroll_page_loads_when_payrolls_table_is_missing(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        Schema::dropIfExists('trainer_payrolls');

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('trainer-payrolls.index'))
            ->assertOk()
            ->assertSee('جدول قبض المدربين غير مهيأ بعد');
    }

    public function test_trainer_payroll_store_does_not_crash_when_payrolls_table_is_missing(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب تجريبي',
            'phone' => '01000000099',
            'password' => '123456',
            'hourly_rate' => '100',
            'transfer_number' => '999',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        Schema::dropIfExists('trainer_payrolls');

        $this->from(route('trainer-payrolls.index'))
            ->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('trainer-payrolls.store'), [
                'trainer_id' => $trainer->id,
            ])
            ->assertRedirect(route('trainer-payrolls.index'))
            ->assertSessionHasErrors(['trainer_id']);
    }
}
