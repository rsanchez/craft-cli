<?php

namespace CraftCli\Bootstrap;

use Exception;

class DirectoryHelper
{
    public function verifyDirectoryIsReadable($path, $label = 'path')
    {
        $this->verifyDirectoryExists($path, $label);

        if (!is_readable($path)) {
            throw new Exception(sprintf('Could not read the %s at %s', $label, $path));
        }
    }

    public function verifyFileExists($path, $label = 'path')
    {
        if (realpath($path) === false) {
            throw new Exception(sprintf('Could not resolve the %s at %s', $label, $path));
        }

        if (!file_exists($path)) {
            throw new Exception(sprintf('Could not find the %s at %s', $label, $path));
        }
    }

    public function verifyDirectoryExists($path, $label = 'path')
    {
        $this->verifyFileExists($path, $label);

        if (!is_dir($path)) {
            throw new Exception(sprintf('The %s at %s is not a directory', $label, $path));
        }
    }

    public function verifyDirectoryIsWritable($path, $label = 'path')
    {
        $this->verifyDirectoryIsReadable($path, $label);

        if (!is_writable($path)) {
            throw new Exception(sprintf('Could not write the %s at %s', $label, $path));
        }
    }

    public function createDirectory($path, $label = 'path')
    {
        $umask = umask(0);

        $permission = 0755;

        if (!mkdir($path, $permission, true)) {
            throw new Exception(sprintf('Could not create the %s at %s', $label, $path));
        }

        chmod($path, $permission);

        umask($umask);
    }
}
