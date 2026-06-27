<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'login_id' => 'MoCU/USR/'.fake()->unique()->numerify('####').'/'.fake()->numerify('##'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'normal_student',
            'registration_number' => null,
            'staff_id' => null,
            'account_status' => 'active',
            'enrollment_status' => 'active',
            'department' => null,
            'programme' => null,
            'year_of_study' => fake()->numberBetween(1, 4),
            'phone_number' => fake()->phoneNumber(),
            'must_change_password' => false,
            'notify_email_new_submission' => true,
            'notify_email_submission_reviewed' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function administrator(): static
    {
        return $this->state(function (): array {
            $login = 'MoCU/ADM/'.fake()->unique()->numerify('####').'/'.fake()->numerify('##');

            return [
                'role' => 'admin',
                'login_id' => $login,
                'staff_id' => $login,
                'registration_number' => null,
                'must_change_password' => false,
            ];
        });
    }

    public function coordinator(): static
    {
        return $this->state(function (): array {
            $login = 'MoCU/CRD/'.fake()->unique()->numerify('####').'/'.fake()->numerify('##');

            return [
                'role' => 'coordinator',
                'login_id' => $login,
                'staff_id' => $login,
                'registration_number' => null,
            ];
        });
    }

    public function hod(): static
    {
        return $this->state(function (): array {
            $login = 'MoCU/HOD/'.fake()->unique()->numerify('####').'/'.fake()->numerify('##');

            return [
                'role' => 'hod',
                'login_id' => $login,
                'staff_id' => $login,
                'registration_number' => null,
            ];
        });
    }

    public function supervisor(): static
    {
        return $this->state(function (): array {
            $login = 'MoCU/SUP/'.fake()->unique()->numerify('####').'/'.fake()->numerify('##');

            return [
                'role' => 'supervisor',
                'login_id' => $login,
                'staff_id' => $login,
                'registration_number' => null,
            ];
        });
    }

    public function student(string $subtype = 'normal_student'): static
    {
        $valid = ['project_student', 'research_student', 'normal_student'];

        return $this->state(function () use ($subtype, $valid): array {
            $role = in_array($subtype, $valid, true) ? $subtype : 'normal_student';
            $year = fake()->numerify('##');
            $num = fake()->unique()->numerify('####');
            $login = "MoCU/STU/{$num}/{$year}";
            $reg = "MoCU/REG/{$num}/{$year}";

            return [
                'role' => $role,
                'login_id' => $login,
                'registration_number' => $reg,
                'staff_id' => null,
                'year_of_study' => fake()->numberBetween(2, 4),
            ];
        });
    }
}
