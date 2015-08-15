<?php

namespace CraftCli\Command;

use CraftCli\Command\ExemptFromBootstrapInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use ZipArchive;

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
        if (! $this->option('terms') && ! $this->confirm('I agree to the terms and conditions (https://buildwithcraft.com/license)')) {
            $this->error('You did not agree to the terms and conditions (https://buildwithcraft.com/license)');

            return;
        }

        if (file_exists(getcwd().'/craft') && ! $this->option('overwrite')) {
            $this->error('Craft is already installed here!');

            if (! $this->confirm('Do you want to overwrite?')) {
                $this->info('Exited without installing.');

                return;
            }
        }

        $this->comment('Looking up the download url...');

        // get the latest download url from buildwithcraft.com
        $contents = file_get_contents('https://buildwithcraft.com');

        if (! preg_match('#window.craftDownloadUrl = "(.*?)"#', $contents, $match)) {
            $this->error('Could not find the download url at buildwithcraft.com.');

            return;
        }

        $url = $match[1];

        // replace hex chars with regular chars
        $url = preg_replace_callback('/\\\\x([ABCDEF0-9]{2})/', function ($match) {
            return chr(hexdec($match[1]));
        }, $url);

        $this->comment('Downloading the installation zip file...');

        $filePath = tempnam(sys_get_temp_dir(), 'craft_installer_');

        $fileHandle = fopen($filePath, 'wb');

        if ($fileHandle === false) {
            $this->error('Could not open temp file.');

            return;
        }

        $downloadContext = stream_context_create(array(), array('notification' => array($this, 'showDownloadProgress')));

        $downloadHandle = fopen($url, 'rb', false, $downloadContext);

        if ($downloadHandle === false) {
            $this->error('Could not download installation zip.');

            return;
        }

        while (! feof($downloadHandle)) {
            if (fwrite($fileHandle, fread($downloadHandle, 1024)) === false) {
                return false;
            }
        }

        fclose($downloadHandle);

        if ($this->progressBar) {
            $this->progressBar->finish();
        }

        fclose($fileHandle);

        $this->line('');

        $this->comment('Unzipping the installation zip file...');

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();

            $zip->open($filePath);

            $zip->extractTo(getcwd());

            $zip->close();

            unset($zip);
        } else {
            if (! function_exists('system')) {
                throw new \RuntimeException('You cannot run the system PHP function');
            }

            system(sprintf('unzip -o -d %s %s', escapeshellarg(getcwd()), escapeshellarg($filePath)));
        }

        if ($public = $this->option('public')) {
            rename(getcwd().'/public', getcwd().'/'.$public);
        }

        unlink($filePath);

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
