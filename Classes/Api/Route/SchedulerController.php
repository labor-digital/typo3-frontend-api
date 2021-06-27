<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.06.22 at 21:53
 */

declare(strict_types=1);
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
 * Last modified: 2020.01.16 at 14:25
 */

namespace LaborDigital\T3fa\Api\Route;


use LaborDigital\T3fa\Core\Routing\Controller\AbstractRouteController;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\UnauthorizedException;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Scheduler\Scheduler;

class SchedulerController extends AbstractRouteController
{
    
    /**
     * Executes the TYPO3 Scheduler as admin user.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function runAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        if (! isset($args['token']) || ! is_array($args['token'])) {
            throw new NotFoundException();
        }
        
        $typoContext = $this->cs()->typoContext;
        
        if (! $typoContext->env()->isDev() &&
            ! $this->validateToken($request, $args['token'], $args['allowTokenInQuery'] ?? false)) {
            throw new UnauthorizedException();
        }
        
        set_time_limit(max(60, (int)($args['maxExecutionTime'] ?? (60 * 10))));
        $startTime = microtime(true);
        
        $taskId = (int)($args['id'] ?? 0);
        [$messages, $hasError] = $this->cs()->simulator->runWithEnvironment(['asAdmin'],
            function () use ($taskId) {
                return $this->execute($taskId);
            }
        );
        
        if (empty($messages)) {
            $messages[] = 'Nothing to do! | OK';
        }
        
        return $this->getJsonResponse([
            'messages' => $messages,
            'timestamp' => time(),
            'timeTaken' => round((microtime(true) - $startTime), 4),
        ], $hasError ? 500 : 200);
    }
    
    /**
     * Checks if a valid token was provided in the request
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     * @param   array                                     $tokens
     * @param   bool                                      $allowQueryToken
     *
     * @return bool
     */
    protected function validateToken(ServerRequestInterface $request, array $tokens, bool $allowQueryToken): bool
    {
        $token = null;
        
        if ($request->hasHeader('x-t3fa-token')) {
            $token = $request->getHeaderLine('x-t3fa-token');
        }
        
        if ($token === null && $allowQueryToken) {
            $token = $request->getQueryParams()['token'] ?? null;
        }
        
        if (empty($token)) {
            return false;
        }
        
        return in_array($token, $tokens, true);
    }
    
    /**
     * Executes the scheduler either for a single task, or for all registered task
     *
     * @param   int  $taskId
     *
     * @return array
     * @throws \Throwable
     */
    protected function execute(int $taskId): array
    {
        $typoContext = $this->cs()->typoContext;
        $isDev = $typoContext->env()->isDev();
        $typoContext->language()->getCurrentBackendLanguage();
        $isSingleTask = $taskId !== 0;
        $messages = [];
        $hasError = false;
        
        $scheduler = $this->makeInstance(Scheduler::class);
        $hasTask = true;
        while ($hasTask) {
            $task = null;
            $hasTask = false;
            try {
                // Make debugging easier by forcing a reexecution
                if ($isDev && $isSingleTask) {
                    $task = $scheduler->fetchTask($taskId);
                    $task->unmarkAllExecutions();
                    $task->setRunOnNextCronJob(true);
                    $task->save();
                }
                
                $task = $scheduler->fetchTask($taskId);
                $hasTask = true;
                $result = $scheduler->executeTask($task);
                if ($result) {
                    $messages[] = $task->getTaskTitle() . ' | OK';
                }
            } catch (OutOfBoundsException $e) {
                $hasTask = false;
            } catch (Throwable $e) {
                if (isset($task)) {
                    $messages[] = $task->getTaskTitle() . ' | FAIL | ' . $e->getMessage();
                } else {
                    $messages[] = 'UNKNOWN TASK | FAIL | ' . $e->getMessage();
                }
                
                if ($isDev) {
                    throw $e;
                }
                
                $hasError = true;
                continue;
            }
            
            if ($isSingleTask) {
                break;
            }
        }
        
        // Record the run in the system registry
        $scheduler->recordLastRun('manual');
        
        return [$messages, $hasError];
    }
}
