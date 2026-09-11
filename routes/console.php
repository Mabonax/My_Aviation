<?php

use App\Domains\Uas\Notifications\Application\Actions\PlanCertificateExpiryNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Artisan::command('uas:plan-expiry-notifications', function () {
    $created = app(PlanCertificateExpiryNotifications::class)->execute();

    $this->info("Planned {$created} UAS compliance notifications.");
})->purpose('Plan due UAS certificate expiry notifications');

Schedule::command('uas:plan-expiry-notifications')->dailyAt('06:00');
