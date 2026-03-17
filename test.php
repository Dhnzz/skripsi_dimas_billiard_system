<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$billing = \App\Models\Billing::latest()->first();
echo "Billing ID: {$billing->id}\n";
echo "Elapsed Seconds: {$billing->elapsed_seconds}\n";
echo "Package ID: ".($billing->package_id ?? 'null')."\n";
if ($billing->package) {
    echo "Package price: {$billing->package->price}\n";
    echo "Package duration: {$billing->package->duration_hours}\n";
    echo "Package type: {$billing->package->type}\n";
}
echo "Pricing ID: {$billing->pricing_id}\n";
if ($billing->pricing) {
    echo "PpH: {$billing->pricing->price_per_hour}\n";
}
echo "Current Total: {$billing->current_total}\n";
echo "Addon Total: {$billing->addon_total}\n";
