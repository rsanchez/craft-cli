<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputOption;
use Craft\ComponentType;
use ReflectionMethod;
use Craft\Craft;

class ClearCacheCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'clear:cache';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Clear Craft cache(s).';

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
            array(
                'select',
                's',
                InputOption::VALUE_NONE,
                'Allows you to select from a list of caches to clear.',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $caches = '*';

        $tool = $this->craft->getComponent('components')->getComponentByTypeAndClass(ComponentType::Tool, 'ClearCaches');

        if ($this->option('select')) {
            $reflectionMethod = new ReflectionMethod($tool, '_getFolders');

            $reflectionMethod->setAccessible(true);

            $values = $reflectionMethod->invoke($tool);

            $values['assetTransformIndex'] = Craft::t('Asset transform index');

            $values['assetIndexingData'] = Craft::t('Asset indexing data');

            $values['templateCaches'] = Craft::t('Template caches');

            $keys = array_keys($values);

            $options = array_values($values);

            $dialog = $this->getHelper('dialog');

            $selected = $dialog->select(
                $this->output,
                'Select which caches to clear (separate multiple by comma)',
                $options,
                null,
                false,
                'Value "%s" is invalid',
                true
            );

            $caches = array();

            foreach ($selected as $index) {
                $caches[] = $keys[$index];
            }
        }

        $this->suppressOutput(function () use ($tool, $caches) {
            $tool->performAction(compact('caches'));
        });

        $this->info('Cache(s) cleared.');
    }
}
