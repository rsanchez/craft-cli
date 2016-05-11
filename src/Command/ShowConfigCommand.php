<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use ReflectionClass;

class ShowConfigCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'show:config';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Show config items.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'name',
                InputArgument::OPTIONAL,
                'Which config item do you want to show? (Leave blank to show all)',
            ),
            array(
                'file',
                InputArgument::OPTIONAL,
                'From which config file do you want to load? (Leave blank to load from general)',
            ),
        );
    }

    protected function dump($value)
    {
        if (is_string($value)) {
            return wordwrap($value, 40, "\n", true);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        ob_start();

        var_dump($value);

        $value = ob_get_clean();

        // remove trailing newline
        $value = mb_substr($value, 0, -1);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $name = $this->argument('name');

        $file = $this->argument('file');

        if ($name) {
            if (strpos($name, '.') !== false) {
                list($file, $name) = explode('.', $name, 2);
            }

            if ($file) {
                $value = $this->craft->getComponent('config')->get($name, $file);
            } else {
                $value = $this->craft->getComponent('config')->get($name);
            }

            $this->line($this->dump($value));

            return;
        }

        $headers = array('File', 'Name', 'Value');

        $rows = array();

        $configService = $this->craft->getComponent('config');

        $reflectionClass = new ReflectionClass($configService);

        $reflectionProperty = $reflectionClass->getProperty('_loadedConfigFiles');

        $reflectionProperty->setAccessible(true);

        $configFiles = $reflectionProperty->getValue($configService);

        foreach ($configFiles as $file => $config) {
            ksort($config);

            foreach ($config as $key => $value) {
                $rows[] = array($file, $key, $this->dump($value));
            }
        }

        $this->table($headers, $rows);
    }
}
