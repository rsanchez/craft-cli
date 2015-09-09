<?php

namespace CraftCli\Command;

use Psy\Shell;

class ConsoleCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'console';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Start an interactive shell.';

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $shell = new Shell();

        $shell->run();
    }
}
