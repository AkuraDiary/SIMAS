<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;

Schedule::call(function () {
    DB::table('notifications')
        ->where('created_at', '<', now()->subDays(7))
        ->delete();
})->daily();
