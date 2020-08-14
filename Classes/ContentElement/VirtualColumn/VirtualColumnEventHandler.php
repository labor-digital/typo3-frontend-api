<?php
declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2019.08.27 at 17:34
 */

namespace LaborDigital\Typo3FrontendApi\ContentElement\VirtualColumn;


use LaborDigital\Typo3BetterApi\DataHandler\DataHandlerActionContext;
use LaborDigital\Typo3BetterApi\Event\Events\BackendListLabelFilterEvent;
use LaborDigital\Typo3BetterApi\Event\Events\DataHandlerDbFieldsFilterEvent;
use LaborDigital\Typo3BetterApi\Event\Events\DataHandlerRecordInfoFilterEvent;
use LaborDigital\Typo3BetterApi\Event\Events\RefIndexRecordDataFilterEvent;
use LaborDigital\Typo3FrontendApi\Domain\Table\Override\TtContentOverrides;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use Neunerlei\Arrays\ArrayGeneratorException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;
use TYPO3\CMS\Core\SingletonInterface;

class VirtualColumnEventHandler implements SingletonInterface, LazyEventSubscriberInterface
{

    /**
     * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
     */
    protected $configRepository;

    /**
     * This is used in the save filter to store the list of virtual columns that are currently handled
     *
     * @var array|null
     */
    protected $currentVirtualColumns;

    /**
     * Holds the list of the virtual values in the database
     *
     * @var array
     */
    protected $currentVirtualValues;

    /**
     * VirtualColumnEventHandler constructor.
     *
     * @param   \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository  $configRepository
     */
    public function __construct(FrontendApiConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @inheritDoc
     */
    public static function subscribeToEvents(EventSubscriptionInterface $subscription)
    {
        $subscription->subscribe(DataHandlerRecordInfoFilterEvent::class, '__dataHandlerRecordInfoFilter');
        $subscription->subscribe(DataHandlerDbFieldsFilterEvent::class, '__dataHandlerFieldArrayFilter');
        $subscription->subscribe(RefIndexRecordDataFilterEvent::class, '__refIndexFieldFilter');
        $subscription->subscribe(BackendListLabelFilterEvent::class, '__onBackendListLabelFilterEvent', ['priority' => 500]);

    }

    /**
     * The filter to prepare the saving of virtual columns when the data handler processes the record
     *
     * @param   \LaborDigital\Typo3BetterApi\DataHandler\DataHandlerActionContext  $context
     */
    public function saveFilter(DataHandlerActionContext $context): void
    {
        // Clear the virtual columns
        $this->currentVirtualColumns = null;

        // Check if we can handle this row
        $row = $context->getRow();
        if (! $this->isViableRow($row)) {
            return;
        }

        // Load the virtual values from the database
        $this->currentVirtualValues = [];
        $id                         = $context->getUid();
        if ((((int)$id) . '') === $id . '' && strpos((string)$id, 'NEW') !== 0) {
            // Update existing record
            /** @var \LaborDigital\Typo3BetterApi\Event\Events\DataHandlerSaveFilterEvent $event */
            $event         = $context->getEvent();
            $dataHandler   = $event->getDataHandler();
            $currentValues = $dataHandler->recordInfo('tt_content', $context->getUid(), TtContentOverrides::VIRTUAL_COLUMN_FIELD);
            $currentValues = Arrays::shorten($currentValues);
            if (! empty($currentValues) && is_string($currentValues)) {
                $this->currentVirtualValues = Arrays::makeFromJson($currentValues);
            }

            // Prepare the current values
            $tcaCols = $GLOBALS['TCA']['tt_content']['columns'];
            foreach ($this->currentVirtualValues as $k => $v) {
                // Strip out fields we don't have in the TCA
                if (! isset($tcaCols[$k])) {
                    unset($this->currentVirtualValues[$k]);
                    continue;
                }

                // Make sure to override empty values that are coming in
                if ($row[$k] === '') {
                    $this->currentVirtualValues[$k] = '';
                    continue;
                }
            }
        }

        // Set our virtual columns
        $this->currentVirtualColumns = $this->getVirtualColumns($row);
    }

    /**
     * Filter to provide the shortcode row fields to the backend list label filter service
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\BackendListLabelFilterEvent  $event
     */
    public function __onBackendListLabelFilterEvent(BackendListLabelFilterEvent $event): void
    {
        $event->setRow($this->hydrateVirtualColumns($event->getRow(), false));
    }

    /**
     * The form filter to unpack the virtual columns into the form array
     *
     * @param   \LaborDigital\Typo3BetterApi\DataHandler\DataHandlerActionContext  $context
     */
    public function formFilter(DataHandlerActionContext $context): void
    {
        $context->setValue($this->hydrateVirtualColumns($context->getValue()));
    }

    /**
     * Is used to block database requests to virtual columns when the data handler processes the record
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\DataHandlerRecordInfoFilterEvent  $event
     */
    public function __dataHandlerRecordInfoFilter(DataHandlerRecordInfoFilterEvent $event): void
    {
        // Ignore if there are multiple fields
        $fields = $event->getFieldList();
        if ($fields === '*' || strpos($fields, ',') !== false) {
            return;
        }
        $allVirtualColumns = $this->configRepository->contentElement()->getAllVirtualColumns();
        if (! isset($allVirtualColumns[$fields])) {
            return;
        }

        // Update the information resolver closure.
        $event->setConcreteInfoProvider(function (string $field) {
            return [$field => Arrays::getPath($this->currentVirtualValues, [$field])];
        });
    }

    /**
     * Is used to remove virtual columns when the data handler processes the record
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\DataHandlerDbFieldsFilterEvent  $event
     */
    public function __dataHandlerFieldArrayFilter(DataHandlerDbFieldsFilterEvent $event): void
    {
        // Ignore if we are not listening...
        if ($event->getTableName() !== 'tt_content') {
            return;
        }

        // Load the field array
        $row = $event->getRow();

        // Handle virtual column values
        if (! empty($this->currentVirtualColumns)) {
            // Extract the virtual columns out of the field array
            $virtualFieldArray = empty($this->currentVirtualValues) ? [] : $this->currentVirtualValues;
            foreach ($this->currentVirtualColumns as $column) {
                if (isset($row[$column])) {
                    $virtualFieldArray[$column] = $row[$column];
                }
            }

            // Add the values to the storage slot
            $row[TtContentOverrides::VIRTUAL_COLUMN_FIELD] = json_encode($virtualFieldArray);
        }

        // Remove all virtual columns from the field array
        $allVirtualColumns = $this->configRepository->contentElement()->getAllVirtualColumns();
        $row               = array_diff_key($row, $allVirtualColumns);

        // Done
        $event->setRow($row);
    }

    /**
     * This listener makes sure the v-columns are correctly de-serialized when the ref index is generated
     *
     * @param   \LaborDigital\Typo3BetterApi\Event\Events\RefIndexRecordDataFilterEvent  $event
     */
    public function __refIndexFieldFilter(RefIndexRecordDataFilterEvent $event)
    {
        // Ignore if we are not listening...
        if ($event->getTableName() !== 'tt_content') {
            return;
        }

        // Load the field array
        $row = $event->getRow();

        // Unpack the vcols
        if (empty($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD])) {
            return;
        }
        try {
            $row = Arrays::merge($row, Arrays::makeFromJson($row[TtContentOverrides::VIRTUAL_COLUMN_FIELD]));
        } catch (ArrayGeneratorException $exception) {
            // Ignore exception and continue silently...
        }

        // Update the row
        $event->setRow($row);
    }

