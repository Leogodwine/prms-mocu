<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Support\PrmsAccountIdentifierFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrmsAccountIdentifierFormatDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_id_requires_registered_department_code(): void
    {
        Department::factory()->create([
            'department_code' => 'ACC',
            'department_name' => 'Accounting and Finance',
        ]);

        $this->assertTrue(PrmsAccountIdentifierFormat::isValidStaffId('MoCU/ACC/231/20'));
        $this->assertFalse(PrmsAccountIdentifierFormat::isValidStaffId('MoCU/UNKNOWN/231/20'));
    }

    public function test_registration_number_requires_registered_programme_code(): void
    {
        $department = Department::factory()->create(['department_code' => 'CICT']);

        Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'programme_name' => 'Bachelor of Business ICT',
        ]);

        $this->assertTrue(PrmsAccountIdentifierFormat::isValidRegistrationNumber('MoCU/BBICT/231/20'));
        $this->assertFalse(PrmsAccountIdentifierFormat::isValidRegistrationNumber('MoCU/UNKNOWN/231/20'));
    }

    public function test_staff_id_must_match_selected_department_label(): void
    {
        Department::factory()->create([
            'department_code' => 'ACC',
            'department_name' => 'Accounting and Finance',
        ]);
        Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        $this->assertTrue(
            PrmsAccountIdentifierFormat::staffIdMatchesDepartment('MoCU/ACC/231/20', 'ACC')
        );
        $this->assertTrue(
            PrmsAccountIdentifierFormat::staffIdMatchesDepartment('MoCU/ACC/231/20', 'Accounting and Finance')
        );
        $this->assertFalse(
            PrmsAccountIdentifierFormat::staffIdMatchesDepartment('MoCU/ACC/231/20', 'CICT')
        );
    }

    public function test_registration_number_must_match_selected_programme_label(): void
    {
        $department = Department::factory()->create(['department_code' => 'CICT']);

        Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'programme_name' => 'Bachelor of Business ICT',
        ]);

        $this->assertTrue(
            PrmsAccountIdentifierFormat::registrationMatchesProgramme('MoCU/BBICT/231/20', 'BBICT')
        );
        $this->assertFalse(
            PrmsAccountIdentifierFormat::registrationMatchesProgramme('MoCU/BBICT/231/20', 'BSDS')
        );
    }
}
