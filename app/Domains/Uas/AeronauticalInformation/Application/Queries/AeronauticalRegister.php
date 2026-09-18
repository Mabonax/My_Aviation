<?php

namespace App\Domains\Uas\AeronauticalInformation\Application\Queries;

use App\Domains\Uas\AeronauticalInformation\Domain\Enums\InformationType;
use App\Domains\Uas\AeronauticalInformation\Domain\Models\AeronauticalInformationItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AeronauticalRegister
{
    public function __construct(private readonly ProviderHealth $health) {}

    public function execute(array $filters, User $actor): array
    {
        Gate::forUser($actor)->authorize('viewAny', AeronauticalInformationItem::class);
        $query = AeronauticalInformationItem::query()->with('source');
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q->where('title', 'like', $search)->orWhere('source_identifier', 'like', $search)->orWhere('summary', 'like', $search));
        }
        if (! empty($filters['type'])) {
            $query->where('information_type', $filters['type']);
        }
        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }
        $status = $filters['status'] ?? 'current';
        if ($status === 'superseded') {
            $query->whereNotNull('superseded_at');
        } elseif ($status !== 'all') {
            $query->whereNull('superseded_at');
            if ($status !== 'current') {
                $query->where('status', $status);
            } else {
                $query->where('status', 'active');
            }
        }
        if (($filters['validity'] ?? '') === 'active') {
            $query->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', now()));
        } elseif (($filters['validity'] ?? '') === 'expired') {
            $query->where('effective_until', '<', now());
        } elseif (($filters['validity'] ?? '') === 'future') {
            $query->where('effective_from', '>', now());
        }

        return ['items' => $query->orderByDesc('id')->paginate(25)->withQueryString()->through(fn ($item) => AeronauticalItemPresenter::toArray($item))->toArray(),
            'filters' => $filters, 'types' => array_column(InformationType::cases(), 'value'), 'providers' => $this->health->execute(),
            'canImport' => Gate::forUser($actor)->allows('sync', AeronauticalInformationItem::class) && Gate::forUser($actor)->allows('manage', AeronauticalInformationItem::class)];
    }

    public function detail(AeronauticalInformationItem $item, User $actor): array
    {
        Gate::forUser($actor)->authorize('view', $item);

        return ['item' => AeronauticalItemPresenter::toArray($item), 'history' => AeronauticalInformationItem::query()->where('provider', $item->provider)->where(fn ($q) => $q->where('source_identifier', $item->source_identifier)->orWhere('id', $item->supersedes_id)->orWhere('supersedes_id', $item->id))->orderByDesc('id')->get(['id', 'source_identifier', 'source_revision', 'status', 'received_at', 'superseded_at'])->toArray()];
    }
}
