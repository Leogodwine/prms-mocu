<?php

namespace Tests\Unit;

use App\Support\PrmsSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PrmsSmsValidationTest extends TestCase
{
    public function test_rejects_empty_required_phone_field(): void
    {
        $request = new Request(['phone_number' => '']);
        $validator = Validator::make($request->all(), []);

        PrmsSms::validatePhoneField($validator, $request);

        $this->assertTrue($validator->errors()->has('phone_number'));
        $this->assertSame(PrmsSms::requiredPhoneMessage(), $validator->errors()->first('phone_number'));
    }

    public function test_rejects_invalid_required_phone_field(): void
    {
        $request = new Request(['phone_number' => '12345']);
        $validator = Validator::make($request->all(), []);

        PrmsSms::validatePhoneField($validator, $request);

        $this->assertTrue($validator->errors()->has('phone_number'));
        $this->assertSame(PrmsSms::invalidPhoneMessage(), $validator->errors()->first('phone_number'));
    }

    public function test_normalizes_valid_required_phone_field(): void
    {
        $request = new Request(['phone_number' => '255738234345']);
        $validator = Validator::make($request->all(), []);

        PrmsSms::validatePhoneField($validator, $request);

        $this->assertFalse($validator->errors()->has('phone_number'));
        $this->assertSame('+255738234345', $request->input('phone_number'));
    }
}
