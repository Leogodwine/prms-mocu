<?php

namespace App\Support;

final class PrmsCopyright
{
    public static function line(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $university = (string) config('prms.copyright.university', 'Moshi Co-operative University');
        $system = (string) config('prms.copyright.system_name', 'project and research management system');
        $product = (string) config('app.name', 'MoCU-PRMS');

        return sprintf(
            '© %d %s - %s · %s all rights reserved',
            $year,
            $university,
            $system,
            $product,
        );
    }
}
