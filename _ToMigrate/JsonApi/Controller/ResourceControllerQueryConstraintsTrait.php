<?php
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
 * Last modified: 2019.08.19 at 12:48
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Controller;


use LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery;
use LaborDigital\Typo3FrontendApi\JsonApi\InvalidJsonApiConfigurationException;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfigGenerator;
use League\Route\Http\Exception\BadRequestException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;
use Neunerlei\Options\Options;
use Neunerlei\TinyTimy\DateTimy;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

trait ResourceControllerQueryConstraintsTrait
{

    /**
     * @var TransformerConfigGenerator
     */
    protected $__transformerConfigGenerator;

    /**
     * Inject the config generator
     *
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Transformation\TransformerConfigGenerator  $transformerConfigGenerator
     */
    public function injectQueryConstraintsTraitTransformerConfigGenerator(TransformerConfigGenerator $transformerConfigGenerator)
    {
        $this->__transformerConfigGenerator = $transformerConfigGenerator;
    }

    /**
     * This helper can be used to add the json api filter constraints to the given query object.
     * Currently it is only possible to add the filter to betterQuery objects.
     *
     * @param   BetterQuery                $query          The query to add the filter to
     * @param   ResourceControllerContext  $context        The context object to get the request from
     * @param   array                      $allowedFields  The list of allowed filter properties. All non defined
     *                                                     properties
     *                                                     will be ignored
     * @param   array                      $likeFields     The list of allowed fields that should be treated as "LIKE" in
     *                                                     your SQL database
     *
     * @return \LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery
     * @see https://jsonapi.org/format/#fetching-filtering
     */
    protected function addFilterConstraint(BetterQuery $query, ResourceControllerContext $context, array $allowedFields, array $likeFields = []): BetterQuery
    {
        // Combine the like fields into the allowed fields
        $allowedFields = array_unique(Arrays::attach($allowedFields, $likeFields));

        // Get the filter
        $localWhere = [];
        foreach ($context->getQuery()->getFilters() as $field => $options) {
            if (! in_array($field, $allowedFields)) {
                continue;
            }

            // Special handling for the pid field
            if ($field === "pid") {
                $query = $query->withPids(array_map(function ($v) {
                    return (int)$v;
                }, Arrays::makeFromStringList($options)));
                continue;
            }

            // Default handling
            $optionParts = Arrays::makeFromStringList($options);
            $optionWhere = [];
            foreach ($optionParts as $value) {
                if (! empty($optionWhere)) {
                    $optionWhere[] = "OR";
                }
                if (in_array($field, $likeFields)) {
                    $optionWhere[] = [$field . " LIKE" => "%" . $value . "%"];
                } else {
                    $optionWhere[] = [$field => $value];
                }
            }
            if (empty($optionWhere)) {
                continue;
            }
            $localWhere[] = $optionWhere;
        }

        // Update the queries where if required
        if (empty($localWhere)) {
            return $query;
        }

        return $query->withWhere($localWhere, "resourceFilter");
    }

