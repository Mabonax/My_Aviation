<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aviation_overlay_sources')) {
            Schema::create('aviation_overlay_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('publisher');
                $table->string('source_url');
                $table->string('source_version');
                $table->date('effective_date')->nullable();
                $table->boolean('authoritative')->default(false);
                $table->text('usage_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('aviation_overlay_zones')) {
            Schema::create('aviation_overlay_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aviation_overlay_source_id')->constrained('aviation_overlay_sources')->cascadeOnDelete();
                $table->string('zone_type');
                $table->string('name');
                $table->string('identifier')->nullable();
                $table->string('status')->default('active');
                $table->json('geometry');
                $table->text('operational_notes')->nullable();
                $table->timestamps();
                $table->index(['zone_type', 'status']);
            });
        }

        $sourceIds = [];

        foreach ($this->sources() as $source) {
            DB::table('aviation_overlay_sources')->updateOrInsert(
                ['name' => $source['name']],
                [...$source, 'created_at' => now(), 'updated_at' => now()]
            );

            $sourceIds[$source['name']] = DB::table('aviation_overlay_sources')->where('name', $source['name'])->value('id');
        }

        foreach ($this->zones($sourceIds) as $zone) {
            DB::table('aviation_overlay_zones')->updateOrInsert(
                ['name' => $zone['name'], 'zone_type' => $zone['zone_type']],
                [...$zone, 'geometry' => json_encode($zone['geometry']), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aviation_overlay_zones');
        Schema::dropIfExists('aviation_overlay_sources');
    }

    private function sources(): array
    {
        return [
            [
                'name' => 'SACAA RPAS industry information',
                'publisher' => 'South African Civil Aviation Authority',
                'source_url' => 'https://www.caa.co.za/industry-information/rpas/',
                'source_version' => 'Checked 2026-09-10',
                'effective_date' => '2026-09-10',
                'authoritative' => true,
                'usage_notes' => 'RPAS regulatory entry point; not a standalone geospatial dataset.',
            ],
            [
                'name' => 'SACAA aeronautical charts and AIP amendments',
                'publisher' => 'South African Civil Aviation Authority',
                'source_url' => 'https://www.caa.co.za/industry-information/aeronautical-charts/',
                'source_version' => 'Checked 2026-09-10',
                'effective_date' => '2026-09-10',
                'authoritative' => true,
                'usage_notes' => 'Official chart/AIP amendment entry point for aeronautical context.',
            ],
            [
                'name' => 'ATNS AIM static data products',
                'publisher' => 'Air Traffic and Navigation Services SOC Ltd',
                'source_url' => 'https://aim.atns.co.za/',
                'source_version' => 'Checked 2026-09-10',
                'effective_date' => '2026-09-10',
                'authoritative' => true,
                'usage_notes' => 'AIM source boundary for controlled/restricted/prohibited airspace imports.',
            ],
        ];
    }

    private function zones(array $sourceIds): array
    {
        return [
            $this->zone($sourceIds['SACAA aeronautical charts and AIP amendments'], 'aerodrome', 'Sample aerodrome review area', 'AD-SAMPLE', [['latitude' => -25.9385, 'longitude' => 28.1400], ['latitude' => -25.9385, 'longitude' => 28.1800], ['latitude' => -25.9700, 'longitude' => 28.1800], ['latitude' => -25.9700, 'longitude' => 28.1400]], 'Placeholder geometry until official chart data is imported.'),
            $this->zone($sourceIds['ATNS AIM static data products'], 'controlled_airspace', 'Sample controlled airspace review area', 'CTR-SAMPLE', [['latitude' => -25.9900, 'longitude' => 28.1000], ['latitude' => -25.9900, 'longitude' => 28.1700], ['latitude' => -26.0400, 'longitude' => 28.1700], ['latitude' => -26.0400, 'longitude' => 28.1000]], 'Placeholder geometry requiring ATNS/SACAA source import before operational use.'),
            $this->zone($sourceIds['ATNS AIM static data products'], 'restricted_airspace', 'Sample restricted airspace review area', 'FAR-SAMPLE', [['latitude' => -26.0200, 'longitude' => 28.1100], ['latitude' => -26.0200, 'longitude' => 28.1500], ['latitude' => -26.0500, 'longitude' => 28.1500], ['latitude' => -26.0500, 'longitude' => 28.1100]], 'Placeholder restricted-area geometry.'),
            $this->zone($sourceIds['ATNS AIM static data products'], 'prohibited_airspace', 'Sample prohibited airspace review area', 'FAP-SAMPLE', [['latitude' => -25.9800, 'longitude' => 28.1850], ['latitude' => -25.9800, 'longitude' => 28.2100], ['latitude' => -26.0100, 'longitude' => 28.2100], ['latitude' => -26.0100, 'longitude' => 28.1850]], 'Placeholder prohibited-area geometry.'),
            $this->zone($sourceIds['SACAA RPAS industry information'], 'strategic_area', 'Sample strategic review area', 'STRAT-SAMPLE', [['latitude' => -25.9600, 'longitude' => 28.0900], ['latitude' => -25.9600, 'longitude' => 28.1250], ['latitude' => -25.9900, 'longitude' => 28.1250], ['latitude' => -25.9900, 'longitude' => 28.0900]], 'Internal strategic review placeholder linked to RPAS source boundary.'),
            $this->zone($sourceIds['SACAA RPAS industry information'], 'approved_operating_zone', 'Sample approved operating zone', 'AOZ-SAMPLE', [['latitude' => -26.0000, 'longitude' => 28.1200], ['latitude' => -26.0000, 'longitude' => 28.1450], ['latitude' => -26.0250, 'longitude' => 28.1450], ['latitude' => -26.0250, 'longitude' => 28.1200]], 'Operator-approved zone placeholder; must be replaced by operator/SACAA evidence before release use.'),
        ];
    }

    private function zone(int $sourceId, string $type, string $name, string $identifier, array $polygon, string $notes): array
    {
        return [
            'aviation_overlay_source_id' => $sourceId,
            'zone_type' => $type,
            'name' => $name,
            'identifier' => $identifier,
            'status' => 'active',
            'geometry' => ['type' => 'polygon', 'points' => $polygon],
            'operational_notes' => $notes,
        ];
    }
};
