<?php

namespace CraftCli\Support;

use ReflectionClass;

class ClassFinder
{
    /**
     * Get a list of classes
     * in the specified directory
     *
     * @param  string $subclassOf
     * @param  string $dir
     * @param  string $namespace
     * @param  bool   $autoload
     * @return array
     */
    public function findClassInDir($subclassOf, $dir, $namespace = null, $autoload = false)
    {
        $classes = array();

        if (!is_dir($dir)) {
            return $classes;
        }

        if ($namespace) {
            $namespace = rtrim($namespace, '\\').'\\';
        }

        $dir = rtrim($dir, '/');

        $files = scandir($dir);

        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if ($extension !== 'php') {
                continue;
            }

            $class = $namespace.basename($file, '.php');

            if (! class_exists($class)) {
                if ($autoload) {
                    require_once $dir.'/'.$file;
                } else {
                    continue;
                }
            }

            $reflectionClass = new ReflectionClass($class);

            if (! $reflectionClass->isInstantiable()) {
                continue;
            }

            if (! $reflectionClass->isSubclassOf($subclassOf)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Check if path is absolute (or relative)
     * @param  string  $path
     * @return boolean
     */
    public function isPathAbsolute($path)
    {
        // starts with dot
        if (strncmp($path, '.', 1) === 0) {
            return true;
        }

        $isWindows = strncmp(strtoupper(PHP_OS), 'WIN', 3) === 0;

        if ($isWindows) {
            // matches drive + path notation (C:\) or \\network-drive\
            return (bool) preg_match('/^([a-zA-Z]:\\\\|\\\\\\\\)/', $path);
        }

        // starts with slash
        return strncmp($path, '/', 1) === 0;
    }
}
