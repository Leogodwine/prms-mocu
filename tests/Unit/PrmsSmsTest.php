<?php

namespace Tests\Unit;

use App\Support\PrmsSms;
use PHPUnit\Framework\TestCase;

class PrmsSmsTest extends TestCase
{
    public function test_normalizes_local_tanzanian_mobile_number(): void
    {
        $this->assertSame('+255712345678', PrmsSms::normalizePhone('0712345678'));
    }

    public function test_normalizes_international_format(): void
    {
        $this->assertSame('+255712345678', PrmsSms::normalizePhone('+255 712 345 678'));
    }

    public function test_normalizes_bare_e164_digits_without_plus_prefix(): void
    {
        $this->assertSame('+255738234345', PrmsSms::normalizePhone('255738234345'));
    }

    public function test_rejects_invalid_numbers(): void
    {
        $this->assertNull(PrmsSms::normalizePhone('12345'));
        $this->assertNull(PrmsSms::normalizePhone('441234567890'));
        $this->assertNull(PrmsSms::normalizePhone(''));
    }

    public function test_truncates_long_sms_body(): void
    {
        $body = PrmsSms::formatBody('Title', str_repeat('x', 600), 'Footer');

        $this->assertLessThanOrEqual(PrmsSms::MAX_LENGTH, mb_strlen($body));
    }
}
