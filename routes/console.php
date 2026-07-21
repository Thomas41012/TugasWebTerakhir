<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('supply-chain:sync')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();