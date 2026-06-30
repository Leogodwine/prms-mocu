<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Support\AuditTrailInterpreter;
use PHPUnit\Framework\TestCase;

class AuditTrailInterpreterTest extends TestCase
{
    public function test_it_labels_known_actions(): void
    {
        $this->assertSame('User account created', AuditTrailInterpreter::actionLabel('admin.user_created'));
        $this->assertSame('Signed in', AuditTrailInterpreter::actionLabel('auth.login'));
    }

    public function test_it_humanizes_unknown_actions(): void
    {
        $this->assertSame('Custom Event', AuditTrailInterpreter::actionLabel('module.custom_event'));
    }

    public function test_it_summarizes_new_values(): void
    {
        $log = new AuditLog([
            'action' => 'admin.user_created',
            'new_values' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => 'student',
            ],
        ]);

        $summary = AuditTrailInterpreter::summarize($log);

        $this->assertStringContainsString('Name: Jane Doe', (string) $summary);
        $this->assertStringContainsString('Email: jane@example.com', (string) $summary);
        $this->assertStringContainsString('Role: student', (string) $summary);
    }

    public function test_it_formats_entity_labels(): void
    {
        $this->assertSame('Research project #12', AuditTrailInterpreter::entityLabel('ResearchProject', '12'));
        $this->assertSame('System', AuditTrailInterpreter::entityLabel('System', null));
    }
}
