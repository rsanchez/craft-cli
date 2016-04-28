<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;

class TailCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'tail';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Show tail of the chosen log file.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'log',
                InputArgument::OPTIONAL,
                'Which log do you want to show?',
                'craft',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $log = $this->argument('log');

        $runtimePath = CRAFT_STORAGE_PATH.'runtime/';

        $logPath = "{$runtimePath}{$log}.log";

        if (! file_exists($runtimePath)) {
            $this->error('Invalid log.');
            return;
        }

        $command = sprintf('tail %s', escapeshellarg($logPath));

        passthru($command);
    }
}
