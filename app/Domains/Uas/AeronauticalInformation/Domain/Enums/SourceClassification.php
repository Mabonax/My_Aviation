<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Enums;

enum SourceClassification: string
{
    case OfficialLive = 'official_live';
    case OfficialPublication = 'official_publication';
    case OfficialSummary = 'official_summary';
    case ImportedReference = 'imported_reference';
    case ManuallyVerified = 'manually_verified';
    case ThirdParty = 'third_party';
    case TestFixture = 'test_fixture';
}
