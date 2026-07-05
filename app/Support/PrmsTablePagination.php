<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

final class PrmsTablePagination
{
    public const DEFAULT = 10;

    /** @var list<int> */
    public const OPTIONS = [10, 20, 30, 50];

    public static function perPage(Request $request, string $param = 'per_page'): int
    {
        $value = (int) $request->query($param, self::DEFAULT);

        return in_array($value, self::OPTIONS, true) ? $value : self::DEFAULT;
    }

    public static function needsControls(int $total, int $perPage = self::DEFAULT): bool
    {
        return $total > $perPage;
    }

    /**
     * @param  Collection<int, mixed>  $items
     */
    public static function paginateCollection(
        Collection $items,
        Request $request,
        string $pageName = 'page',
        ?string $path = null,
    ): LengthAwarePaginator {
        $perPage = self::perPage($request);
        $page = Paginator::resolveCurrentPage($pageName);
        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $path ?? $request->url(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ]
        );
    }
}
