<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;
use Exception;

class UpdateAssetIndexesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'update:assetsindexes';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Update Asset Indexes';

    /**
     * {@inheritdoc}
     */
    protected $showsDuration = true;

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'sourceIds',
                InputArgument::IS_ARRAY,
                'Which Assets source ID(s) do you want to update? Leave blank to update all indexes.',
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
                'delete-missing-files',
                'd',
                InputOption::VALUE_NONE,
                'Delete missing files?',
            ),
            array(
                'delete-missing-folders',
                'f',
                InputOption::VALUE_NONE,
                'Delete missing folders?',
            ),
            array(
                'single-index',
                'I',
                InputOption::VALUE_REQUIRED,
                '',
            ),
            array(
                'session-id',
                'S',
                InputOption::VALUE_REQUIRED,
                '',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $indexes = array();

        $sessionId = $this->option('session-id') ?: craft()->assetIndexing->getIndexingSessionId();

        $this->info('Fetching sources...');

        $sources = craft()->assetSources->getAllSources();

        $sourceIds = $this->argument('sourceIds');

        if ($sourceIds) {
            $sources = array_filter($sources, function ($source) use ($sourceIds) {
                return in_array($source->id, $sourceIds) || in_array($source->handle, $sourceIds);
            });
        }

        $sourceIds = array_values(array_map(function ($source) {
            return $source->id;
        }, $sources));

        $singleIndex = $this->option('single-index');

        if ($singleIndex || $singleIndex === '0') {
            return $this->processIndexForSource($sessionId, $singleIndex, $sourceIds[0]);
        }

        $missingFolders = array();

        $grandTotal = 0;

        $this->info('Fetching indexes...');

        foreach ($sourceIds as $sourceId) {
            // Get the indexing list
            $indexList = craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId);

            if (!empty($indexList['error'])) {
                return $this->fail($indexList['error']);
            }

            if (isset($indexList['missingFolders'])) {
                $missingFolders += $indexList['missingFolders'];
            }

            $grandTotal += $indexList['total'];

            $indexes[$sourceId] = $indexList['total'];
        }

        $this->info('Updating indexes...');

        $progressBar = new ProgressBar($this->output, $grandTotal);

        $count = 0;

        // Index the file
        foreach ($indexes as $sourceId => $total) {
            for ($i = 0; $i < $total; $i++) {
                $this->runSingleIndex($sessionId, $i, $sourceId);

                $progressBar->setProgress(++$count);
            }
        }

        $progressBar->finish();

        $this->line('');

        $this->info('Deleting stale index data...');

        $missingFiles = craft()->assetIndexing->getMissingFiles($sourceIds, $sessionId);

        // Clean up stale indexing data (all sessions that have all recordIds set)
        $sessionsInProgress = craft()->db->createCommand()
                ->select('sessionId')
                ->from('assetindexdata')
                ->where('recordId IS NULL')
                ->group('sessionId')
                ->queryScalar();

        if (empty($sessionsInProgress)) {
            craft()->db->createCommand()->delete('assetindexdata');
        } else {
            craft()->db->createCommand()->delete('assetindexdata', array('not in', 'sessionId', $sessionsInProgress));
        }

        if ($missingFiles && $this->option('delete-missing-files')) {
            $this->info('Deleting missing files...');

            craft()->assetIndexing->removeObsoleteFileRecords(array_keys($missingFiles));
        }

        if ($missingFolders && $this->option('delete-missing-folders')) {
            $this->info('Deleting missing folders...');

            craft()->assetIndexing->removeObsoleteFolderRecords(array_keys($missingFolders));
        }

        $this->info('Asset indexes have been updated.');
    }

    protected function runSingleIndex($sessionId, $index, $sourceId)
    {
        $args = array(
            PHP_BINARY,
            $_SERVER['PHP_SELF'],
            'update:assetsindexes',
            '--single-index='.$index,
            '--session-id='.$sessionId,
            $sourceId,
        );

        $process = new Process($args);

        $process->run();
    }

    protected function processIndexForSource($sessionId, $index, $sourceId)
    {
        $attempts = 0;

        $retries = 3;

        do {
            $attempts++;

            try {
                craft()->assetIndexing->processIndexForSource($sessionId, $index, $sourceId);

                return;
            } catch (Exception $e) {
                if ($attempts === $retries) {
                    throw $e;
                }
            }
        } while (true);
    }
}
