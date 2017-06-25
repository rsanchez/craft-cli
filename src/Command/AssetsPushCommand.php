<?php

namespace CraftCli\Command;

use CraftCli\Support\RsyncCommand;
use CraftCli\Support\SshCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Craft\LocalAssetSourceType;
use Craft\IOHelper;
use Exception;

class AssetsPushCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'assets:push';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Push assets to a remote server';

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
                'SSH host of remote connection.',
            ),
            array(
                'ssh-user',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH user of remote connection.',
            ),
            array(
                'ssh-port',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH port of remote connection.',
            ),
            array(
                'ssh-identity-file',
                null,
                InputOption::VALUE_REQUIRED,
                'SSH identity file.',
            ),
            array(
                'remote-base-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Override remote base path.',
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

    protected function parseEnvironmentVariables($str, $config, $isDest = false)
    {
        if (isset($config['environmentVariables'])) {
            foreach ($config['environmentVariables'] as $key => $value) {
                if ($isDest && $key === 'basePath' && $this->option('remote-base-path')) {
                    $value = $this->option('remote-base-path');
                }

                $str = str_replace('{'.$key.'}', $value, $str);
            }
        }

        return $str;
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $config = require $this->configPath.'general.php';

        if (! isset($config['*'])) {
            return $this->fail('Could not find a multi-environment configuration in your general.php.');
        }

        $remoteEnvironment = $this->argument('remote-environment');

        $localConfig = $remoteConfig = $config['*'];

        if (isset($config[$this->environment])) {
            $localConfig = array_merge($localConfig, $config[$this->environment]);
        }

        if (isset($config[$remoteEnvironment])) {
            $remoteConfig = array_merge($remoteConfig, $config[$remoteEnvironment]);
        }

        $this->debug = $this->option('debug');

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

        // test the ssh credentials
        if ($this->sshCredentials) {
            $this->info('Testing SSH credentials...');

            if (! $this->testSshCredentials()) {
                return $this->fail('Could not connect to remote server via SSH.');
            }
        }

        $assetSources = array_filter(
            $this->craft->assetSources->getAllSources(),
            function ($source) {
                return $source->getSourceType() instanceof LocalAssetSourceType;
            }
        );

        $this->info('Pushing local assets...');

        foreach ($assetSources as $source) {
            $path = $source->getSourceType()->getSettings()->path;

            $src = $this->parseEnvironmentVariables($path, $localConfig);

            $dest = $this->parseEnvironmentVariables($path, $remoteConfig, true);

            $command = (string) $this->makeRsyncCommand($src, $dest);

            if ($this->debug) {
                $this->output->writeln($command);
            } else {
                passthru($command);
            }
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
     * Make an rsync command object
     * @param  string $command Command to execute over SSH
     * @return \CraftCli\Support\RsyncCommand
     */
    protected function makeRsyncCommand($src, $dest)
    {
        $command = new RsyncCommand($src, $dest);

        if (!empty($this->sshCredentials['host'])) {
            $command->destHost = $this->sshCredentials['host'];
        }

        if (!empty($this->sshCredentials['user'])) {
            $command->destUser = $this->sshCredentials['user'];
        }

        if (!empty($this->sshCredentials['port'])) {
            $command->port = $this->sshCredentials['port'];
        }

        if (!empty($this->sshCredentials['identityFile'])) {
            $command->identityFile = $this->sshCredentials['identityFile'];
        }

        return $command;
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
}
