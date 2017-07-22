<?php

namespace CraftCli\Command;

use CraftCli\Support\SshCommand;
use CraftCli\Support\MysqlCommand;
use CraftCli\Support\MysqlDumpCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class DbPushCommand extends DbPullCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'db:push';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Push the local database to a remote server';

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        $mysqlDumpCommand = (string) $this->makeMysqlCommand(MysqlDumpCommand::class, $this->localCredentials);

        $remoteCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->remoteCredentials);

        if (! $this->option('no-gzip')) {
            $mysqlDumpCommand .= ' | gzip';

            $remoteCommand = 'gunzip | '.$remoteCommand;
        }

        if ($this->sshCredentials) {
            $remoteCommand = $this->makeSshCommand($remoteCommand);
        }

        $command = "$mysqlDumpCommand | $remoteCommand";

        $this->info('Pushing local database...');

        if ($this->debug) {
            $this->output->writeln($command);
        } else {
            passthru($command);
        }
    }
}
