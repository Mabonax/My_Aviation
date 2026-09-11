<?php

namespace App\Domains\Uas\Checklists\Domain\Services;

use App\Domains\Uas\Checklists\Domain\Models\UasChecklistTemplate;
use Illuminate\Validation\ValidationException;

class ChecklistCompletionEvaluator
{
    public function normalise(UasChecklistTemplate $template, array $results): array
    {
        $allowedKeys = collect($template->items)->pluck('key')->all();
        $itemsByKey = collect($template->items)->keyBy('key');
        $normalised = collect($results)
            ->mapWithKeys(function (mixed $value, string $key) use ($allowedKeys): array {
                if (! in_array($key, $allowedKeys, true)) {
                    throw ValidationException::withMessages([
                        'results' => "Checklist result '{$key}' is not part of the active checklist version.",
                    ]);
                }

                return [$key => $this->normaliseValue($value)];
            });

        foreach ($itemsByKey as $key => $item) {
            if (($item['required'] ?? true) && ! $normalised->has($key)) {
                throw ValidationException::withMessages([
                    'results' => "Checklist item '{$item['label']}' is required.",
                ]);
            }
        }

        return $normalised->all();
    }

    public function state(UasChecklistTemplate $template, array $results, ?string $exceptions): string
    {
        $requiredKeys = collect($template->items)->where('required', true)->pluck('key');
        $failed = $requiredKeys->contains(fn (string $key): bool => ($results[$key]['result'] ?? null) !== 'pass');

        if ($failed) {
            return 'blocked';
        }

        return filled($exceptions) ? 'completed_with_exceptions' : 'completed';
    }

    private function normaliseValue(mixed $value): array
    {
        $result = is_array($value) ? ($value['result'] ?? null) : $value;
        $notes = is_array($value) ? ($value['notes'] ?? null) : null;

        if (! in_array($result, ['pass', 'fail', 'not_applicable'], true)) {
            throw ValidationException::withMessages([
                'results' => 'Checklist item results must be pass, fail or not_applicable.',
            ]);
        }

        return [
            'result' => $result,
            'notes' => filled($notes) ? (string) $notes : null,
        ];
    }
}