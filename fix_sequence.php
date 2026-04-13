<?php

use Illuminate\Support\Facades\DB;

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

// Bootstrap Laravel to make Facades work
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

try {
    // Reset the sequence for services table
    DB::statement("SELECT setval('services_id_seq', (SELECT COALESCE(MAX(id), 0) FROM services) + 1)");
    echo "✅ Services sequence reset successfully!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
