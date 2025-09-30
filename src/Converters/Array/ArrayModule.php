<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Dtos\Module;

trait ArrayModule
{
    /**
     * @return array<string, mixed>
     */
    public function convertModule(Module $module): array
    {
        return $this->normalize([
            'key' => $module->key->value,
            'label' => $module->label,
            'fields' => $module->fields
                ->map(fn ($f) => $f->convert($this))
                ->toArray(),
        ]);
    }
}
