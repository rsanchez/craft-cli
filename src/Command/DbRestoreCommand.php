<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use CraftCli\Support\MysqlCommand;
use Exception;

class DbRestoreCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'db:restore';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Restore a database from backup.';

    /**
     * Database credentials
     * @var array
     */
    protected $credentials;

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
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite database without confirmation prompt.',
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
                'path',
                InputArgument::OPTIONAL,
                'Specify a path to the backup file. Defaults to the most recent backup in craft/storage/backups.',
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

        if (isset($dbConfig['*'])) {
            $this->credentials = $dbConfig['*'];

            if (isset($dbConfig[$this->environment])) {
                $this->credentials = array_merge($this->credentials, $dbConfig[$this->environment]);
            }
        } else {
            $this->credentials = $dbConfig;
        }

        $this->debug = $this->option('debug');

        if (! $this->debug && ! $this->option('force') && ! $this->confirm('This will overwrite your local database. Are you sure you want to continue?')) {
            throw new Exception('Transfer cancelled.');
        }

        // test the db credentials
        $this->info('Testing database credentials...');

        if (! $this->testCredentials()) {
            throw new Exception('Could not connect to mysql database.');
        }
    }

    /**
     * Test local database credentials
     * @return boolean
     */
    protected function testCredentials()
    {
        $mysqlCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->credentials, 'SHOW TABLES');

        if ($this->debug) {
            $this->output->writeln($mysqlCommand);

            return true;
        }

        exec($mysqlCommand, $output, $status);

        return $status === 0;
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
     * {@inheritdoc}
     */
    protected function fire()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        if ($this->argument('path')) {
            $path = $this->argument('path');

            if (!file_exists($path)) {
                return $this->fail(sprintf('File %s not found.', $path));
            }
        // find the latest backup
        } else {
            $files = glob(CRAFT_STORAGE_PATH.'backups/*.sql');

            if (!$files) {
                return $this->fail('No backups found in craft/storage/backups.');
            }

            // sort by latest
            usort($files, function($a, $b) {
                $a = filemtime($a);
                $b = filemtime($b);

                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? -1 : 1;
            });

            $path = array_shift($files);
        }

        $mysqlCommand = (string) $this->makeMysqlCommand(MysqlCommand::class, $this->credentials);

        $command = "$mysqlCommand < $path";

        $this->info('Restoring database...');

        if ($this->debug) {
            $this->output->writeln($command);
        } else {
            passthru($command);
        }

        $this->info('Database restored.');
    }
}