    /**
     * Internal helper which checks if the current field array contains virtual columns and therefore must be processed.
     *
     * @param   array  $row
     *
     * @return bool
     */
    protected function isViableRow(array $row): bool
    {
        return ! empty($this->getVirtualColumns($row));
    }

    /**
     * Returns the list of virtual columns for a given field array
     *
     * @param   array  $row
     *
     * @return array
     */
    protected function getVirtualColumns(array $row): array
    {
        $cType = Arrays::getPath($row, ['CType']);
        if (empty($cType)) {
            return [];
        }

        if (! is_string($cType)) {
            $cType = reset($cType);
        }

        return $this->configRepository->contentElement()->getVirtualColumnsFor($cType);
    }


    /**
     * Rehydrates the virtual column data for the given row.
     * The result is the row which has all virtual columns merged in.
     *
     * @param   array  $row            The row to read the virtual columns from and inject the hydrated values to.
     * @param   bool   $useVColPrefix  By default the columns are added with their vCol_signature_ prefix,
     *                                 because it is required by the form handler. Some cases require it to be injected
     *                                 without that prefix. If you want to skip the prefix set this to FALSE
     *
     * @return array
     */
    protected function hydrateVirtualColumns(array $row, bool $useVColPrefix = true): array
    {
        if (! $this->isViableRow($row)) {
            return $row;
        }

        $columns               = $this->getVirtualColumns($row);
        $virtualColumns        = array_fill_keys($columns, null);
        $virtualColumnDefaults = array_intersect_key($row, $virtualColumns);

        $storedColumns = Arrays::getPath($row, [TtContentOverrides::VIRTUAL_COLUMN_FIELD]);
        if (empty($storedColumns)) {
            $storedColumns = [];
        } else {
            $storedColumns = Arrays::makeFromJson($storedColumns);
        }
        $virtualColumns = Arrays::merge($virtualColumns, $virtualColumnDefaults, $storedColumns);

        if (! $useVColPrefix) {
            $virtualColumns = Arrays::renameKeys($virtualColumns, array_flip($columns));
        }

        return Arrays::attach($row, $virtualColumns);
    }
}
