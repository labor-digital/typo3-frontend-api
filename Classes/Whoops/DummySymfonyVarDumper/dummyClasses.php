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
 * Last modified: 2019.08.27 at 00:04
 */

namespace Symfony\Component\VarDumper\Dumper {
	
	use LaborDigital\Typo3FrontendApi\Whoops\ErrorHandler;
	use ReflectionObject;
	use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
	use Whoops\Util\HtmlDumperOutput;
	
	class HtmlDumper {
		public function dump($var, HtmlDumperOutput $output) {
			// Disable value rendering
			if (ErrorHandler::renderValues()) $result = DebuggerUtility::var_dump($var);
			else {
				if (is_scalar($var)) $result = (string)$var;
				else $result = "[VALUE RENDERING DISABLED: " . gettype($var) .
					(is_object($var) ? ": " . get_class($var) : "") . "]";
			}
			
			// Adjust debug output to match whoops at least somewhat...
			$result = str_replace("<dfn>\$var</dfn>", "", $result);
			
			// Convince whoops to take our output
			$ref = new ReflectionObject($output);
			$prop = $ref->getProperty("output");
			$prop->setAccessible(TRUE);
			$prop->setValue($output, $result);
		}
		
		public function setStyles() { }
	}
	
}

namespace Symfony\Component\VarDumper\Caster {
	
	class Caster {
		public const EXCLUDE_VERBOSE = TRUE;
	}
	
}

namespace Symfony\Component\VarDumper\Cloner {
	
	class AbstractCloner {
		public static $defaultCasters = [];
		
		public function cloneVar($val, ...$args) {
			return $val;
		}
	}
	
	class VarCloner extends AbstractCloner {
		public function addCasters(...$args) { }
	}
}