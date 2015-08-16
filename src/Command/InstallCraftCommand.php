<?php

namespace CraftCli\Command;

use CraftCli\Command\ExemptFromBootstrapInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use PharData;

class InstallCraftCommand extends BaseCommand implements ExemptFromBootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'install';

    /**
     * {@inheritdoc}
     */
    protected $description = '';

    /**
     * @var Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar;

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

        // create a temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'craft_installer_');

        $filePath = $tempPath.'.tar.gz';

        $tarPath = $tempPath.'.tar';

        // rename the temp file to .tar.gz (PharData requires that extension)
        rename($tempPath, $filePath);

        // open the temp file for writing
        $fileHandle = fopen($filePath, 'wb');

        if ($fileHandle === false) {
            $this->error('Could not open temp file.');

            return;
        }

        // download context so we can track download progress
        $downloadContext = stream_context_create(array(), array('notification' => array($this, 'showDownloadProgress')));

        // open the download url for reading
        $downloadHandle = fopen($url, 'rb', false, $downloadContext);

        if ($downloadHandle === false) {
            $this->error('Could not download installation file.');

            return;
        }

        while (! feof($downloadHandle)) {
            if (fwrite($fileHandle, fread($downloadHandle, 1024)) === false) {
                $this->error('Could not write installation file to disk.');

                return;
            }
        }

        fclose($downloadHandle);

        if ($this->progressBar) {
            $this->progressBar->finish();

            $this->line('');
        }

        fclose($fileHandle);

        $this->comment('Extracting...');

        // create a tar file
        (new PharData($filePath))->decompress();

        // unarchive from the tar
        (new PharData($tarPath))->extractTo(getcwd(), null, true);

        // change the name of the public folder
        if ($public = $this->option('public')) {
            rename(getcwd().'/public', getcwd().'/'.$public);
        }

        // remove the temp files
        unlink($filePath);

        unlink($tarPath);

        $this->info('Installation complete!');
    }

    protected function showDownloadProgress(
        $notificationCode,
        $severity,
        $message,
        $messageCode,
        $bytesTransferred,
        $bytesMax
    ) {
        switch ($notificationCode) {
            case STREAM_NOTIFY_FILE_SIZE_IS:
                $this->progressBar = new ProgressBar($this->output, $bytesMax);
                break;
            case STREAM_NOTIFY_PROGRESS:
                if ($this->progressBar) {
                    $this->progressBar->setProgress($bytesTransferred);
                }
                break;
        }
    }
}
