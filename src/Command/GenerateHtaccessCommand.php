<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class GenerateHtaccessCommand extends Command implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'generate:htaccess';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Generate the default Craft CMS .htaccess file.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'path',
                InputArgument::OPTIONAL,
                'Where to create the .htaccess file.',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        // where to create the file, default to current directory
        $path = $this->argument('path') ?: '.';

        // make sure it has a trailing slash
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $destination = $path.'.htaccess';

        if (file_exists($destination)) {
            $path = realpath($path).DIRECTORY_SEPARATOR;

            $confirmed = $this->confirm("An .htaccess file already exists in {$path}. Do you want to overwrite? [yN] ", false);

            if (! $confirmed) {
                $this->info('Did not create .htaccess file.');

                return;
            }
        }

        copy(__DIR__.'/../templates/htaccess.txt', $destination);

        $this->info($destination.' created.');
    }
}
