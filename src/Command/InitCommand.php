<?php

namespace CraftCli\Command;

class InitCommand extends Command implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'init';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a default configuration file.';

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $file = getcwd().'/.craft-cli.php';

        if (file_exists($file)) {
            $confirmed = $this->confirm('A configuration file already exists. Do you want to overwrite? y[n]', false);

            if (! $confirmed) {
                return;
            }
        }

        $copy = copy(__DIR__.'/../../sample.craft-cli.php', $file);

        if ($copy === false) {
            $this->error('Could not create the file.');

            return;
        }

        $this->info('Configuration file created.');
    }
}
