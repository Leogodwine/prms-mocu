<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminConfigurationController extends Controller
{
    public function index(): View
    {
        $configs = SystemConfiguration::query()->orderBy('category')->orderBy('config_key')->get();

        return view('admin.configuration', [
            'configs' => $configs,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'configs' => ['required', 'array'],
        ]);

        foreach ($validated['configs'] as $key => $value) {
            SystemConfiguration::query()->updateOrCreate(
                ['config_key' => $key],
                [
                    'config_value' => is_array($value) ? json_encode($value) : (string) $value,
                    'config_type' => 'string',
                    'category' => 'general',
                ]
            );
        }

        Audit::log($request, 'admin.configuration_updated', 'SystemConfiguration');

        return back()->with('status', 'System configuration updated.');
    }
}

