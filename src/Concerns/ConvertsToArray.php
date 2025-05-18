<?php

declare(strict_types=1);

namespace Innobrain\Structure\Concerns;

use Illuminate\Support\Collection;

use function is_array;

/**
 * Helper to strip null / empty values recursively from an array payload.
 */
trait ConvertsToArray
{
    private function filterEmptyRecursive(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if ($value instanceof Collection) {
                $value = $this->filterEmptyRecursive($value->toArray());
            } elseif (is_array($value)) {
                $value = $this->filterEmptyRecursive($value);
            }
            if ($value === null) {
                continue;
            }
            if ($value === '') {
                continue;
            }
            if ($value === []) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }
}
