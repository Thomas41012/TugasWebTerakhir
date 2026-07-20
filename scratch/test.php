<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$c = app(Livewire\Volt\ComponentResolver::class)->resolve('api-status-monitor', [resource_path('views/livewire')]);
echo "Class: $c\n";
print_r(get_class_vars($c));
