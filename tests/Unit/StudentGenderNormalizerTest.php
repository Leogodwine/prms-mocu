<?php

namespace Tests\Unit;

use App\Support\StudentGenderNormalizer;
use Tests\TestCase;

class StudentGenderNormalizerTest extends TestCase
{
    public function test_normalizes_common_values(): void
    {
        $this->assertSame('male', StudentGenderNormalizer::normalize('Male'));
        $this->assertSame('female', StudentGenderNormalizer::normalize('F'));
        $this->assertSame('male', StudentGenderNormalizer::normalize('m'));
        $this->assertNull(StudentGenderNormalizer::normalize(''));
        $this->assertNull(StudentGenderNormalizer::normalize('nonbinary'));
    }
}
