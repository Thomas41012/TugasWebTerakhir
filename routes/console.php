<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('supply-chain:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();