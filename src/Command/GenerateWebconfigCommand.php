<?php

namespace CraftCli\Command;

use CraftCli\Command\ExemptFromBootstrapInterface;
use Symfony\Component\Console\Input\InputArgument;
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class GenerateWebconfigCommand extends BaseCommand implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'generate:webconfig';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Generate the default Craft CMS web.config file.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'path',
                InputArgument::OPTIONAL,
                'Where to create the web.config file.',
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

        $destination = $path.'web.config';

        if (file_exists($destination)) {
            $path = realpath($path).DIRECTORY_SEPARATOR;

            $this->error("A web.config file already exists in {$path}");

            $confirmed = $this->confirm('Do you want to overwrite?');

            if (! $confirmed) {
                $this->info('Did not create web.config file.');

                return;
            }
        }

        copy(__DIR__.'/../templates/web.config.txt', $destination);

        $this->info($destination.' created.');
    }
}
