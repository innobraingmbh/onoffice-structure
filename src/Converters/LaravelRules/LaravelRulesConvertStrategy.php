<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Contracts\ConvertStrategy;

/**
 * Converts fields into Laravel validation rules and modules into rule arrays
 * keyed by field key. Multi-select fields receive an additional "{fieldKey}.*"
 * rule so that each submitted item is checked against the permitted values.
 */
final readonly class LaravelRulesConvertStrategy implements ConvertStrategy
{
    use ConvertsFieldToLaravelRules;
    use ConvertsModuleToLaravelRules;

    /**
     * @param  bool  $pipeSyntax  true ➜ 'string|max:80|nullable',  false ➜ ['string', 'max:80', 'nullable']
     * @param  bool  $includeNullable  true ➜ append 'nullable' when a field has no default
     */
    public function __construct(
        private bool $pipeSyntax = true,
        private bool $includeNullable = true,
    ) {}

    /**
     * @param  string[]  $rules
     * @return string|array<int, string>
     */
    private function pipeOrArray(array $rules): string|array
    {
        return $this->pipeSyntax ? implode('|', $rules) : $rules;
    }
}
