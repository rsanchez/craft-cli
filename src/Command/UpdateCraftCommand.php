<?php

namespace CraftCli\Command;

use CraftCli\Support\TarExtractor;
use CraftCli\Support\Downloader\TempDownloader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Exception;
use CFileHelper;

class UpdateCraftCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'update';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Update Craft.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'path',
                InputArgument::OPTIONAL,
                'Specify an installation path. Defaults to the current working directory.',
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
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $path = rtrim($this->argument('path'), DIRECTORY_SEPARATOR) ?: getcwd();

        if (! $this->option('terms') && ! $this->confirm('I agree to the terms and conditions (https://buildwithcraft.com/license)')) {
            $this->error('You did not agree to the terms and conditions (https://buildwithcraft.com/license)');

            return;
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

        $tmp = getcwd().uniqid('/.tmp-');

        @mkdir($tmp, 0777, true);

        $tarExtractor = new TarExtractor($filePath, $tmp);

        $tarExtractor->extract();

        CFileHelper::copyDirectory($tmp.'/craft/app', $path.'/craft/app');

        CFileHelper::removeDirectory($tmp);

        $this->comment('Running migrations...');

        craft()->updates->updateDatabase('craft');

        $this->info('Update complete!');
    }
}
