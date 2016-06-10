<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
    protected function getOptions()
    {
        return array(
            array(
                'lines',
                'n',
                InputOption::VALUE_REQUIRED,
                'How many lines to show?',
            ),
            array(
                'blocks',
                'b',
                InputOption::VALUE_REQUIRED,
                'How many 512-byte blocks to show?',
            ),
            array(
                'bytes',
                'c',
                InputOption::VALUE_REQUIRED,
                'How many bytes to show?',
            ),
            array(
                'reverse',
                'r',
                InputOption::VALUE_NONE,
                'Reverse tail',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $log = $this->argument('log');

        $runtimePath = $this->storagePath.'runtime/logs/';

        $logPath = "{$runtimePath}{$log}.log";

        if (! file_exists($runtimePath)) {
            $this->error('Invalid log.');
            return;
        }

        $args = '';

        if ($this->option('lines')) {
            $args .= ' -n '.$this->option('lines');
        } elseif ($this->option('blocks')) {
            $args .= ' -b '.$this->option('lines');
        } elseif ($this->option('bytes')) {
            $args .= ' -c '.$this->option('bytes');
        }

        if ($this->option('reverse')) {
            $args .= ' -r';
        }

        $command = sprintf('tail%s %s', $args, escapeshellarg($logPath));

        passthru($command);
    }
}
