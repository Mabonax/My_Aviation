<?php

use App\Domains\Uas\Notifications\Application\Actions\PlanCertificateExpiryNotifications;
use App\Domains\Uas\Aircraft\Application\Actions\ImportAircraftCatalogue;
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
Artisan::command('uas:import-aircraft-catalogue {path?}', function (?string $path = null) {
    $summary = app(ImportAircraftCatalogue::class)->execute($path);

    $this->info("Aircraft catalogue import complete from {$summary['source_path']}");
    $this->line("Manufacturers created: {$summary['manufacturers_created']}");
    $this->line("Models created: {$summary['models_created']}");
    $this->line("Models updated: {$summary['models_updated']}");
    $this->line("Models unchanged: {$summary['models_unchanged']}");
    $this->line("Rows rejected: {$summary['rows_rejected']}");

    foreach ($summary['validation_errors'] as $error) {
        $this->warn('Row '.$error['row'].': '.json_encode($error['errors']));
    }
})->purpose('Import the governed UAS aircraft model catalogue from JSON or CSV');

Schedule::command('uas:plan-expiry-notifications')->dailyAt('06:00');
