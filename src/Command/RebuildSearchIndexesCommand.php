<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;

class RebuildSearchIndexesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'rebuild:searchindexes';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Rebuild Search Indexes';

    /**
     * {@inheritdoc}
     */
    protected $showsDuration = true;

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'offset',
                InputArgument::OPTIONAL,
                'Offset the query',
                0,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $offset = $this->argument('offset');

        // Get all the element IDs ever
        $result = $this->craft->db->createCommand()
            ->select('id, type')
            ->from('elements')
            ->offset($offset)
            ->queryAll();

        $progressBar = new ProgressBar($this->output, count($result) + $offset);

        foreach ($result as $i => $row) {
            // Get the element type
            $elementType = $this->craft->elements->getElementType($row['type']);

            if (!$elementType) {
                $progressBar->setProgress($offset + $i + 1);

                continue;
            }

            // delete existing indexes
            $this->craft->db->createCommand()->delete('searchindex', 'elementId = :elementId', array(':elementId' => $row['id']));

            if ($elementType->isLocalized()) {
                $localeIds = $this->craft->i18n->getSiteLocaleIds();
            } else {
                $localeIds = array($this->craft->i18n->getPrimarySiteLocaleId());
            }

            $criteria = $this->craft->elements->getCriteria($row['type'], array(
                'id'            => $row['id'],
                'status'        => null,
                'localeEnabled' => null,
            ));

            foreach ($localeIds as $localeId) {
                $criteria->locale = $localeId;
                $element = $criteria->first();

                if (!$element) {
                    continue;
                }

                $this->craft->search->indexElementAttributes($element);

                if (!$elementType->hasContent()) {
                    continue;
                }

                $fieldLayout = $element->getFieldLayout();
                $keywords = array();

                foreach ($fieldLayout->getFields() as $fieldLayoutField) {
                    $field = $fieldLayoutField->getField();

                    if (!$field) {
                        continue;
                    }

                    $fieldType = $field->getFieldType();

                    if (!$fieldType) {
                        continue;
                    }

                    $fieldType->element = $element;

                    $handle = $field->handle;

                    // Set the keywords for the content's locale
                    $fieldSearchKeywords = $fieldType->getSearchKeywords($element->getFieldValue($handle));
                    $keywords[$field->id] = $fieldSearchKeywords;

                    $this->craft->search->indexElementFields($element->id, $localeId, $keywords);
                }
            }

            $progressBar->setProgress($offset + $i + 1);
        }

        $progressBar->finish();

        $this->line('');

        $this->info('Search indexes have been rebuilt.');
    }
}
