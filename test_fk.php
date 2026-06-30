<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking foreign key constraints...\n";
$constraints = DB::select("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");

foreach($constraints as $c) {
    if($c->TABLE_NAME == 'services' && $c->COLUMN_NAME == 'sub_category_id') {
        echo "Found constraint: " . $c->CONSTRAINT_NAME . " on " . $c->TABLE_NAME . "." . $c->COLUMN_NAME . " referencing " . $c->REFERENCED_TABLE_NAME . "." . $c->REFERENCED_COLUMN_NAME . "\n";
    }
}

echo "\nDone\n";