    /**
     * Is used to filter the query by a custom field
     *
     * @param   BetterQuery                $query     The query to add the filter constraint to
     * @param   ResourceControllerContext  $context   The context object to get the request from
     * @param   string                     $field     The name of the filter field we have to read from the query parameters
     * @param   callable                   $callback  A callback which is called when we found the required $field in the
     *                                                filter property of the query parameters.
     *                                                The callback will receive the $query, the content of the $field,
     *                                                the $context object and the name of the $field as parameters.
     *                                                The callback must always return a better query object!
     *
     * @return \LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery
     * @throws \LaborDigital\Typo3FrontendApi\JsonApi\InvalidJsonApiConfigurationException
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    public function addCustomFilterConstraint(BetterQuery $query, ResourceControllerContext $context, string $field, callable $callback): BetterQuery
    {
        if (! isset($context->getQuery()->getFilters()[$field])) {
            return $query;
        }
        $queryResult = call_user_func($callback, $query, $context->getQuery()->getFilters()[$field], $context, $field);
        if (! $queryResult instanceof BetterQuery) {
            throw new InvalidJsonApiConfigurationException("The custom constraint filter callback has to return an object of type: " . BetterQuery::class);
        }

        return $queryResult;
    }

    /**
     * Can be used to add a date range constraint on a query object. It will also automatically add additional
     * metadata to the response object so the frontend can read the given constraints.
     *
     * It is optional if you work on a single field or with startDate and endDate fields.
     *
     * If you just want to work with a single date field, just omit the $endDateProperty.
     *
     * @param   BetterQuery                $query              The query to add the constraint to
     * @param   ResourceControllerContext  $context            The context to read the request from and which is used to
     *                                                         add the additional metadata
     * @param   string                     $startDateProperty  The extbase model's property that holds the start date
     * @param   string|null                $endDateProperty    The extbase model's property that holds the end date
     * @param   array                      $options            Additional options for advanced configuration -
     *                                                         resolveProperty string ("both"): Can be used to use only a
     *                                                         single field for a lookup. By default the startDate and the
     *                                                         endDate fields are matched against the constraints,
     *                                                         sometimes you don't want that behaviour tho. Set this to
     *                                                         "start" to use the $startDateProperty and "end" to use the
     *                                                         $endDateProperty to resolve if an element is in the date
     *                                                         range or not
     *
     * @return \LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    protected function addDateRangeFilterConstraint(
        BetterQuery $query,
        ResourceControllerContext $context,
        string $startDateProperty,
        ?string $endDateProperty = null,
        array $options = []
    ): BetterQuery {
        // Prepare options
        $options = Options::make($options, [
            "resolveProperty" => [
                "type"    => "string",
                "values"  => ["both", "start", "end"],
                "default" => "both",
            ],
        ]);

        // Get the date constraints
        // Start date
        $startDateField = $startDateProperty;
        $minDate        = $query->withLimit(1)->withOrder($startDateProperty, "asc")->getFirst(true);
        if (! empty($minDate) && ! isset($minDate[$startDateField])) {
            $startDateField = Inflector::toDatabase($startDateField);
        }
        $minDate = new DateTimy(empty($minDate) || ! isset($minDate[$startDateField]) ?
            0 : $minDate[$startDateField]);
        $minDate->setTime(0, 0, 0);

        // End date
        $endDateField = $endDateProperty;
        if ($endDateField === null) {
            $endDateField = $startDateProperty;
        }
        $maxDate = $query->withLimit(1)->withOrder($endDateField, "desc")->getFirst(true);
        if (! empty($maxDate) && ! isset($maxDate[$endDateField])) {
            $endDateField = Inflector::toDatabase($endDateField);
        }
        $maxDate = new DateTimy(empty($maxDate) || ! isset($maxDate[$endDateField]) ?
            0 : $maxDate[$endDateField]);
        $maxDate->setTime(23, 59, 59);

        // Range start
        $filter     = $context->getQuery()->getFilters();
        $rangeStart = ! empty($filter["rangeStart"]) ? new DateTimy((string)$filter["rangeStart"]) : $minDate;
        if ($rangeStart < $minDate) {
            $rangeStart = $minDate;
        } elseif ($rangeStart > $maxDate) {
            $rangeStart = $maxDate;
        }

        // Range end
        $rangeEnd = ! empty($filter["rangeEnd"]) ? new DateTimy((string)$filter["rangeEnd"]) : $maxDate;
        if ($rangeEnd < $minDate) {
            $rangeEnd = $minDate;
        } elseif ($rangeEnd > $maxDate) {
            $rangeEnd = $maxDate;
        }
        if ($rangeEnd < $rangeStart) {
            $tmp        = $rangeStart;
            $rangeStart = $rangeEnd;
            $rangeEnd   = $tmp;
        }

        // Guess the range end if required. This is used for "short-dates" like "YYYY-MM"
        if (! empty($filter["rangeEnd"]) && substr_count($filter["rangeEnd"], "-") === 1) {
            $rangeEnd->modify("+1 month")->modify("-1 day");
        }

        // Add date range meta
        $meta              = $context->getMeta();
        $meta["dateRange"] = [
            "min"        => $minDate->formatJs(),
            "max"        => $maxDate->formatJs(),
            "rangeStart" => $rangeStart->formatJs(),
            "rangeEnd"   => $rangeEnd->formatJs(),
        ];
        $context->setMeta($meta);

        // Check if we should switch the resolver field
        if ($options["resolveProperty"] !== "both") {
            if ($options["resolveProperty"] === "start") {
                $endDateField = $startDateField;
            } else {
                $startDateField = $endDateField;
            }
        }

        // Add the constraint to the query object
        return $query->withWhere([
            function (QueryInterface $query) use ($startDateField, $endDateField, $rangeStart, $rangeEnd) {
                return $query->logicalAnd([
                    $query->greaterThanOrEqual($endDateField, $rangeStart),
                    $query->lessThanOrEqual($startDateField, $rangeEnd),
                ]);
            },
        ], "dateTimeRange");
    }

    /**
     * This helper is used to apply the sorting order to the given query object
     * Currently it is only possible to add the filter to betterQuery objects.
     *
     * @param   \LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery                  $query
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext  $context
     * @param   array                                                                        $allowedFields
     *
     * @return \LaborDigital\Typo3BetterApi\Domain\BetterQuery\BetterQuery
     * @throws \League\Route\Http\Exception\BadRequestException
     * @see https://jsonapi.org/format/#fetching-sorting
     */
    protected function addSortingConstraint(BetterQuery $query, ResourceControllerContext $context, array $allowedFields): BetterQuery
    {
        // Get the sort fields
        $config   = null;
        $ordering = [];
        foreach ($context->getQuery()->getSorting() as $field => $direction) {
            // Check if a sub field is required
            $subField = null;
            if (stripos($field, ".") !== false) {
                $field    = Arrays::makeFromStringList($field, ".");
                $_field   = array_shift($field);
                $subField = array_shift($field);
                $field    = $_field;

                // Translate the given resource Type into the property name
                if (is_null($config)) {
                    $config = $this->__transformerConfigGenerator->makeTransformerConfigFor(
                        TransformerConfigGenerator::EMPTY_VALUE_MARKER, $context->getResourceType(), $context->getResourceConfig());
                }
                foreach ($config->includes as $property => $include) {
                    if ($include["resourceType"] !== $field) {
                        continue;
                    }
                    $field = $property;
                    break;
                }
            }

            // Validate the field
            if (! in_array($field, $allowedFields)) {
                throw new BadRequestException("The sort field " . $field . " is not allowed");
            }

            // Add the ordering
            if (! empty($subField)) {
                $field .= "." . $subField;
            }
            $ordering[$field] = $direction;
        }

        // Set the ordering
        $ordering = array_merge($query->getQuery()->getOrderings(), $ordering);

        return $query->withOrder($ordering);
    }
}
