<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\DTOs;

final readonly class ProviderRequest
{
    // Payload supports validated webhook/AIXM/PIB/MET envelopes in future adapters.
    public function __construct(public ?string $path = null, public ?array $payload = null, public ?string $cursor = null) {}
}
