<?php

namespace Tests\Unit;

use App\Models\Student;
use App\Support\GroupAutoFormer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupAutoFormerTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_size_plan_for_pairs_with_odd_count(): void
    {
        $former = new GroupAutoFormer;

        $this->assertSame([2, 2, 3], $former->buildSizePlan(7, 2));
        $this->assertSame([3], $former->buildSizePlan(3, 2));
        $this->assertSame([2, 2], $former->buildSizePlan(4, 2));
    }

    public function test_build_size_plan_for_trios(): void
    {
        $former = new GroupAutoFormer;

        $this->assertSame([3, 3], $former->buildSizePlan(6, 3));
        $this->assertSame([3, 2], $former->buildSizePlan(5, 3));
        $this->assertSame([4], $former->buildSizePlan(4, 3));
    }

    public function test_form_groups_balances_gender_in_pairs(): void
    {
        $former = new GroupAutoFormer;

        $students = collect([
            $this->makeStudent(1, 'male'),
            $this->makeStudent(2, 'female'),
            $this->makeStudent(3, 'male'),
            $this->makeStudent(4, 'female'),
        ]);

        $groups = $former->formGroups($students, 2);

        $this->assertCount(2, $groups);
        $this->assertCount(2, $groups[0]);
        $this->assertCount(2, $groups[1]);

        foreach ($groups as $group) {
            $genders = collect($group)->map(fn (int $userId) => $students->firstWhere('user_id', $userId)?->normalizedGender());
            $this->assertTrue($genders->contains('male') && $genders->contains('female'));
        }
    }

    public function test_form_groups_individual_size(): void
    {
        $former = new GroupAutoFormer;

        $students = collect([
            $this->makeStudent(10, 'male'),
            $this->makeStudent(11, 'female'),
        ]);

        $groups = $former->formGroups($students, 1);

        $this->assertSame([[10], [11]], $groups);
    }

    public function test_form_groups_by_programme_keeps_programmes_separate(): void
    {
        $former = new GroupAutoFormer;

        $students = collect([
            $this->makeStudent(1, 'male', 101),
            $this->makeStudent(2, 'female', 101),
            $this->makeStudent(3, 'male', 202),
            $this->makeStudent(4, 'female', 202),
        ]);

        $groupsByProgramme = $former->formGroupsByProgramme($students, 2);

        $this->assertCount(2, $groupsByProgramme);
        $this->assertArrayHasKey(101, $groupsByProgramme);
        $this->assertArrayHasKey(202, $groupsByProgramme);
        $this->assertSame([[1, 2]], $groupsByProgramme[101]);
        $this->assertSame([[3, 4]], $groupsByProgramme[202]);
    }

    private function makeStudent(int $userId, string $gender, ?int $programmeId = null): Student
    {
        $student = new Student([
            'user_id' => $userId,
            'registration_number' => 'REG-' . $userId,
            'full_name' => 'Student ' . $userId,
            'gender' => $gender,
            'programme_id' => $programmeId,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);
        $student->id = $userId;

        return $student;
    }
}
