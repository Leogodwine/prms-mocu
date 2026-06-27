<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Check all supervisor users
$supervisors = User::where('role', 'supervisor')->get(['id', 'name', 'email', 'role', 'account_status', 'must_change_password']);

echo "=== Supervisor Users ===" . PHP_EOL;
foreach ($supervisors as $u) {
    echo "ID: {$u->id} | {$u->email} | role={$u->role} | status={$u->account_status} | must_change_pw=" . ($u->must_change_password ? 'YES' : 'no') . PHP_EOL;
}

// Check middleware registration
echo PHP_EOL . "=== Checking Bootstrap/App for middleware alias ===" . PHP_EOL;
$bootstrap = file_get_contents(__DIR__ . '/bootstrap/app.php');
if (strpos($bootstrap, 'EnsureUserRole') !== false || strpos($bootstrap, 'role') !== false) {
    echo "Found 'role' or 'EnsureUserRole' reference in bootstrap/app.php" . PHP_EOL;
} else {
    echo "No 'role' middleware reference in bootstrap/app.php" . PHP_EOL;
}

// Check if middleware alias is defined somewhere
$files = [
    __DIR__ . '/bootstrap/app.php',
    __DIR__ . '/app/Http/Kernel.php',
];
foreach ($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        echo PHP_EOL . "--- " . basename($f) . " ---" . PHP_EOL;
        echo $content . PHP_EOL;
    }
}
