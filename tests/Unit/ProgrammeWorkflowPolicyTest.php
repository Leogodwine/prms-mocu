<?php

namespace Tests\Unit;

use App\Enums\ProgramOutputType;
use App\Support\ProgrammeWorkflowPolicy;
use Tests\TestCase;

class ProgrammeWorkflowPolicyTest extends TestCase
{
    public function test_certificate_payload_is_forced_to_none(): void
    {
        $payload = ProgrammeWorkflowPolicy::applyToProgrammePayload([
            'programme_code' => 'CIT',
            'academic_level' => 'certificate',
            'output_type' => ProgramOutputType::ResearchOnly->value,
            'is_project_eligible' => true,
        ]);

        $this->assertSame(ProgramOutputType::None->value, $payload['output_type']);
        $this->assertFalse($payload['is_project_eligible']);
    }

    public function test_non_dbi_ct_diploma_payload_is_forced_to_none(): void
    {
        $payload = ProgrammeWorkflowPolicy::applyToProgrammePayload([
            'programme_code' => 'DHRM',
            'academic_level' => 'diploma',
            'output_type' => ProgramOutputType::ProjectOnly->value,
            'is_project_eligible' => true,
        ]);

        $this->assertSame(ProgramOutputType::None->value, $payload['output_type']);
        $this->assertFalse($payload['is_project_eligible']);
    }

    public function test_dbi_ct_diploma_payload_is_project_only(): void
    {
        $payload = ProgrammeWorkflowPolicy::applyToProgrammePayload([
            'programme_code' => 'DBICT',
            'academic_level' => 'diploma',
            'output_type' => ProgramOutputType::None->value,
            'is_project_eligible' => false,
        ]);

        $this->assertSame(ProgramOutputType::ProjectOnly->value, $payload['output_type']);
        $this->assertTrue($payload['is_project_eligible']);
    }
}
