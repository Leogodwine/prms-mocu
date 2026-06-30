<?php

namespace Tests\Unit;

use App\Support\PrmsTablePagination;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class PrmsTablePaginationTest extends TestCase
{
    public function test_it_defaults_to_ten_rows(): void
    {
        $request = Request::create('/admin/users', 'GET');

        $this->assertSame(10, PrmsTablePagination::perPage($request));
    }

    public function test_it_honours_allowed_page_sizes(): void
    {
        $request = Request::create('/admin/users', 'GET', ['per_page' => '30']);

        $this->assertSame(30, PrmsTablePagination::perPage($request));
    }

    public function test_it_rejects_invalid_page_sizes(): void
    {
        $request = Request::create('/admin/users', 'GET', ['per_page' => '99']);

        $this->assertSame(10, PrmsTablePagination::perPage($request));
    }

    public function test_it_detects_when_controls_are_needed(): void
    {
        $this->assertFalse(PrmsTablePagination::needsControls(1));
        $this->assertTrue(PrmsTablePagination::needsControls(2));
    }
}
