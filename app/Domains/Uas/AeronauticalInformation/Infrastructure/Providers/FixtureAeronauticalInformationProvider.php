<?php

namespace App\Domains\Uas\AeronauticalInformation\Infrastructure\Providers;

use App\Domains\Uas\AeronauticalInformation\Domain\Enums\SourceClassification;

class FixtureAeronauticalInformationProvider extends ManualAeronauticalInformationProvider
{
    public function key(): string
    {
        return 'fixture';
    }

    public function classification(): SourceClassification
    {
        return SourceClassification::TestFixture;
    }
}
