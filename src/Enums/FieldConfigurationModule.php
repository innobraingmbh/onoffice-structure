<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

use InvalidArgumentException;

enum FieldConfigurationModule: string
{
    case Address = 'address';
    case Estate = 'estate';
    case AgentsLog = 'agentsLog';
    case Calendar = 'calendar';
    case Email = 'email';
    case File = 'file';
    case News = 'news';
    case Intranet = 'intranet';
    case Project = 'project';
    case Task = 'task';
    case User = 'user';

    /**
     * The module keys to request from the API, in declaration order. Empty
     * means all modules.
     *
     * @param  array<int, self|string>  $only
     * @return array<int, string>
     *
     * @throws InvalidArgumentException when a module key is unknown
     */
    public static function values(array $only = []): array
    {
        $requested = collect($only ?: self::cases())
            ->map(fn (self|string $module): string => $module instanceof self
                ? $module->value
                : (self::tryFrom($module) ?? throw new InvalidArgumentException("Unknown field configuration module [$module]."))->value);

        return collect(array_column(self::cases(), 'value'))->intersect($requested)->values()->all();
    }
}
