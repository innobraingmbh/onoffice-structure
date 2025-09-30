<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Converters\Concerns\ConvertStrategy;

/**
 * Convert the package's DTOs into Laravel validation-rule arrays / strings.
 *
 * Typical usage:
 *   $rules = $modules->convert(new LaravelRulesConvertStrategy());          // all modules
 *   $address = $modules['address']->convert(new LaravelRulesConvertStrategy(pipeSyntax:false));
 *
 * The strategy returns:
 *   • Module   ⇒ array<string, string|array>   (field key → rule list)
 *   • Field    ⇒ string (pipe syntax)  or array<string>  depending on $pipeSyntax
 *
 * Multi-select fields automatically receive an additional "{fieldKey}.*" rule
 * with the permitted-values "in:" constraint so that each submitted item is validated.
 */
final readonly class LaravelRulesConvertStrategy implements ConvertStrategy
{
    use LaravelRulesField;
    use LaravelRulesFieldDependency;
    use LaravelRulesFieldFilter;
    use LaravelRulesModule;
    use LaravelRulesPermittedValue;

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
     */
    private function pipeOrArray(array $rules): string|array
    {
        return $this->pipeSyntax ? implode('|', $rules) : $rules;
    }
}
