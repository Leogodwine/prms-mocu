<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Builds project groups from eligible students with gender balance.
 * Groups are always formed within a single programme cohort.
 *
 * @phpstan-type StudentGroup list<int>
 */
final class GroupAutoFormer
{
    /**
     * Form groups separately for each programme so members never mix programmes.
     *
     * @param  Collection<int, Student>  $students
     * @return array<int|string, list<StudentGroup>>  programme_id (0 = unknown) => groups
     */
    public function formGroupsByProgramme(Collection $students, int $groupSize): array
    {
        $cohorts = $students->groupBy(fn (Student $student) => $student->programme_id ?? 0);

        $groupsByProgramme = [];

        foreach ($cohorts as $programmeId => $cohort) {
            $groups = $this->formGroups($cohort, $groupSize);
            if ($groups !== []) {
                $groupsByProgramme[$programmeId] = $groups;
            }
        }

        return $groupsByProgramme;
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return list<StudentGroup>  user IDs per group
     */
    public function formGroups(Collection $students, int $groupSize): array
    {
        if ($groupSize < 1 || $groupSize > 3) {
            throw new \InvalidArgumentException('Group size must be 1, 2, or 3.');
        }

        $students = $students->values();

        if ($students->isEmpty()) {
            return [];
        }

        if ($groupSize === 1) {
            return $students
                ->map(fn (Student $student) => [(int) $student->user_id])
                ->values()
                ->all();
        }

        $plan = $this->buildSizePlan($students->count(), $groupSize);

        return $this->assignStudentsBalanced($students, $plan);
    }

    /**
     * @return list<int>  member counts per group
     */
    public function buildSizePlan(int $count, int $targetSize): array
    {
        if ($count < 1) {
            return [];
        }

        if ($targetSize === 1) {
            return array_fill(0, $count, 1);
        }

        if ($targetSize === 2) {
            if ($count === 1) {
                return [1];
            }

            if ($count % 2 === 1) {
                $pairCount = intdiv($count - 3, 2);

                return array_merge(
                    array_fill(0, max(0, $pairCount), 2),
                    [3]
                );
            }

            return array_fill(0, intdiv($count, 2), 2);
        }

        // Target size 3
        $fullGroups = intdiv($count, 3);
        $remainder = $count % 3;
        $plan = array_fill(0, $fullGroups, 3);

        if ($remainder === 0) {
            return $plan;
        }

        if ($remainder === 2) {
            $plan[] = 2;

            return $plan;
        }

        // Remainder 1
        if ($plan !== []) {
            $plan[count($plan) - 1] += 1;

            return $plan;
        }

        return [1];
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  list<int>  $plan
     * @return list<StudentGroup>
     */
    private function assignStudentsBalanced(Collection $students, array $plan): array
    {
        $males = $students
            ->filter(fn (Student $student) => $student->normalizedGender() === 'male')
            ->shuffle()
            ->values();
        $females = $students
            ->filter(fn (Student $student) => $student->normalizedGender() === 'female')
            ->shuffle()
            ->values();
        $unknown = $students
            ->filter(fn (Student $student) => $student->normalizedGender() === null)
            ->shuffle()
            ->values();

        $groups = [];

        foreach ($plan as $size) {
            $memberIds = $this->pickBalancedGroup($males, $females, $unknown, $size);

            if ($memberIds !== []) {
                $groups[] = $memberIds;
            }
        }

        return $groups;
    }

    /**
     * @param  Collection<int, Student>  $males
     * @param  Collection<int, Student>  $females
     * @param  Collection<int, Student>  $unknown
     * @return StudentGroup
     */
    private function pickBalancedGroup(
        Collection &$males,
        Collection &$females,
        Collection &$unknown,
        int $size
    ): array {
        $picked = [];

        for ($slot = 0; $slot < $size; $slot++) {
            $remaining = $males->count() + $females->count() + $unknown->count();
            if ($remaining === 0) {
                break;
            }

            $inGroupMale = count(array_filter(
                $picked,
                fn (Student $student) => $student->normalizedGender() === 'male'
            ));
            $inGroupFemale = count(array_filter(
                $picked,
                fn (Student $student) => $student->normalizedGender() === 'female'
            ));

            if ($males->isNotEmpty() && $females->isNotEmpty()) {
                if ($inGroupMale <= $inGroupFemale) {
                    $picked[] = $males->shift();
                } else {
                    $picked[] = $females->shift();
                }

                continue;
            }

            if ($males->isNotEmpty()) {
                $picked[] = $males->shift();

                continue;
            }

            if ($females->isNotEmpty()) {
                $picked[] = $females->shift();

                continue;
            }

            $picked[] = $unknown->shift();
        }

        return array_map(fn (Student $student) => (int) $student->user_id, $picked);
    }
}
