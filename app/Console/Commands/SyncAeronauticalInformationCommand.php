<?php

namespace App\Console\Commands;

use App\Domains\Uas\AeronauticalInformation\Application\Actions\SyncAeronauticalInformation;
use App\Domains\Uas\AeronauticalInformation\Domain\DTOs\ProviderRequest;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncAeronauticalInformationCommand extends Command
{
    protected $signature = 'uas:sync-aeronautical-information {provider=manual} {--path= : Local JSON dataset file}';

    protected $description = 'Import source-preserving aeronautical references through a registered provider; no live feed is assumed';

    public function handle(SyncAeronauticalInformation $sync): int
    {
        try {
            $result = $sync->execute($this->argument('provider'), new ProviderRequest(path: $this->option('path')));
            $this->info("Sync {$result->id}: {$result->records_received} received, {$result->records_created} created, {$result->records_updated} superseded.");
            $this->warn($result->usable_for_release ? 'Operational coverage must still pass mission freshness checks.' : 'Reference/test metadata only. This dataset cannot authorize operational release.');

            return self::SUCCESS;
        } catch (ValidationException $error) {
            $this->error(collect($error->errors())->flatten()->implode(' '));
        } catch (Throwable) {
            $this->error('Synchronization failed. No partial dataset was accepted; see the provider sync register.');
        }

        return self::FAILURE;
    }
}
