<?php

namespace CraftCli\Command;

use Boris\Boris;

class ConsoleCommand extends BaseCommand
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
        $requiredExtensions = array('readline', 'posix', 'pcntl');

        foreach ($requiredExtensions as $extension) {
            if (! extension_loaded($extension)) {
                throw new \Exception(sprintf('PHP %s extension is required for this command.', $extension));
            }
        }

        $boris = new Boris('> ');

        $boris->start();
    }
}
