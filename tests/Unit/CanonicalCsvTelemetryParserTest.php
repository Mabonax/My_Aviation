<?php

use App\Domains\Uas\Telemetry\Domain\Services\CanonicalCsvTelemetryParser;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

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

it('rejects malformed or ambiguous flight evidence', function (string $csv) {
    expect(fn () => (new CanonicalCsvTelemetryParser)->parse($csv))
        ->toThrow(ValidationException::class);
})->with([
    'no timezone' => str_replace('+02:00', '', canonicalTelemetryCsv()),
    'invalid date' => str_replace('2026-01-01', '2026-02-30', canonicalTelemetryCsv()),
    'reversed times' => str_replace('11:00:00', '09:00:00', canonicalTelemetryCsv()),
    'duplicate times' => str_replace('10:30:00', '10:00:00', canonicalTelemetryCsv()),
    'future times' => str_replace('2026-01-01', '2099-01-01', canonicalTelemetryCsv()),
    'bad coordinate' => str_replace('-25.8', '91', canonicalTelemetryCsv()),
    'infinite coordinate' => str_replace('-25.8', '1e999', canonicalTelemetryCsv()),
    'negative altitude' => str_replace(',250,', ',-1,', canonicalTelemetryCsv()),
    'mixed aircraft' => str_replace('250,SN-001', '250,SN-002', canonicalTelemetryCsv()),
    'no takeoff event' => str_replace('takeoff', 'sample', canonicalTelemetryCsv()),
    'two takeoffs' => str_replace('sample', 'takeoff', canonicalTelemetryCsv()),
    'no landing event' => str_replace('landing', 'sample', canonicalTelemetryCsv()),
    'bad header' => str_replace('recorded_at', 'timestamp', canonicalTelemetryCsv()),
    'NUL byte' => canonicalTelemetryCsv()."\0",
    'oversized' => str_repeat('x', CanonicalCsvTelemetryParser::MAX_BYTES + 1),
]);
