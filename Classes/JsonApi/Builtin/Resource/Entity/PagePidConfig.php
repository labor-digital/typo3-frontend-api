<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.04.19 at 18:12
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\Entity;


use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\SelfTransformingInterface;

class PagePidConfig implements SelfTransformingInterface
{


    /**
     * The pid that was used to request the pid context
     *
     * @var int
     */
    protected $pid;

    /**
     * The list of localized pids
     *
     * @var array
     */
    protected $localPids;

    /**
     * PagePid constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext  $context
     */
    public function __construct(int $pid, TypoContext $context)
    {
        $this->pid       = $pid;
        $this->localPids = $context->getPidAspect()->getAllPids();
    }

    /**
     * Returns the pid that was used to request the pid context
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        $list         = $this->localPids;
        $list["id"]   = $this->pid;
        $list["hash"] = md5(\GuzzleHttp\json_encode($this->localPids));

        return $list;
    }

}
