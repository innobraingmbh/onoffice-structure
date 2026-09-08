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
     * Resolve the module keys to request from the API. An empty list means all modules.
     *
     * @param  array<int, self|string>  $only
     * @return array<int, string>
     *
     * @throws InvalidArgumentException when a module key is unknown
     */
    public static function values(array $only = []): array
    {
        if ($only === []) {
            return array_map(static fn (self $module): string => $module->value, self::cases());
        }

        return array_values(array_unique(array_map(
            static fn (self|string $module): string => $module instanceof self ? $module->value : self::fromKey($module)->value,
            $only,
        )));
    }

    private static function fromKey(string $key): self
    {
        return self::tryFrom($key) ?? throw new InvalidArgumentException("Unknown field configuration module [$key].");
    }
}
