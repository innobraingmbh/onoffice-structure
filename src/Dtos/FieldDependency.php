<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

/**
 * A permitted value of a field that is only available when the parent field
 * holds the given value, e.g. the "objekttyp" value "einfamilienhaus" requires
 * "objektart" to be "haus".
 */
readonly class FieldDependency
{
    public function __construct(
        public string $permittedValueKey,
        public string $parentFieldValue,
    ) {}
}
