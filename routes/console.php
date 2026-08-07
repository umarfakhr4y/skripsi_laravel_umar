<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:check-absensi-harian')->dailyAt('17:00');
Schedule::command('app:check-tugas-terlambat')->dailyAt('08:00');

//php artisan app:check-absensi-harian
//php artisan app:check-tugas-terlambat
//php artisan schedule:work

