<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('streams:sync')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
