<?php

namespace CraftCli\Support;

class RsyncCommand
{
    /**
     * SSH src host
     * @var string
     */
    public $srcHost;

    /**
     * SSH src user
     * @var string
     */
    public $srcUser;

    /**
     * SSH dest host
     * @var string
     */
    public $destHost;

    /**
     * SSH dest user
     * @var string
     */
    public $destUser;

    /**
     * SSH port
     * @var string
     */
    public $port;

    /**
     * SSH identity file path
     * @var string
     */
    public $identityFile;

    /**
     * Rsync flags
     * @var string
     */
    public $flags = 'trzP';

    /**
     * Rsync --no-perms flag
     * @var boolean
     */
    public $noPerms = true;

    /**
     * Rsync --no-owner flag
     * @var boolean
     */
    public $noOwner = true;

    /**
     * Rsync --no-group flag
     * @var boolean
     */
    public $noGroup = true;

    /**
     * Rsync --exclude flag(s)
     * @var array
     */
    public $exclude = [
        '.DS_Store',
    ];

    /**
     * Rsync src path
     * @var string
     */
    public $src;

    /**
     * Rsync dest path
     * @var string
     */
    public $dest;

    /**
     * Constructor
     * @param string $src
     * @param string $dest
     */
    public function __construct($src, $dest)
    {
        $this->src = $src;
        $this->dest = $dest;
    }

    protected function getPath($which)
    {
        $path = $this->{$which};

        if ($this->{$which.'Host'}) {
            $path = $this->{$which.'Host'}.':'.$path;
        }

        if ($this->{$which.'User'}) {
            $path = $this->{$which.'User'}.'@'.$path;
        }

        return $path;
    }

    protected function getSrcPath()
    {
        return $this->getPath('src');
    }

    protected function getDestPath()
    {
        return $this->getPath('dest');
    }

    /**
     * Get command line arguments for shell command
     * @return array
     */
    protected function getArguments()
    {
        $arguments = [
            'rsync',
        ];

        if ($this->flags) {
            $arguments[] = '-'.$this->flags;
        }

        if ($this->noPerms) {
            $arguments[] = '--no-perms';
        }

        if ($this->noOwner) {
            $arguments[] = '--no-owner';
        }

        if ($this->noGroup) {
            $arguments[] = '--no-group';
        }

        $sshOpts = [];

        if ($this->port) {
            $sshOpts[] = '-p';
            $sshOpts[] = escapeshellarg($this->port);
        }

        if ($this->identityFile) {
            $sshOpts[] = '-i';
            $sshOpts[] = escapeshellarg($this->identityFile);
        }

        if ($sshOpts) {
            $arguments[] = '-e';
            $arguments[] = sprintf("'ssh %s'", implode(' ', $sshOpts));
        }

        foreach ($this->exclude as $exclude) {
            $arguments[] = '--exclude';
            $arguments[] = escapeshellarg($exclude);
        }

        $arguments[] = escapeshellarg($this->getSrcPath());

        $arguments[] = escapeshellarg($this->getDestPath());

        return $arguments;
    }

    /**
     * Compile the shell command
     * @return string
     */
    public function __toString()
    {
        $arguments = $this->getArguments();

        // space prefix to prevent bash history
        return ' '.implode(' ', $arguments);
    }
}
