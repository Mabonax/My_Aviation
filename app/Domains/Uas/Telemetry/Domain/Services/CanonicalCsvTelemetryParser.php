<?php

namespace App\Domains\Uas\Telemetry\Domain\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

/**
 * YAW CSV v1: one flight, explicitly delimited by takeoff and landing events.
 * Native manufacturer logs need separate, validated adapters.
 */
class CanonicalCsvTelemetryParser
{
    public const MAX_BYTES = 2097152;

    public function parse(string $csv): array
    {
        if ($csv === '' || strlen($csv) > self::MAX_BYTES || str_contains($csv, "\0")) {
            $this->fail('The CSV must be non-empty, contain no NUL bytes and be at most 2 MiB.');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);

        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            $expected = ['recorded_at', 'latitude', 'longitude', 'altitude_ft', 'aircraft_serial', 'event'];
            if ($header !== $expected) {
                $this->fail('Expected CSV header: '.implode(',', $expected));
            }

            $points = [];
            $serial = null;
            $previous = null;
            $events = [];
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== count($expected) || count($points) >= 10000) {
                    $this->fail('Each row must have six fields; at most 10,000 samples are allowed.');
                }
                [$timestamp, $latitude, $longitude, $altitude, $aircraftSerial, $event] = $row;
                if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D', $timestamp)) {
                    $this->fail('Sample times must be ISO 8601 timestamps with an explicit timezone.');
                }
                $time = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP', $timestamp);
                $errors = DateTimeImmutable::getLastErrors();
                if ($time === false || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
                    $this->fail('Invalid sample timestamp.');
                }
                if ($previous !== null && $time <= $previous) {
                    $this->fail('Sample timestamps must be strictly increasing.');
                }
                if ($time > new DateTimeImmutable('now')) {
                    $this->fail('Flight samples cannot be in the future.');
                }
                foreach ([$latitude, $longitude, $altitude] as $number) {
                    if (! is_numeric($number) || ! is_finite((float) $number)) {
                        $this->fail('Coordinates and altitude must be finite numbers.');
                    }
                }
                if (abs((float) $latitude) > 90 || abs((float) $longitude) > 180 || (float) $altitude < 0 || (float) $altitude > 100000) {
                    $this->fail('Coordinates are out of range or altitude is outside 0–100,000 ft.');
                }
                if ($aircraftSerial === '' || strlen($aircraftSerial) > 255 || ($serial !== null && $serial !== $aircraftSerial)) {
                    $this->fail('All samples must identify the same non-empty aircraft serial.');
                }
                if (! in_array($event, ['takeoff', 'sample', 'landing'], true)) {
                    $this->fail('Events must be takeoff, sample or landing.');
                }
                $serial = $aircraftSerial;
                $previous = $time;
                $events[] = $event;
                $points[] = [
                    'recorded_at' => $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sP'),
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                    'altitude_ft' => (float) $altitude,
                ];
            }
            if (count($points) < 2 || $events[0] !== 'takeoff' || end($events) !== 'landing'
                || count(array_filter($events, fn ($event) => $event === 'takeoff')) !== 1
                || count(array_filter($events, fn ($event) => $event === 'landing')) !== 1) {
                $this->fail('A single flight must begin with takeoff and end with landing.');
            }

            return [
                'format' => 'yaw_csv_v1',
                'aircraft_serial' => $serial,
                'actual_takeoff_at' => $points[0]['recorded_at'],
                'actual_landing_at' => $points[count($points) - 1]['recorded_at'],
                'point_count' => count($points),
                'points' => $points,
            ];
        } finally {
            fclose($stream);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
