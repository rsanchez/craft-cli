<?php

namespace CraftCli\Command;

use CraftCli\Support\TarExtractor;
use CraftCli\Support\Downloader\TempDownloader;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class InstallCraftCommand extends Command implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'install';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Install Craft to the current directory.';

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
            array(
                'terms', // name
                't', // shortcut
                InputOption::VALUE_NONE, // mode
                'I agree to the terms and conditions (https://buildwithcraft.com/license)', // description
                null, // default value
            ),
            array(
                'public', // name
                'p', // shortcut
                InputOption::VALUE_REQUIRED, // mode
                'Rename the public folder.', // description
                null, // default value
            ),
            array(
                'overwrite', // name
                'o', // shortcut
                InputOption::VALUE_NONE, // mode
                'Overwrite existing installation.', // description
                null, // default value
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        // check terms and conditions
        if (! $this->option('terms') && ! $this->confirm('I agree to the terms and conditions (https://buildwithcraft.com/license)')) {
            $this->error('You did not agree to the terms and conditions (https://buildwithcraft.com/license)');

            return;
        }

        // check if craft is already installed, and overwrite option
        if (file_exists(getcwd().'/craft') && ! $this->option('overwrite')) {
            $this->error('Craft is already installed here!');

            if (! $this->confirm('Do you want to overwrite?')) {
                $this->info('Exited without installing.');

                return;
            }
        }

        $url = 'http://buildwithcraft.com/latest.tar.gz?accept_license=yes';

        $this->comment('Downloading...');

        $downloader = new TempDownloader($url, '.tar.gz');

        $downloader->setOutput($this->output);

        try {
            $filePath = $downloader->download();
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->comment('Extracting...');

        $tarExtractor = new TarExtractor($filePath, getcwd());

        $tarExtractor->extract();

        // change the name of the public folder
        if ($public = $this->option('public')) {
            rename(getcwd().'/public', getcwd().'/'.$public);
        }

        $this->info('Installation complete!');
    }
}
