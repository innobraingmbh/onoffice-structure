<?php

declare(strict_types=1);

namespace Innobrain\Structure\Commands;

use Illuminate\Console\Command;

class StructureCommand extends Command
{
    public $signature = 'onoffice-structure';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
