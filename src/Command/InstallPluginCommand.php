<?php

namespace CraftCli\Command;

use CraftCli\Support\TarExtractor;
use CraftCli\Support\PluginReader;
use CraftCli\Support\Downloader\TempDownloader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Exception;
use CFileHelper;

class InstallPluginCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'install:plugin';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Install a plugin from a Github repository.';

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
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
    protected function getArguments()
    {
        return array(
            array(
                'repo',
                InputArgument::REQUIRED,
                'Name of Github repository. (ex. pixelandtonic/Events)',
            ),
            array(
                'branch',
                InputArgument::OPTIONAL,
                'Which branch? (Default: master)',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $repo = $this->argument('repo');

        if (! preg_match('#^([\d\w_-]+)/([\d\w\._-]+)$#', $repo)) {
            throw new Exception('Repository must be formatted: username/repo.');
        }

        $branch = $this->argument('branch') ?: 'master';

        $url = sprintf('https://github.com/%s/archive/%s.tar.gz', $repo, $branch);

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

        $extractionPath = rtrim(sys_get_temp_dir(), '/').'/craft_plugin_'.uniqid();

        if (! @mkdir($extractionPath)) {
            $this->error('Could not create directory in system temp directory.');

            return;
        }

        $tarExtractor = new TarExtractor($filePath, $extractionPath);

        $tarExtractor->extract();

        // determine the folder structure of the download in the temp path
        $pluginReader = new PluginReader($extractionPath);

        try {
            $pluginFile = $pluginReader->read();
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $folderName = strtolower($pluginFile->getBasename('Plugin.php'));

        // check if craft is already installed, and overwrite option
        if (file_exists($this->pluginsPath.$folderName) && ! $this->option('overwrite')) {
            $this->error(sprintf('%s is already installed!', $folderName));

            if (! $this->confirm('Do you want to overwrite?')) {
                $this->info('Exited without installing.');

                return;
            }
        }

        // move the plugin from the temp folder to the craft installation
        CFileHelper::copyDirectory($pluginFile->getPath(), $this->pluginsPath.$folderName);

        // delete the temp files
        CFileHelper::removeDirectory($extractionPath);

        $this->info('Installation complete!');
    }
}
