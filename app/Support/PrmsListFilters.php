<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Session-flash list filters: survive one redirect after Apply, then clear on refresh.
 */
final class PrmsListFilters
{
    public static function sessionKey(string $page): string
    {
        return 'prms.list_filters.'.$page;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array{filters: array<string, mixed>, redirect: ?RedirectResponse}
     */
    public static function resolve(
        Request $request,
        string $page,
        array $defaults,
        string $redirectRoute,
        array $routeParams = [],
        ?callable $sanitize = null,
    ): array {
        $key = self::sessionKey($page);

        if ($request->boolean('reset_filters')) {
            session()->forget($key);

            return [
                'filters' => $defaults,
                'redirect' => redirect()->route($redirectRoute, $routeParams),
            ];
        }

        if ($request->isMethod('post') && $request->input('_filter_action') === 'apply') {
            $incoming = $request->except(['_token', '_filter_action', 'reset_filters']);
            $filters = array_replace($defaults, array_intersect_key($incoming, $defaults));

            if ($sanitize !== null) {
                $filters = $sanitize($filters);
            }

            session()->flash($key, $filters);

            return [
                'filters' => $filters,
                'redirect' => redirect()->route($redirectRoute, $routeParams),
            ];
        }

        $flashed = $request->session()->get($key);

        return [
            'filters' => is_array($flashed) ? array_replace($defaults, $flashed) : $defaults,
            'redirect' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public static function peek(Request $request, string $page, array $defaults): array
    {
        $flashed = $request->session()->get(self::sessionKey($page));

        return is_array($flashed) ? array_replace($defaults, $flashed) : $defaults;
    }

    public static function resetUrl(string $redirectRoute, array $routeParams = []): string
    {
        return route($redirectRoute, array_merge($routeParams, ['reset_filters' => 1]));
    }
}
