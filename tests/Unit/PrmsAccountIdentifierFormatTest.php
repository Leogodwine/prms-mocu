<?php

namespace Tests\Unit;

use App\Support\PrmsAccountIdentifierFormat;
use PHPUnit\Framework\TestCase;

class PrmsAccountIdentifierFormatTest extends TestCase
{
    public function test_parses_staff_id_example(): void
    {
        $parsed = PrmsAccountIdentifierFormat::parse('MoCU/ACC/231/20');

        $this->assertNotNull($parsed);
        $this->assertSame('ACC', $parsed['code']);
        $this->assertSame('231', $parsed['number']);
        $this->assertSame('20', $parsed['year']);
        $this->assertSame('MoCU/ACC/231/20', $parsed['normalized']);
    }

    public function test_parses_registration_number_example(): void
    {
        $parsed = PrmsAccountIdentifierFormat::parse('MoCU/BBICT/231/20');

        $this->assertNotNull($parsed);
        $this->assertSame('BBICT', $parsed['code']);
        $this->assertSame('MoCU/BBICT/231/20', $parsed['normalized']);
    }

    public function test_rejects_wrong_prefix_casing(): void
    {
        $this->assertNull(PrmsAccountIdentifierFormat::parse('mocu/BBICT/231/20'));
        $this->assertNull(PrmsAccountIdentifierFormat::parse('MOCU/BBICT/231/20'));
        $this->assertNull(PrmsAccountIdentifierFormat::parse('Mocu/BBICT/231/20'));
    }

    public function test_rejects_lowercase_code_segment(): void
    {
        $this->assertNull(PrmsAccountIdentifierFormat::parse('MoCU/bbict/231/20'));
        $this->assertNull(PrmsAccountIdentifierFormat::parse('MoCU/acc/231/20'));
    }

    public function test_rejects_year_that_is_not_two_digits(): void
    {
        $this->assertNull(PrmsAccountIdentifierFormat::parse('MoCU/BBICT/231/2020'));
        $this->assertNull(PrmsAccountIdentifierFormat::parse('MoCU/BBICT/231/2'));
    }

    public function test_rejects_invalid_formats(): void
    {
        $this->assertFalse(PrmsAccountIdentifierFormat::hasValidStaffIdFormat('MoCU/ACC/231'));
        $this->assertFalse(PrmsAccountIdentifierFormat::hasValidStaffIdFormat('ACC/231/20'));
        $this->assertFalse(PrmsAccountIdentifierFormat::hasValidRegistrationNumberFormat('MoCU/STAFF/2024/001'));
        $this->assertFalse(PrmsAccountIdentifierFormat::hasValidRegistrationNumberFormat('MoCU/BBICT/231/2020'));
    }

    public function test_accepts_hyphenated_programme_code_format(): void
    {
        $this->assertTrue(PrmsAccountIdentifierFormat::hasValidRegistrationNumberFormat('MoCU/PGD-AF/15/24'));
        $this->assertSame('PGD-AF', PrmsAccountIdentifierFormat::parsedProgrammeCode('MoCU/PGD-AF/15/24'));
    }

    public function test_admin_legacy_identifier_is_allowed(): void
    {
        $this->assertTrue(PrmsAccountIdentifierFormat::isValidAdminIdentifier('MoCU/ADMIN/001'));
        $this->assertTrue(PrmsAccountIdentifierFormat::isValidAdminIdentifier('MoCU/ADMIN/001/26'));
        $this->assertFalse(PrmsAccountIdentifierFormat::isValidAdminIdentifier('MoCU/admin/001'));
    }
}
