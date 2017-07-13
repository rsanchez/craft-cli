<?php

namespace CraftCli\Command;

use Craft\ComponentType;
use Craft\Craft;
use ReflectionMethod;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
            array(
                'caches',
                'c',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Caches to clear',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $caches = '*';

        $tool = $this->craft->getComponent(
            'components'
        )->getComponentByTypeAndClass(ComponentType::Tool, 'ClearCaches');

        $reflectionMethod = new ReflectionMethod($tool, '_getFolders');
        $reflectionMethod->setAccessible(true);

        $values = $reflectionMethod->invoke($tool);
        $values['assetTransformIndex'] = Craft::t('Asset transform index');
        $values['assetIndexingData'] = Craft::t('Asset indexing data');
        $values['templateCaches'] = Craft::t('Template caches');

        $keys = array_keys($values);
        $options = array_values($values);


        if ($this->option('select')) {

            $questionHelper = $this->getHelperSet()->has('question')
                ? $this->getHelperSet()->get('question')
                : $this->getHelperSet()->get('dialog');

            if ($questionHelper instanceof QuestionHelper) {
                $question = new ChoiceQuestion(
                    'Select which caches to clear (separate multiple by comma)',
                    $options,
                    0
                );
                $question->setMultiselect(true);
                $selected = $questionHelper->ask(
                    $this->input,
                    $this->output,
                    $question
                );
            } else {
                $selected = $questionHelper->select(
                    $this->output,
                    'Select which caches to clear (separate multiple by comma)',
                    $options,
                    null,
                    false,
                    'Value "%s" is invalid',
                    true
                );
            }

            $caches = array();

            foreach ($selected as $index => $name) {
                $caches[] = $keys[$index];
            }
        }

        if ($this->option('caches')) {
            $selected = array_intersect($options, $this->option('caches'));
            $caches = array();
            foreach ($selected as $index => $name) {
                $caches[] = $keys[$index];
            }
        }


        $this->suppressOutput(
            function () use ($tool, $caches) {
                $tool->performAction(compact('caches'));
            }
        );

        $this->info('Cache(s) cleared.');
    }
}
