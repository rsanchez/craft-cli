<?php

namespace CraftCli\Command;

use CraftCli\Support\TarExtractor;
use CraftCli\Support\Downloader\TempDownloader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Exception;

class DownloadCraftCommand extends Command implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'download';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Download Craft to the current directory.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'path',
                InputArgument::OPTIONAL,
                'Specify an download path. Defaults to the current working directory.',
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
            array(
                'no-prompt', // name
                null, // shortcut
                InputOption::VALUE_NONE, // mode
                'No interactive prompt', // description
            ),
        );
    }

    /**
     * Download Craft from buildwithcraft.com
     * @return void
     */
    protected function fire()
    {
        $path = $this->getPath();

        if (! is_dir($path) && ! @mkdir($path, 0777, true)) {
            return $this->fail(sprintf('Could not create directory %s.', $path));
        }

        // check terms and conditions
        if (! $this->option('terms') && ($this->option('no-prompt') || ! $this->confirm('I agree to the terms and conditions (https://buildwithcraft.com/license)'))) {
            return $this->fail('You did not agree to the terms and conditions (https://buildwithcraft.com/license)');
        }

        // check if craft is already installed, and overwrite option
        if (file_exists($path.'/craft') && ! $this->option('overwrite')) {
            $this->error('Craft is already installed here!');

            if ($this->option('no-prompt') || ! $this->confirm('Do you want to overwrite?')) {
                $this->info('Skipped download.');

                return;
            }
        }

        $url = 'https://buildwithcraft.com/latest.tar.gz?accept_license=yes';

        $this->comment('Downloading...');

        $downloader = new TempDownloader($url, '.tar.gz');

        $downloader->setOutput($this->output);

        try {
            $filePath = $downloader->download();
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        $this->comment('Extracting...');

        $tarExtractor = new TarExtractor($filePath, $path);

        $tarExtractor->extract();

        // Rename .htaccess
        rename($path.'/public/htaccess', $path.'/public/.htaccess');

        // change the name of the public folder
        if ($public = $this->option('public')) {
            rename($path.'/public', $path.'/'.$public);
        }

        $this->info('Download complete!');
    }

    /**
     * Get download/install path
     * @return string
     */
    protected function getPath()
    {
        $path = rtrim($this->argument('path'), DIRECTORY_SEPARATOR) ?: getcwd();

        if (!preg_match('#^(\.|/|([A-Z]:\\\\))#i', $path)) {
            $path = '.'.DIRECTORY_SEPARATOR.$path;
        }

        return $path;
    }
}
