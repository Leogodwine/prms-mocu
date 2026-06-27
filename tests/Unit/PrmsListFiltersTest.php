<?php

namespace Tests\Unit;

use App\Support\PrmsListFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

class PrmsListFiltersTest extends TestCase
{
    public function test_apply_flashes_filters_for_one_request_only(): void
    {
        $defaults = ['q' => '', 'type' => ''];

        $applyRequest = Request::create('/login', 'POST', [
            '_filter_action' => 'apply',
            'q' => 'hello',
            'type' => 'proposal',
        ]);
        $applyRequest->setLaravelSession($this->app['session.store']);

        $apply = PrmsListFilters::resolve($applyRequest, 'demo', $defaults, 'login');
        $this->assertNotNull($apply['redirect']);
        $this->assertSame('hello', $apply['filters']['q']);

        $session = $this->app['session.store'];
        $session->ageFlashData();

        $firstGet = Request::create('/login', 'GET');
        $firstGet->setLaravelSession($session);
        $first = PrmsListFilters::resolve($firstGet, 'demo', $defaults, 'login');
        $this->assertNull($first['redirect']);
        $this->assertSame('hello', $first['filters']['q']);

        $session->ageFlashData();

        $refreshGet = Request::create('/login', 'GET');
        $refreshGet->setLaravelSession($session);
        $refresh = PrmsListFilters::resolve($refreshGet, 'demo', $defaults, 'login');
        $this->assertSame('', $refresh['filters']['q']);
    }
}
