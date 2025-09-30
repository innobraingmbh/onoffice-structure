<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Converters\Array\Concerns\FilterEmptyRecursive;
use Innobrain\Structure\Converters\Concerns\ConvertStrategy;

/**
 * Concrete converter that turns DTOs into nested arrays.
 */
final readonly class ArrayConvertStrategy implements ConvertStrategy
{
    use ArrayField;
    use ArrayFieldDependency;
    use ArrayFieldFilter;
    use ArrayModule;
    use ArrayPermittedValue;
    use FilterEmptyRecursive;

    public function __construct(private bool $dropEmpty = false) {}

    private function normalize(array $payload): array
    {
        return $this->dropEmpty ? $this->filterEmptyRecursive($payload) : $payload;
    }
}
