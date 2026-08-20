<?php

namespace Tests\Feature;

use App\Filament\App\Resources\StudentResource\Pages\EditStudent;
use App\Filament\Student\Pages\StudentProfile;
use App\Models\School;
use App\Models\User;
use App\Services\ProfilePhotoService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Students\Models\Student;
use Tests\TestCase;

class ProfilePhotoUploaderTest extends TestCase
{
    use InteractsWithDatabase;

    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        if (! empty($this->createdUserIds)) {
            $students = Student::whereIn('user_id', $this->createdUserIds)->get();
            foreach ($students as $student) {
                if ($student->photo_path) {
                    Storage::disk('public')->delete(ltrim($student->photo_path, '/'));
                }
            }
            Student::whereIn('id', $students->pluck('id'))->forceDelete();
            User::whereIn('id', $this->createdUserIds)->forceDelete();
            $this->createdUserIds = [];
        }

        parent::tearDown();
    }

    private function makeUserAndStudent(School $school): array
    {
        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Photo Tester',
            'email' => 'photo-'.uniqid().'@test.local',
            'password' => 'Password123!',
            'account_status' => 'active',
            'requested_role' => 'student',
        ]);

        $this->createdUserIds[] = $user->id;

        $student = Student::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Photo',
            'last_name' => 'Tester',
            'gender' => 'male',
            'date_of_birth' => '2010-01-01',
            'admission_date' => now()->toDateString(),
            'student_id_number' => 'PH-'.uniqid(),
            'admission_number' => 'PHAD-'.uniqid(),
            'status' => 'active',
        ]);

        return [$user, $student];
    }

    private function prepareStudentPanel(School $school, User $user): void
    {
        $this->actingAs($user);
        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'student']);
        Filament::setCurrentPanel(Filament::getPanel('student'));
    }

    public function test_student_profile_page_renders_photo_uploader(): void
    {
        $school = School::firstOrFail();
        [$user, $student] = $this->makeUserAndStudent($school);
        $this->prepareStudentPanel($school, $user);

        Livewire::test(StudentProfile::class)
            ->assertOk()
            ->assertViewHas('student', fn ($s) => $s?->id === $student->id)
            ->assertViewHas('photoRejection');
    }

    public function test_student_profile_shows_rejection_reason_when_photo_removed(): void
    {
        $school = School::firstOrFail();
        [$user, $student] = $this->makeUserAndStudent($school);

        $student->update([
            'photo_rejected_reason' => 'Photo was blurry',
            'photo_rejected_by' => 1,
            'photo_rejected_at' => now(),
        ]);

        $this->prepareStudentPanel($school, $user);

        Livewire::test(StudentProfile::class)
            ->assertOk()
            ->assertViewHas('photoRejection', function ($rejection) {
                return $rejection !== null && $rejection['reason'] === 'Photo was blurry';
            });
    }

    public function test_student_can_save_valid_passport_photo(): void
    {
        $school = School::firstOrFail();
        [$user, $student] = $this->makeUserAndStudent($school);
        $this->prepareStudentPanel($school, $user);

        $dataUrl = $this->makePassportDataUrl(480, 640);

        Livewire::test(StudentProfile::class)
            ->call('savePhoto', $dataUrl)
            ->assertHasNoErrors();

        $fresh = Student::find($student->id);
        $this->assertNotNull($fresh->photo_path);
        $this->assertNull($fresh->photo_rejected_at);
        Storage::disk('public')->assertExists(ltrim($fresh->photo_path, '/'));
    }

    public function test_student_photo_rejects_tiny_low_quality_image(): void
    {
        $school = School::firstOrFail();
        [$user, $student] = $this->makeUserAndStudent($school);
        $this->prepareStudentPanel($school, $user);

        $dataUrl = $this->makePassportDataUrl(150, 200);

        Livewire::test(StudentProfile::class)
            ->call('savePhoto', $dataUrl)
            ->assertHasNoErrors();

        $this->assertNull(Student::find($student->id)->photo_path);
    }

    public function test_student_photo_rejects_landscape_non_passport_ratio(): void
    {
        $school = School::firstOrFail();
        [$user, $student] = $this->makeUserAndStudent($school);
        $this->prepareStudentPanel($school, $user);

        $dataUrl = $this->makePassportDataUrl(800, 500);

        Livewire::test(StudentProfile::class)
            ->call('savePhoto', $dataUrl)
            ->assertHasNoErrors();

        $this->assertNull(Student::find($student->id)->photo_path);
    }

    public function test_profile_photo_service_rejects_invalid_payload(): void
    {
        [$path, $error] = app(ProfilePhotoService::class)->storeFromDataUrl('not-a-data-url', 'student-photos');
        $this->assertNull($path);
        $this->assertNotNull($error);
    }

    protected function makePassportDataUrl(int $width, int $height): string
    {
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 220, 220, 230);
        imagefill($img, 0, 0, $bg);
        ob_start();
        imagejpeg($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/jpeg;base64,'.base64_encode($data);
    }

    public function test_admin_can_remove_student_photo_via_action(): void
    {
        $school = School::firstOrFail();
        [$user, $student] = $this->makeUserAndStudent($school);
        $this->prepareStudentPanel($school, $user);

        $dataUrl = $this->makePassportDataUrl(480, 640);
        Livewire::test(StudentProfile::class)->call('savePhoto', $dataUrl);

        $student->refresh();
        $this->assertNotNull($student->photo_path);

        $admin = User::find(13);
        $this->actingAs($admin);
        App::instance('current_tenant', $school);
        URL::defaults(['panel' => 'app']);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        Livewire::test(EditStudent::class, [
            'record' => $student->getRouteKey(),
        ])
            ->assertActionVisible('removeProfilePhoto')
            ->callAction('removeProfilePhoto', ['reason' => 'Not clear'])
            ->assertHasNoActionErrors();

        $fresh = $student->refresh();
        $this->assertNull($fresh->photo_path);
        $this->assertSame('Not clear', $fresh->photo_rejected_reason);
        $this->assertSame($admin->id, (int) $fresh->photo_rejected_by);
        $this->assertNotNull($fresh->photo_rejected_at);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->latest()
            ->first();
        $this->assertNotNull($notification);
        $data = json_decode($notification->data, true);
        $this->assertStringContainsString('profile photo was removed', $data['subject'] ?? '');
    }
}
