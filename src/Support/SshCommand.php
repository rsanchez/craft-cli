<?php

namespace CraftCli\Support;

class SshCommand
{
    /**
     * SSH host
     * @var string
     */
    public $host;

    /**
     * SSH user
     * @var string
     */
    public $user;

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
     * Command to exectute over SSH
     * @var string
     */
    public $command;

    /**
     * Constructor
     * @param string $host    SSH host
     * @param string $command Command to exectute over SSH
     */
    public function __construct($host, $command)
    {
        $this->host = $host;
        $this->command = $command;
    }

    /**
     * Compile the shell command
     * @return string
     */
    public function __toString()
    {
        $arguments = ['ssh'];

        if ($this->identityFile) {
            $arguments[] = '-i '.escapeshellarg($this->identityFile);
        }

        if ($this->port) {
            $arguments[] = '-p '.$this->port;
        }

        $arguments[] = '-o ConnectTimeout=10';

        $arguments[] = $this->user ? $this->user.'@'.$this->host : $this->host;

        $arguments[] = escapeshellarg($this->command);

        return implode(' ', $arguments);
    }
}
