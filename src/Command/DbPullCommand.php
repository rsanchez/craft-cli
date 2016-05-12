<?php

namespace CraftCli\Command;

use CraftCli\Support\SshCommand;
use CraftCli\Support\MysqlCommand;
use CraftCli\Support\MysqlDumpCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class DbPullCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'db:pull';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Pull a database from a remote server';

    /**
     * Remote database credentials
     * @var array
     */
    protected $remoteCredentials;

    /**
     * Local database credentials
     * @var array
     */
    protected $localCredentials;

    /**
     * SSH credentials
     * @var array
     */
    protected $sshCredentials;

    /**
     * Debugging
     * @var bool
     */
    protected $debug;

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
            array(
                'ssh-host',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH host.',
            ),
            array(
                'ssh-user',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH user.',
            ),
            array(
                'ssh-port',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH port.',
            ),
            array(
                'ssh-identity-file',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH identity file.',
            ),
            array(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite database without confirmation prompt.',
            ),
            array(
                'no-gzip',
                null,
                InputOption::VALUE_NONE,
                'Do not gzip db during transfer.',
            ),
            array(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Print the command rather than executing it.',
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
                'remote-environment',
                InputArgument::REQUIRED,
                'Server name of the remote environment.',
            ),
        );
    }

    /**
     * Validate environments/config
     * @throws \Exception
     * @return void
     */
    protected function validate()
    {
        $dbConfig = require $this->configPath.'db.php';

        if (! isset($dbConfig['*'])) {
            throw new Exception('Could not find a multi-environment configuration in your db.php.');
        }

        $remoteEnvironment = $this->argument('remote-environment');

        $this->localCredentials = $this->remoteCredentials = $dbConfig['*'];

        if (isset($dbConfig[$this->environment])) {
            $this->localCredentials = array_merge($this->localCredentials, $dbConfig[$this->environment]);
        }

        if (isset($dbConfig[$remoteEnvironment])) {
            $this->remoteCredentials = array_merge($this->remoteCredentials, $dbConfig[$remoteEnvironment]);
        }

        $this->debug = $this->option('debug');

        if (! $this->debug && ! $this->option('force') && ! $this->confirm('This will overwrite your local database. Are you sure you want to continue?')) {
            throw new Exception('Transfer cancelled.');
        }

        if ($this->option('ssh-host')) {
            $this->sshCredentials = [
                'host' => $this->option('ssh-host'),
            ];

            if ($this->option('ssh-user')) {
                $this->sshCredentials['user'] = $this->option('ssh-user');
            }

            if ($this->option('ssh-port')) {
                $this->sshCredentials['port'] = $this->option('ssh-port');
            }

            if ($this->option('ssh-identity-file')) {
                $this->sshCredentials['identityFile'] = $this->option('ssh-identity-file');
            }
        }

        // test the local db credentials
        $this->info('Testing local database credentials...');

        if (! $this->testLocalCredentials()) {
            throw new Exception('Could not connect to local mysql database.');
        }

        // test the ssh credentials
        if ($this->sshCredentials) {
            $this->info('Testing SSH credentials...');

            if (! $this->testSshCredentials()) {
                throw new Exception('Could not connect to remote server via SSH.');
            }
        }

        // test the remote db credentials
        $this->info('Testing remote database credentials...');

        if (! $this->testRemoteCredentials()) {
            throw new Exception('Could not connect to remote mysql database.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $mysqlCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->localCredentials);

        $remoteCommand = (string) $this->makeMysqlCommand(MysqlDumpCommand::class, $this->remoteCredentials);

        if (! $this->option('no-gzip')) {
            $remoteCommand .= ' | gzip';

            $mysqlCommand = 'gunzip | '.$mysqlCommand;
        }

        if ($this->sshCredentials) {
            $remoteCommand = $this->makeSshCommand($remoteCommand);
        }

        $command = "$remoteCommand | $mysqlCommand";

        $this->info('Fetching remote database...');

        if ($this->debug) {
            $this->output->writeln($command);
        } else {
            passthru($command);
        }
    }

    /**
     * Make an SSH command object
     * @param  string $command Command to execute over SSH
     * @return \CraftCli\Support\SshCommand
     */
    protected function makeSshCommand($command)
    {
        $sshCommand = new SshCommand($this->sshCredentials['host'], $command);

        if (! empty($this->sshCredentials['user'])) {
            $sshCommand->user = $this->sshCredentials['user'];
        }

        if (! empty($this->sshCredentials['port'])) {
            $sshCommand->port = $this->sshCredentials['port'];
        }

        if (! empty($this->sshCredentials['identityFile'])) {
            $sshCommand->identityFile = $this->sshCredentials['identityFile'];
        }

        return $sshCommand;
    }

    /**
     * Make a MysqlCommand object
     * @param  string $class
     * @param  array  $credentials
     * @param  string $query
     * @return \CraftCli\Support\AbstractMysqlCommand
     */
    protected function makeMysqlCommand($class, $credentials, $query = null)
    {
        $mysqlCommand = new $class($credentials['database']);

        if (! empty($credentials['server'])) {
            $mysqlCommand->host = $credentials['server'];
        }

        if (! empty($credentials['user'])) {
            $mysqlCommand->user = $credentials['user'];
        }

        if (! empty($credentials['password'])) {
            $mysqlCommand->password = $credentials['password'];
        }

        if (! empty($credentials['port'])) {
            $mysqlCommand->port = $credentials['port'];
        }

        if ($query) {
            $mysqlCommand->query = $query;
        }

        return $mysqlCommand;
    }

    /**
     * Test local database credentials
     * @return boolean
     */
    protected function testLocalCredentials()
    {
        $mysqlCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->localCredentials, 'SHOW TABLES');

        if ($this->debug) {
            $this->output->writeln($mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return $status === 0;
    }

    /**
     * Test SSH credentials
     * @return boolean
     */
    protected function testSshCredentials()
    {
        $sshCommand = (string) $this->makeSshCommand('echo "foo"');

        if ($this->debug) {
            $this->output->writeln($sshCommand);

            return true;
        }

        exec($sshCommand, $output, $status);

        return $status === 0;
    }

    /**
     * Test remote database credentials
     * @return boolean
     */
    protected function testRemoteCredentials()
    {
        $remoteCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->remoteCredentials, 'SHOW TABLES');

        if ($this->sshCredentials) {
            $remoteCommand = (string) $this->makeSshCommand($remoteCommand);
        }

        if ($this->debug) {
            $this->output->writeln($remoteCommand);

            return true;
        }

        exec($remoteCommand, $output, $status);

        return $status === 0;
    }
}
