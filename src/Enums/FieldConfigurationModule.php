<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

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
     * @param  array<int, string>  $only
     * @return array<int, string>
     */
    public static function values(array $only = []): array
    {
        $all = array_map(static fn (self $element) => $element->value, self::cases());

        return $only === [] ? $all : array_values(array_intersect($all, $only));
    }
}
