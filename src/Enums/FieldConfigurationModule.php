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
}
