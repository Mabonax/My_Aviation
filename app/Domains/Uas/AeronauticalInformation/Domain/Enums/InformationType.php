<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Enums;

enum InformationType: string
{
    case Notam = 'NOTAM';
    case Aip = 'AIP';
    case AipAmendment = 'AIP_AMENDMENT';
    case AipSupplement = 'AIP_SUPPLEMENT';
    case Aic = 'AIC';
    case Airac = 'AIRAC';
    case Pib = 'PIB';
    case Chart = 'CHART';
    case Metar = 'METAR';
    case Taf = 'TAF';
    case Sigmet = 'SIGMET';
}
