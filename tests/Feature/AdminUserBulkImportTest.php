<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_bulk_import_creates_student_with_gender_from_csv(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $department = Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'programme_name' => 'Bachelor of Business ICT',
        ]);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $csv = "name,email,reg_no,phone_number,gender,role,department,programme,year_of_study\n"
            ."Import Student,import.student@example.com,MoCU/BBICT/501/20,255738234345,female,student,CICT,BBICT,2\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv, 'text/csv');

        $response = $this->actingAs($admin)->from(route('admin.users.index'))->post(route('admin.users.bulk-import'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');

        $user = User::query()->where('email', 'import.student@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isStudentUser());
        $this->assertSame('+255738234345', $user->phone_number);

        $student = Student::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($student);
        $this->assertSame('female', $student->gender);
    }

    public function test_bulk_import_skips_student_rows_without_gender(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $department = Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'programme_name' => 'Bachelor of Business ICT',
        ]);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $csv = "name,email,reg_no,phone_number,gender,role,department,programme,year_of_study\n"
            ."No Gender Student,nogender@example.com,MoCU/BBICT/502/20,255738234345,,student,CICT,BBICT,2\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv, 'text/csv');

        $response = $this->actingAs($admin)->from(route('admin.users.index'))->post(route('admin.users.bulk-import'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');
        $this->assertNull(User::query()->where('email', 'nogender@example.com')->first());
    }

    public function test_bulk_import_skips_rows_without_phone_number(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $department = Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'programme_name' => 'Bachelor of Business ICT',
        ]);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $csv = "name,email,reg_no,phone_number,gender,role,department,programme,year_of_study\n"
            ."No Phone Student,nophone@example.com,MoCU/BBICT/504/20,,female,student,CICT,BBICT,2\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv, 'text/csv');

        $response = $this->actingAs($admin)->from(route('admin.users.index'))->post(route('admin.users.bulk-import'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');
        $this->assertNull(User::query()->where('email', 'nophone@example.com')->first());
    }

    public function test_create_student_requires_gender(): void
    {
        $this->seed(RoleSeeder::class);

        $department = Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'programme_name' => 'Bachelor of Business ICT',
        ]);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'form_context' => 'create',
            'name' => 'New Student',
            'email' => 'new.student@example.com',
            'phone_number' => '255738234345',
            'login_id' => 'MoCU/BBICT/503/20',
            'role' => 'student',
            'department' => 'CICT',
            'programme' => 'BBICT',
            'year_of_study' => 2,
        ]);

        $response->assertSessionHasErrors('gender');
        $this->assertNull(User::query()->where('email', 'new.student@example.com')->first());
    }

    public function test_create_supervisor_requires_gender_and_stores_on_staff_profile(): void
    {
        $this->seed(RoleSeeder::class);
        Notification::fake();

        Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'form_context' => 'create',
            'name' => 'New Supervisor',
            'email' => 'supervisor.new@example.com',
            'phone_number' => '255738234345',
            'login_id' => 'MoCU/CICT/601/20',
            'role' => 'supervisor',
            'department' => 'CICT',
            'gender' => 'male',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'supervisor.new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('male', $user->gender);

        $staff = \App\Models\Staff::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($staff);
        $this->assertSame('male', $staff->gender);
    }
}
