<?php

use App\Domains\Uas\Telemetry\Domain\Services\CanonicalCsvTelemetryParser;
use Illuminate\Validation\ValidationException;

function canonicalTelemetryCsv(): string
{
    return "recorded_at,latitude,longitude,altitude_ft,aircraft_serial,event\n"
        ."2026-01-01T10:00:00+02:00,-25.9,28.1,0,SN-001,takeoff\n"
        ."2026-01-01T10:30:00+02:00,-25.8,28.2,250,SN-001,sample\n"
        ."2026-01-01T11:00:00+02:00,-25.9,28.1,0,SN-001,landing\n";
}

it('normalises a single explicitly delimited flight into UTC samples', function () {
    $result = (new CanonicalCsvTelemetryParser)->parse(canonicalTelemetryCsv());
    expect($result['actual_takeoff_at'])->toBe('2026-01-01T08:00:00+00:00')
        ->and($result['actual_landing_at'])->toBe('2026-01-01T09:00:00+00:00')
        ->and($result['aircraft_serial'])->toBe('SN-001')
        ->and($result['point_count'])->toBe(3);
});

it('rejects malformed or ambiguous flight evidence', function (Closure $mutate) {
    expect(fn () => (new CanonicalCsvTelemetryParser)->parse($mutate(canonicalTelemetryCsv())))
        ->toThrow(ValidationException::class);
})->with([
    'no timezone' => fn ($csv) => str_replace('+02:00', '', $csv),
    'invalid date' => fn ($csv) => str_replace('2026-01-01', '2026-02-30', $csv),
    'reversed times' => fn ($csv) => str_replace('11:00:00', '09:00:00', $csv),
    'duplicate times' => fn ($csv) => str_replace('10:30:00', '10:00:00', $csv),
    'future times' => fn ($csv) => str_replace('2026-01-01', '2099-01-01', $csv),
    'bad coordinate' => fn ($csv) => str_replace('-25.8', '91', $csv),
    'infinite coordinate' => fn ($csv) => str_replace('-25.8', '1e999', $csv),
    'negative altitude' => fn ($csv) => str_replace(',250,', ',-1,', $csv),
    'mixed aircraft' => fn ($csv) => str_replace('250,SN-001', '250,SN-002', $csv),
    'no takeoff event' => fn ($csv) => str_replace('takeoff', 'sample', $csv),
    'two takeoffs' => fn ($csv) => str_replace('sample', 'takeoff', $csv),
    'no landing event' => fn ($csv) => str_replace('landing', 'sample', $csv),
    'bad header' => fn ($csv) => str_replace('recorded_at', 'timestamp', $csv),
    'NUL byte' => fn ($csv) => $csv."\0",
    'oversized' => fn ($csv) => str_repeat('x', CanonicalCsvTelemetryParser::MAX_BYTES + 1),
]);
