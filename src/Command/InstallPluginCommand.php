<?php

namespace CraftCli\Command;

use CraftCli\Support\TarExtractor;
use CraftCli\Support\PluginReader;
use CraftCli\Support\Downloader\TempDownloader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Exception;

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

        if (! preg_match('#^([\d\w_-]+)/([\d\w_-]+)$#', $repo)) {
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

        $extractionPath = sys_get_temp_dir().'craft_plugin_'.uniqid();

        $fs = new Filesystem();

        try {
            $fs->mkdir($extractionPath);
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $tarExtractor = new TarExtractor($filePath, $extractionPath);

        $tarExtractor->extract();

        // determine the folder structure of the download in the temp path
        $pluginReader = new PluginReader($extractionPath);

        if (! $pluginReader->read()) {
            $this->error('Could not find a valid plugin in this download.');

            return;
        }

        // check if craft is already installed, and overwrite option
        if (file_exists(CRAFT_PLUGINS_PATH.$pluginReader->getFolderName()) && ! $this->option('overwrite')) {
            $this->error(sprintf('%s is already installed!', $pluginReader->getFolderName()));

            if (! $this->confirm('Do you want to overwrite?')) {
                $this->info('Exited without installing.');

                return;
            }
        }

        // move the plugin from the temp folder to the craft installation
        try {
            $fs->rename($pluginReader->getPath(), CRAFT_PLUGINS_PATH.$pluginReader->getFolderName());
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        // delete the temp files
        try {
            $fs->remove($extractionPath);
        } catch (Exception $e) {
        }

        $this->info('Installation complete!');
    }
}
