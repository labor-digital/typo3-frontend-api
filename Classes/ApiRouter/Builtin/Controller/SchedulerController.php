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
 * Last modified: 2020.01.16 at 14:25
 */

namespace LaborDigital\Typo3FrontendApi\ApiRouter\Builtin\Controller;


use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector;
use LaborDigital\Typo3FrontendApi\ApiRouter\Controller\AbstractRouteController;
use LaborDigital\Typo3FrontendApi\ApiRouter\Traits\SimpleTokenAuthControllerTrait;
use LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository;
use League\Route\Http\Exception\NotFoundException;
use Neunerlei\Arrays\Arrays;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Scheduler\Scheduler;

class SchedulerController extends AbstractRouteController {
	use SimpleTokenAuthControllerTrait;
	
	/**
	 * @var \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository
	 */
	protected $configRepository;
	
	/**
	 * SchedulerController constructor.
	 *
	 * @param \LaborDigital\Typo3FrontendApi\ExtConfig\FrontendApiConfigRepository $configRepository
	 */
	public function __construct(FrontendApiConfigRepository $configRepository) {
		$this->configRepository = $configRepository;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function configureRoutes(RouteCollector $routes) {
		if (!class_exists(Scheduler::class)) return;
		$routes->get("/scheduler/run/{id}", "runAction", ["useCache" => FALSE]);
		$routes->get("/scheduler/run", "runAction", ["useCache" => FALSE]);
	}
	
	/**
	 * Executes the TYPO3 Scheduler as admin user.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param array                                    $args
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \League\Route\Http\Exception\NotFoundException
	 */
	public function runAction(ServerRequestInterface $request, array $args): ResponseInterface {
		
		// Check if the scheduler route is enabled
		if (!$this->configRepository->tool()->get("scheduler.enabled", FALSE))
			throw new NotFoundException();
		
		// Validate the request
		$this->validateTokenOrDie($request,
			$this->configRepository->tool()->get("scheduler.allowTokenInQuery", FALSE));
		
		// Prepare the execution time
		set_time_limit($this->configRepository->tool()->get("scheduler.maxExecutionType", 60 * 10));
		$startTime = microtime(TRUE);
		
		// Check if we are running a single task
		$taskId = (int)Arrays::getPath($args, ["id"], 0);
		$isSingleTask = $taskId !== 0;
		
		// Prepare the result
		$messages = [];
		$hasError = FALSE;
		
		// Make sure we have a user and a language
		$this->Simulator->runAsAdmin(function () use (&$messages, &$hasError, $taskId, $isSingleTask) {
			$this->TypoContext->getLanguageAspect()->getCurrentBackendLanguage();
			$isDev = $this->TypoContext->getEnvAspect()->isDev();
			
			// Create the scheduler instance
			$scheduler = $this->Container->get(Scheduler::class);
			$hasTask = TRUE;
			while ($hasTask) {
				$task = NULL;
				$hasTask = FALSE;
				try {
					// Make debugging easier by forcing a reexecution
					if ($isDev && $isSingleTask) {
						$task = $scheduler->fetchTask($taskId);
						$task->unmarkAllExecutions();
						$task->setRunOnNextCronJob(TRUE);
						$task->save();
					}
					$task = $scheduler->fetchTask($taskId);
					$hasTask = TRUE;
					$result = $scheduler->executeTask($task);
					if ($result) $messages[] = $task->getTaskTitle() . " | OK";
				} catch (OutOfBoundsException $e) {
					$hasTask = FALSE;
				} catch (Throwable $e) {
					if (isset($task)) $messages[] = $task->getTaskTitle() . " | FAIL | " . $e->getMessage();
					else $messages[] = "UNKNOWN TASK | FAIL | " . $e->getMessage();
					if ($isDev) throw $e;
					$hasError = TRUE;
					continue;
				}
				if ($isSingleTask) break;
			}
			
			// Record the run in the system registry
			$scheduler->recordLastRun('manual');
			
		});
		
		// Nothing done
		if (empty($messages)) $messages[] = "Nothing to do! | OK";
		
		// Build the response
		return $this->getJsonResponse([
			"messages"  => $messages,
			"timestamp" => time(),
			"timeTaken" => round((microtime(TRUE) - $startTime), 4),
		], $hasError ? 500 : 200);
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getTokens(): array {
		return $this->configRepository->tool()->get("scheduler.token");
	}
	
	
}