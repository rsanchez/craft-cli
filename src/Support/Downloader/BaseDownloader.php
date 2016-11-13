<?php

namespace CraftCli\Support\Downloader;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\CaBundle\CaBundle;
use Exception;

abstract class BaseDownloader
{
    /**
     * Url to download
     * @var string
     */
    protected $url;

    /**
     * Path to download the file
     * @var string
     */
    protected $path;

    /**
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar;

    /**
     * Set the output
     * @param Symfony\Component\Console\Output\OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Download the file to the specified path
     * @return string path to the downloaded file
     */
    public function download()
    {
        if (! ini_get('allow_url_fopen')) {
            throw new Exception('allow_url_fopen is disabled.');
        }

        // open the temp file for writing
        $fileHandle = fopen($this->path, 'wb');

        if ($fileHandle === false) {
            throw new Exception('Could not open temp file.');
        }

        $caPath = CaBundle::getSystemCaRootBundlePath();

        if (is_dir($caPath)) {
            $streamOptions = array('ssl' => array('capath' => $caPath));
        } else {
            $streamOptions = array('ssl' => array('cafile' => $caPath));
        }

        $streamParams = array('notification' => array($this, 'showDownloadProgress'));

        // download context so we can track download progress
        $downloadContext = stream_context_create($streamOptions, $streamParams);

        // open the download url for reading
        $downloadHandle = @fopen($this->url, 'rb', false, $downloadContext);

        if ($downloadHandle === false) {
            throw new Exception('Could not download installation file.');
        }

        while (! feof($downloadHandle)) {
            if (fwrite($fileHandle, fread($downloadHandle, 1024)) === false) {
                throw new Exception('Could not write installation file to disk.');
            }
        }

        fclose($downloadHandle);

        fclose($fileHandle);

        if ($this->progressBar) {
            $this->progressBar->finish();

            $this->output->writeln('');
        }

        return $this->path;
    }

    /**
     * ProgressBar callback
     * @param  $notificationCode
     * @param  $severity
     * @param  $message
     * @param  $messageCode
     * @param  $bytesTransferred
     * @param  $bytesMax
     * @return void
     */
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
                if ($this->output) {
                    $this->progressBar = new ProgressBar($this->output, $bytesMax);
                }
                break;
            case STREAM_NOTIFY_PROGRESS:
                if ($this->progressBar) {
                    $this->progressBar->setProgress($bytesTransferred);
                }
                break;
        }
    }
}
