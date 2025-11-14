<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create all necessary roles
$roles = ['admin', 'seller', 'buyer'];

foreach ($roles as $roleName) {
    $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
    echo "✅ Role '{$roleName}' exists\n";
}

echo "\n✅ All roles created successfully!\n";
