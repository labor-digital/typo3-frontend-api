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
 * Last modified: 2019.08.19 at 09:43
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Normalizer;


use Neunerlei\Arrays\Arrays;
use stdClass;

/**
 * Class Normalizer
 *
 * @package    LaborDigital\Typo3FrontendApi\JsonApi\Normalizer
 *
 * @deprecated Will be removed in v10 use "woohoolabs/yang" instead!
 */
class Normalizer implements NormalizerInterface
{

    /**
     * Receives the serialized json api structure and normalizes it into a tree of structured data.
     * The linked relationships will automatically be resolved as properties of each entity.
     *
     * @param   array|\stdClass  $response
     *
     * @return array
     * @deprecated Will be removed in v10 use "woohoolabs/yang" instead!
     */
    public function normalize($response)
    {
        // Validate input
        if ($response instanceof stdClass) {
            $response = json_decode(json_encode($response), true);
        }
        if (! is_array($response) || empty($response["data"])) {
            return ["entities" => [], "result" => []];
        }

        // Prepare working data
        $data        = is_array($response["data"]) && Arrays::isSequential($response["data"]) ?
            $response["data"] : [$response["data"]];
        $included    = is_array($response["included"]) ? $response["included"] : [];
        $result      = $relationMap = [];
        $payloadType = null;

        // Loop through data entities
        foreach ($data as $k => $entity) {
            // Skip invalid entities
            if (! $this->isValidEntity($entity)) {
                continue;
            }

            // Get payload type
            if (empty($payloadType)) {
                $payloadType = $entity["type"];
            }

            // Add to result
            $result = $this->addResult($result, $entity);
            $result = $this->addEntity($result, $entity, $relationMap);
        }


        // Loop through included entities
        foreach ($included as $k => $entity) {
            $result = $this->addEntity($result, $entity);
        }

        // Resolve relationships of the payload type
        if (! is_null($payloadType)) {
            foreach ($result["entities"][$payloadType] as $k => &$entity) {
                if (empty($relationMap[$k])) {
                    continue;
                }
                foreach ($relationMap[$k] as $field => $rel) {
                    if (! is_array($rel["data"])) {
                        continue;
                    }

                    // Check for a one to one or one to many relation
                    if ($this->isValidEntity($rel["data"])) {
                        // Single entity relation
                        $entity[$field] = $result["entities"][$rel["data"]["type"]][$rel["data"]["id"]];
                    } else {
                        // Multi entity relation
                        $entity[$field] = [];
                        foreach ($rel["data"] as $_rel) {
                            if (! $this->isValidEntity($_rel["data"])) {
                                continue;
                            }
                            $entity[$field][] = $result["entities"][$_rel["data"]["type"]][$_rel["data"]["id"]];
                        }
                    }
                }
            }
        }

        // Build output object
        if (isset($response["meta"])) {
            $result["meta"] = $response["meta"];
        }
        if (isset($response["links"])) {
            $result["links"] = $response["links"];
        }

        // Done
        return $result;
    }

    /**
     * Internal helper which checks if a given element looks like a valid json api entity
     *
     * @param $entity
     *
     * @return bool
     */
    protected function isValidEntity($entity): bool
    {
        return is_array($entity) && isset($entity["type"]) && isset($entity["id"]);
    }

    /**
     * Adds the given entity to the result list and returns the changed array
     *
     * @param   array  $result
     * @param   array  $entity
     *
     * @return array
     */
    protected function addResult(array $result, array $entity): array
    {
        $result["result"][$entity["type"]][] = $entity["id"];

        return $result;
    }

    /**
     * Adds the given entity to the list of entities and returns the updated list
     *
     * @param   array  $result
     * @param   array  $entity
     * @param   array  $relationMap
     *
     * @return array
     */
    protected function addEntity(array $result, array $entity, array &$relationMap = []): array
    {
        // Build merged entity
        $_entity                                            = is_array($entity["attributes"]) ? $entity["attributes"] : [];
        $_entity["id"]                                      = $entity["id"];
        $result["entities"][$entity["type"]][$entity["id"]] = $_entity;

        // Resolve relationships
        if (empty($entity["relationships"])) {
            return $result;
        }
        $relationMap[$entity["id"]] = ! empty($entity["relationships"]) ? $entity["relationships"] : null;

        return $result;
    }
}
