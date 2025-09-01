<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// Schedule::command('category:reset-slots')->everyMinute();
// Schedule::command('product:decrement-reservation-slots')->everyMinute();

// Data Cleaning

Schedule::command('daily-attendance:clean')->dailyAt('09:00');
Schedule::command('lalamove:update-meta')->monthly();