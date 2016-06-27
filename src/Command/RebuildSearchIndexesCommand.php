<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Helper\ProgressBar;

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
    protected function fire()
    {
        // Truncate the searchindex table
        $this->craft->db->createCommand()->truncateTable('searchindex');

        // Get all the element IDs ever
        $result = $this->craft->db->createCommand()
            ->select('id, type')
            ->from('elements')
            ->queryAll();

        $progressBar = new ProgressBar($this->output, count($result));

        foreach ($result as $i => $row) {
            // Get the element type
            $elementType = $this->craft->elements->getElementType($row['type']);

            if (!$elementType) {
                $progressBar->setProgress($i + 1);

                continue;
            }

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

            $progressBar->setProgress($i + 1);
        }

        $progressBar->finish();

        $this->line('');

        $this->info('Search indexes have been rebuilt.');
    }
}
