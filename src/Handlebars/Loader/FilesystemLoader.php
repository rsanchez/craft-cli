<?php

namespace CraftCli\Handlebars\Loader;

use Handlebars\Loader\FilesystemLoader as Loader;
use LogicException;

/**
 * Overridden to work in Phar
 */
class FilesystemLoader extends Loader
{
    /**
     * Puts directory into standardized format
     *
     * @param String $dir The directory to sanitize
     *
     * @return String
     */
    protected function sanitizeDirectory($dir)
    {
        $isPhar = preg_match('#^phar:///(.+)$#', $dir, $match);
        $isAbsolute = strncmp('/', $dir, 1) === 0;
        $prefix = '';

        if ($isPhar) {
            $prefix = 'phar:///';
            $dir = $match[1];
        }

        if ($isAbsolute) {
            $prefix = '/';
        }

        return $prefix.rtrim($this->normalizePath($dir), '/');
    }

    /**
     * Normalize path.
     *
     * From https://github.com/thephpleague/flysystem/blob/master/src/Util.php
     *
     * @param string $path
     *
     * @throws LogicException
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        // Remove any kind of funky unicode whitespace
        $normalized = preg_replace('#\p{C}+|^\./#u', '', $path);
        $normalized = $this->normalizeRelativePath($normalized);
        if (preg_match('#/\.{2}|^\.{2}/|^\.{2}$#', $normalized)) {
            throw new LogicException(
                'Path is outside of the defined root, path: [' . $path . '], resolved: [' . $normalized . ']'
            );
        }
        $normalized = preg_replace('#\\\{2,}#', '\\', trim($normalized, '\\'));
        $normalized = preg_replace('#/{2,}#', '/', trim($normalized, '/'));
        return $normalized;
    }

    /**
     * Normalize relative directories in a path.
     *
     * From https://github.com/thephpleague/flysystem/blob/master/src/Util.php
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizeRelativePath($path)
    {
        // Path remove self referring paths ("/./").
        $path = preg_replace('#/\.(?=/)|^\./|/\./?$#', '', $path);
        // Regex for resolving relative paths
        $regex = '#/*[^/\.]+/\.\.#Uu';
        while (preg_match($regex, $path)) {
            $path = preg_replace($regex, '', $path);
        }
        return $path;
    }
}
