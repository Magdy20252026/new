<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\AdministratorPayroll;
use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Swimmer;
use App\Models\SwimmerFile;
use App\Models\Trainer;
use App\Models\TrainerAdvance;
use App\Models\TrainerFile;
use App\Models\TrainerHour;
use App\Models\TrainerPayroll;
use App\Models\TrainingGroup;
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

    public function test_manager_can_create_update_and_delete_administrator(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('administrators.store'), [
                'name' => 'أحمد علي',
                'phone' => '01000000100',
                'job_title' => 'مشرف إداري',
                'salary' => '4500',
            ])
            ->assertRedirect(route('administrators.index'));

        $administrator = Administrator::query()->where('phone', '01000000100')->firstOrFail();

        $this->assertSame($branch->id, $administrator->branch_id);
        $this->assertSame('أحمد علي', $administrator->name);
        $this->assertSame('مشرف إداري', $administrator->job_title);
        $this->assertSame('4500.00', $administrator->salary);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('administrators.update', $administrator), [
                'name' => 'أحمد محمد',
                'phone' => '01000000101',
                'job_title' => 'مدير إداري',
                'salary' => '5200',
            ])
            ->assertRedirect(route('administrators.index'));

        $administrator->refresh();

        $this->assertSame('أحمد محمد', $administrator->name);
        $this->assertSame('01000000101', $administrator->phone);
        $this->assertSame('مدير إداري', $administrator->job_title);
        $this->assertSame('5200.00', $administrator->salary);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('administrators.destroy', $administrator))
            ->assertRedirect(route('administrators.index'));

        $this->assertDatabaseMissing('administrators', ['id' => $administrator->id]);
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

    public function test_manager_can_create_update_and_delete_training_group_with_generated_name(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب المجموعة',
            'phone' => '01000000040',
            'password' => '123456',
            'hourly_rate' => '150',
            'transfer_number' => '741',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('training-groups.store'), [
                'level' => 'تجهيزي فرق جديد',
                'trainer_id' => $trainer->id,
                'training_days_per_week' => 2,
                'available_training_days' => 12,
                'max_swimmers' => 24,
                'price' => '650',
                'schedule' => [
                    ['day' => 'saturday', 'time' => '14:00'],
                    ['day' => 'tuesday', 'time' => '16:30'],
                ],
            ])
            ->assertRedirect(route('training-groups.index'));

        $trainingGroup = TrainingGroup::query()->firstOrFail();

        $this->assertSame($branch->id, $trainingGroup->branch_id);
        $this->assertSame($trainer->id, $trainingGroup->trainer_id);
        $this->assertSame('تجهيزي فرق جديد - مدرب المجموعة - السبت 14:00 - الثلاثاء 16:30', $trainingGroup->name);
        $this->assertSame([
            ['day' => 'saturday', 'time' => '14:00'],
            ['day' => 'tuesday', 'time' => '16:30'],
        ], $trainingGroup->schedule);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('training-groups.update', $trainingGroup), [
                'level' => 'فرق استارات 2 نجمة',
                'trainer_id' => $trainer->id,
                'training_days_per_week' => 1,
                'available_training_days' => 9,
                'max_swimmers' => 20,
                'price' => '720',
                'schedule' => [
                    ['day' => 'monday', 'time' => '18:15'],
                ],
            ])
            ->assertRedirect(route('training-groups.index'));

        $trainingGroup->refresh();

        $this->assertSame('فرق استارات 2 نجمة - مدرب المجموعة - الاثنين 18:15', $trainingGroup->name);
        $this->assertSame(1, $trainingGroup->training_days_per_week);
        $this->assertSame(9, $trainingGroup->available_training_days);
        $this->assertSame(20, $trainingGroup->max_swimmers);
        $this->assertSame('720.00', $trainingGroup->price);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('training-groups.destroy', $trainingGroup))
            ->assertRedirect(route('training-groups.index'));

        $this->assertDatabaseMissing('training_groups', ['id' => $trainingGroup->id]);
    }


    public function test_manager_can_create_update_and_delete_swimmer_with_generated_barcode(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب السباحين',
            'phone' => '01000000043',
            'password' => '123456',
            'hourly_rate' => '150',
            'transfer_number' => '3333',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);
        $trainingGroup = TrainingGroup::query()->create([
            'branch_id' => $branch->id,
            'trainer_id' => $trainer->id,
            'name' => 'مجموعة 10 صباحًا',
            'level' => 'مدارس سباحة',
            'training_days_per_week' => 2,
            'available_training_days' => 12,
            'max_swimmers' => 20,
            'price' => '650',
            'schedule' => [['day' => 'saturday', 'time' => '10:00']],
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('swimmers.store'), [
                'name' => 'عمر أحمد',
                'birth_year' => 2016,
                'father_phone' => '01020030040',
                'mother_phone' => '01020030041',
                'training_group_id' => $trainingGroup->id,
                'subscription_start_date' => '2026-05-17',
                'subscription_end_date' => '2026-06-28',
                'group_price' => '650',
                'amount_paid' => '200',
            ])
            ->assertRedirect(route('swimmers.index'));

        $swimmer = Swimmer::query()->firstOrFail();

        $this->assertSame(1001, $swimmer->serial_number);
        $this->assertSame('1001-عمر أحمد-2016-10-01020030040-01020030041-مجموعة 10 صباحًا', $swimmer->barcode);
        $this->assertSame('450.00', $swimmer->remaining_amount);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('swimmers.update', $swimmer), [
                'name' => 'عمر أحمد علي',
                'birth_year' => 2015,
                'father_phone' => '01020030042',
                'mother_phone' => '01020030043',
                'training_group_id' => $trainingGroup->id,
                'subscription_start_date' => '2026-05-18',
                'subscription_end_date' => '2026-06-29',
                'group_price' => '700',
                'amount_paid' => '250',
            ])
            ->assertRedirect(route('swimmers.index'));

        $swimmer->refresh();

        $this->assertSame('1001-عمر أحمد علي-2015-11-01020030042-01020030043-مجموعة 10 صباحًا', $swimmer->barcode);
        $this->assertSame('450.00', $swimmer->remaining_amount);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('swimmers.destroy', $swimmer))
            ->assertRedirect(route('swimmers.index'));

        $this->assertDatabaseMissing('swimmers', ['id' => $swimmer->id]);
    }

    public function test_manager_can_manage_swimmer_files_with_multiple_medical_reports(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $trainer = Trainer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مدرب الملفات',
            'phone' => '01000000044',
            'password' => '123456',
            'hourly_rate' => '155',
            'transfer_number' => '4444',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);
        $trainingGroup = TrainingGroup::query()->create([
            'branch_id' => $branch->id,
            'trainer_id' => $trainer->id,
            'name' => 'مجموعة الملفات',
            'level' => 'مدارس سباحة',
            'training_days_per_week' => 2,
            'available_training_days' => 8,
            'max_swimmers' => 12,
            'price' => '500',
            'schedule' => [['day' => 'monday', 'time' => '11:00']],
        ]);
        $swimmer = Swimmer::query()->create([
            'branch_id' => $branch->id,
            'training_group_id' => $trainingGroup->id,
            'serial_number' => 1001,
            'barcode' => '1001-سباح-2015-11-010-011-مجموعة الملفات',
            'name' => 'سباح الملفات',
            'birth_year' => 2015,
            'father_phone' => '01010010010',
            'mother_phone' => '01010010011',
            'subscription_start_date' => '2026-05-17',
            'subscription_end_date' => '2026-06-14',
            'group_price' => '500',
            'amount_paid' => '100',
            'remaining_amount' => '400',
        ]);

        $photo = UploadedFile::fake()->image('swimmer-photo.png', 1200, 1200);
        $medicalOne = UploadedFile::fake()->image('medical-one.png', 1200, 1200);
        $medicalTwo = UploadedFile::fake()->image('medical-two.png', 1200, 1200);
        $replacement = UploadedFile::fake()->image('replacement.png', 1200, 1200);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('swimmers.files.store', $swimmer), [
                'type' => SwimmerFile::TYPE_PLAYER_PHOTO,
                'title' => 'صورة اللاعب',
                'images' => [$photo],
            ])
            ->assertRedirect(route('swimmers.files.index', $swimmer));

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('swimmers.files.store', $swimmer), [
                'type' => SwimmerFile::TYPE_MEDICAL_REPORT,
                'title' => 'التقرير الطبي',
                'images' => [$medicalOne, $medicalTwo],
            ])
            ->assertRedirect(route('swimmers.files.index', $swimmer));

        $this->assertCount(3, $swimmer->swimmerFiles()->get());
        $swimmerFile = $swimmer->swimmerFiles()->where('type', SwimmerFile::TYPE_PLAYER_PHOTO)->firstOrFail();
        $this->assertFileExists(public_path($swimmerFile->file_path));

        $oldPath = $swimmerFile->file_path;

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->put(route('swimmers.files.update', [$swimmer, $swimmerFile]), [
                'type' => SwimmerFile::TYPE_FEDERATION_CARD,
                'title' => 'كارنية الاتحاد',
                'image' => $replacement,
            ])
            ->assertRedirect(route('swimmers.files.index', $swimmer));

        $swimmerFile->refresh();
        $this->assertSame(SwimmerFile::TYPE_FEDERATION_CARD, $swimmerFile->type);
        $this->assertFileDoesNotExist(public_path($oldPath));
        $this->assertFileExists(public_path($swimmerFile->file_path));

        $currentPath = $swimmerFile->file_path;

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->delete(route('swimmers.files.destroy', [$swimmer, $swimmerFile]))
            ->assertRedirect(route('swimmers.files.index', $swimmer));

        $this->assertDatabaseMissing('swimmer_files', ['id' => $swimmerFile->id]);
        $this->assertFileDoesNotExist(public_path($currentPath));

        File::deleteDirectory(public_path('uploads/swimmers'));
    }

    public function test_swimmer_page_shows_only_swimmers_for_current_branch(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع 2']);
        $trainerOne = Trainer::query()->create([
            'branch_id' => $branchOne->id,
            'name' => 'مدرب 1',
            'phone' => '01000000045',
            'password' => '123456',
            'hourly_rate' => '120',
            'transfer_number' => '5555',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);
        $trainerTwo = Trainer::query()->create([
            'branch_id' => $branchTwo->id,
            'name' => 'مدرب 2',
            'phone' => '01000000046',
            'password' => '123456',
            'hourly_rate' => '120',
            'transfer_number' => '6666',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);
        $groupOne = TrainingGroup::query()->create([
            'branch_id' => $branchOne->id,
            'trainer_id' => $trainerOne->id,
            'name' => 'مجموعة الفرع الأول',
            'level' => 'مدارس سباحة',
            'training_days_per_week' => 2,
            'available_training_days' => 8,
            'max_swimmers' => 12,
            'price' => '300',
            'schedule' => [['day' => 'saturday', 'time' => '12:00']],
        ]);
        $groupTwo = TrainingGroup::query()->create([
            'branch_id' => $branchTwo->id,
            'trainer_id' => $trainerTwo->id,
            'name' => 'مجموعة الفرع الثاني',
            'level' => 'مدارس سباحة',
            'training_days_per_week' => 2,
            'available_training_days' => 8,
            'max_swimmers' => 12,
            'price' => '300',
            'schedule' => [['day' => 'monday', 'time' => '12:00']],
        ]);

        $swimmerOne = Swimmer::query()->create([
            'branch_id' => $branchOne->id,
            'training_group_id' => $groupOne->id,
            'serial_number' => 1001,
            'barcode' => '1001-سباح الفرع الأول-2014-12-1-2-مجموعة الفرع الأول',
            'name' => 'سباح الفرع الأول',
            'birth_year' => 2014,
            'father_phone' => '1',
            'mother_phone' => '2',
            'subscription_start_date' => '2026-05-17',
            'subscription_end_date' => '2026-06-14',
            'group_price' => '300',
            'amount_paid' => '100',
            'remaining_amount' => '200',
        ]);
        $swimmerTwo = Swimmer::query()->create([
            'branch_id' => $branchTwo->id,
            'training_group_id' => $groupTwo->id,
            'serial_number' => 1002,
            'barcode' => '1002-سباح الفرع الثاني-2014-12-3-4-مجموعة الفرع الثاني',
            'name' => 'سباح الفرع الثاني',
            'birth_year' => 2014,
            'father_phone' => '3',
            'mother_phone' => '4',
            'subscription_start_date' => '2026-05-17',
            'subscription_end_date' => '2026-06-14',
            'group_price' => '300',
            'amount_paid' => '100',
            'remaining_amount' => '200',
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('swimmers.index'))
            ->assertOk()
            ->assertSee($swimmerOne->name)
            ->assertDontSee($swimmerTwo->name);
    }

    public function test_training_groups_page_shows_only_groups_for_current_branch(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع 2']);

        $trainerOne = Trainer::query()->create([
            'branch_id' => $branchOne->id,
            'name' => 'مدرب الفرع الأول',
            'phone' => '01000000041',
            'password' => '123456',
            'hourly_rate' => '120',
            'transfer_number' => '1111',
            'transfer_type' => Trainer::TRANSFER_TYPE_WALLET,
        ]);

        $trainerTwo = Trainer::query()->create([
            'branch_id' => $branchTwo->id,
            'name' => 'مدرب الفرع الثاني',
            'phone' => '01000000042',
            'password' => '123456',
            'hourly_rate' => '140',
            'transfer_number' => '2222',
            'transfer_type' => Trainer::TRANSFER_TYPE_INSTAPAY,
        ]);

        $groupOne = TrainingGroup::query()->create([
            'branch_id' => $branchOne->id,
            'trainer_id' => $trainerOne->id,
            'name' => 'مجموعة الفرع الأول',
            'level' => 'مدارس سباحة',
            'training_days_per_week' => 1,
            'available_training_days' => 2,
            'max_swimmers' => 12,
            'price' => '300',
            'schedule' => [['day' => 'saturday', 'time' => '12:00']],
        ]);

        $groupTwo = TrainingGroup::query()->create([
            'branch_id' => $branchTwo->id,
            'trainer_id' => $trainerTwo->id,
            'name' => 'مجموعة الفرع الثاني',
            'level' => 'رجال',
            'training_days_per_week' => 1,
            'available_training_days' => 2,
            'max_swimmers' => 16,
            'price' => '400',
            'schedule' => [['day' => 'monday', 'time' => '15:00']],
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('training-groups.index'))
            ->assertOk()
            ->assertSee($groupOne->name)
            ->assertDontSee($groupTwo->name);
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

    public function test_administrator_page_shows_only_administrators_for_current_branch(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branchOne = Branch::query()->firstOrFail();
        $branchTwo = Branch::query()->create(['name' => 'فرع 2']);

        $administratorOne = Administrator::query()->create([
            'branch_id' => $branchOne->id,
            'name' => 'إداري الفرع الأول',
            'phone' => '01000000200',
            'job_title' => 'سكرتير',
            'salary' => '4000',
        ]);

        $administratorTwo = Administrator::query()->create([
            'branch_id' => $branchTwo->id,
            'name' => 'إداري الفرع الثاني',
            'phone' => '01000000201',
            'job_title' => 'محاسب',
            'salary' => '4300',
        ]);

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branchOne->id])
            ->get(route('administrators.index'))
            ->assertOk()
            ->assertSee($administratorOne->name)
            ->assertDontSee($administratorTwo->name);
    }

    public function test_administrators_page_shows_setup_error_when_table_is_missing(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        Schema::drop('administrators');

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('administrators.index'))
            ->assertOk()
            ->assertSee('جدول الإداريين غير مهيأ بعد. شغّل php artisan migrate ثم أعد تحميل الصفحة.');
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
            ->assertSee('المجموعات')
            ->assertSee('صلاحيات المستخدمين')
            ->assertSee('السباحين')
            ->assertSee('المدربين')
            ->assertSee(route('administrators.index'))
            ->assertSee(route('training-groups.index'))
            ->assertSee(route('trainer-hours.index'))
            ->assertSee(route('trainer-advances.index'))
            ->assertSee(route('trainer-payrolls.index'))
            ->assertSee(route('administrator-payrolls.index'))
            ->assertSee(route('trainer-payment-week.edit'))
            ->assertSee('قبض المدربين')
            ->assertSee('قبض الإداريين')
            ->assertSee('الإداريين')
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

    public function test_manager_can_pay_administrator_salary_and_see_it_in_table(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $administrator = Administrator::query()->create([
            'branch_id' => $branch->id,
            'name' => 'إداري الرواتب',
            'phone' => '01000000400',
            'job_title' => 'مدير إداري',
            'salary' => '5200',
        ]);

        $this->travelTo('2026-05-17 12:00:00');

        try {
            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('administrator-payrolls.index', ['administrator_id' => $administrator->id]))
                ->assertOk()
                ->assertSee('قبض الإداريين')
                ->assertSee($administrator->name)
                ->assertSee($administrator->phone)
                ->assertSee('5200');

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->post(route('administrator-payrolls.store'), [
                    'administrator_id' => $administrator->id,
                ])
                ->assertRedirect(route('administrator-payrolls.index'));

            $administratorPayroll = AdministratorPayroll::query()
                ->where('branch_id', $branch->id)
                ->where('administrator_id', $administrator->id)
                ->firstOrFail();

            $this->assertSame('2026-05-01', $administratorPayroll->period_start->toDateString());
            $this->assertSame('2026-05-31', $administratorPayroll->period_end->toDateString());
            $this->assertSame('5200.00', $administratorPayroll->amount);

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('administrator-payrolls.index'))
                ->assertOk()
                ->assertSee('جدول رواتب الإداريين')
                ->assertSee($administrator->name)
                ->assertSee('2026-05')
                ->assertSee('2026-05-17');
        } finally {
            $this->travelBack();
        }
    }

    public function test_paid_administrator_is_hidden_for_current_month_and_returns_next_month(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $administrator = Administrator::query()->create([
            'branch_id' => $branch->id,
            'name' => 'إداري شهري',
            'phone' => '01000000401',
            'job_title' => 'محاسب',
            'salary' => '4800',
        ]);

        $this->travelTo('2026-05-17 12:00:00');

        try {
            AdministratorPayroll::query()->create([
                'branch_id' => $branch->id,
                'administrator_id' => $administrator->id,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
                'amount' => '4800',
                'paid_at' => '2026-05-17 12:00:00',
            ]);

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('administrator-payrolls.index'))
                ->assertOk()
                ->assertDontSee('value="'.$administrator->id.'"', false);

            $this->travelTo('2026-06-01 12:00:00');

            $this->actingAs($manager)
                ->withSession(['current_branch_id' => $branch->id])
                ->get(route('administrator-payrolls.index'))
                ->assertOk()
                ->assertSee('value="'.$administrator->id.'"', false)
                ->assertSee($administrator->name);
        } finally {
            $this->travelBack();
        }
    }

    public function test_administrator_payroll_page_loads_when_table_is_missing(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        Schema::dropIfExists('administrator_payrolls');

        $this->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('administrator-payrolls.index'))
            ->assertOk()
            ->assertSee('جدول قبض الإداريين غير مهيأ بعد');
    }

    public function test_administrator_payroll_store_does_not_crash_when_table_is_missing(): void
    {
        $this->seed();
        $manager = User::query()->where('username', 'magdy')->firstOrFail();
        $branch = Branch::query()->firstOrFail();
        $administrator = Administrator::query()->create([
            'branch_id' => $branch->id,
            'name' => 'إداري تجريبي',
            'phone' => '01000000402',
            'job_title' => 'سكرتير',
            'salary' => '4100',
        ]);

        Schema::dropIfExists('administrator_payrolls');

        $this->from(route('administrator-payrolls.index'))
            ->actingAs($manager)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('administrator-payrolls.store'), [
                'administrator_id' => $administrator->id,
            ])
            ->assertRedirect(route('administrator-payrolls.index'))
            ->assertSessionHasErrors(['administrator_id']);
    }
}
