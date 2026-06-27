<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SyncSisStudentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sis_sync_sets_student_gender_from_source_file(): void
    {
        $source = base_path('storage/app/sis/test-students-gender.json');
        File::ensureDirectoryExists(dirname($source));
        File::put($source, json_encode([
            [
                'registration_number' => 'SIS/TEST/0001',
                'full_name' => 'Test Female',
                'gender' => 'female',
                'programme' => 'BBICT',
                'department' => 'FBIS',
                'year_of_study' => 4,
                'university_email' => 'test.female@example.edu',
                'enrollment_status' => 'active',
            ],
            [
                'registration_number' => 'SIS/TEST/0002',
                'full_name' => 'Test Male',
                'sex' => 'M',
                'programme' => 'BBICT',
                'department' => 'FBIS',
                'year_of_study' => 4,
                'university_email' => 'test.male@example.edu',
                'enrollment_status' => 'active',
            ],
        ], JSON_THROW_ON_ERROR));

        $exitCode = Artisan::call('sis:sync-students', ['--source' => 'storage/app/sis/test-students-gender.json']);

        $this->assertSame(0, $exitCode);

        $female = Student::query()->where('registration_number', 'SIS/TEST/0001')->first();
        $male = Student::query()->where('registration_number', 'SIS/TEST/0002')->first();

        $this->assertNotNull($female);
        $this->assertNotNull($male);
        $this->assertSame('female', $female->gender);
        $this->assertSame('male', $male->gender);
        $this->assertSame('female', data_get($female->sis_data, 'gender'));
        $this->assertSame('M', data_get($male->sis_data, 'gender'));

        File::delete($source);
    }

    public function test_gender_backfill_copies_from_sis_data(): void
    {
        $user = User::factory()->student('project_student')->create([
            'registration_number' => 'SIS/BF/0001',
            'login_id' => 'SIS/BF/0001',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'registration_number' => 'SIS/BF/0001',
            'gender' => null,
            'sis_data' => ['gender' => 'female'],
        ]);

        Artisan::call('prms:backfill-student-gender');

        $this->assertSame('female', $student->fresh()->gender);
    }
}
