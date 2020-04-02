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
 * Last modified: 2019.08.11 at 12:37
 */

namespace LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Transformer;


use LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService;
use LaborDigital\Typo3FrontendApi\JsonApi\Builtin\Resource\SiteConfigAwareTrait;
use LaborDigital\Typo3FrontendApi\JsonApi\Transformation\AbstractResourceTransformer;
use Neunerlei\Inflection\Inflector;
use Neunerlei\PathUtil\Path;
use Throwable;
use TYPO3\CMS\Core\Resource\File;

class FileResourceTransformer extends AbstractResourceTransformer {
	use SiteConfigAwareTrait;
	
	/**
	 * Defines how file types are mapped to their string representation
	 */
	protected const FILE_TYPE_MAP = [
		File::FILETYPE_UNKNOWN     => "unknown",
		File::FILETYPE_TEXT        => "text",
		File::FILETYPE_IMAGE       => "image",
		File::FILETYPE_AUDIO       => "audio",
		File::FILETYPE_VIDEO       => "video",
		File::FILETYPE_APPLICATION => "application",
	];
	
	/**
	 * @var \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService
	 */
	protected $falFileService;
	
	/**
	 * FileResourceTransformer constructor.
	 *
	 * @param \LaborDigital\Typo3BetterApi\FileAndFolder\FalFileService $falFileService
	 */
	public function __construct(FalFileService $falFileService) {
		$this->falFileService = $falFileService;
	}
	
	/**
	 * @inheritDoc
	 */
	public function transformValue($value): array {
		if (empty($value)) return ["id" => NULL];
		try {
			$fileInfo = $this->falFileService->getFileInfo($value);
			
			// Generate the type string by the numeric value
			$type = "unknown";
			if (isset(static::FILE_TYPE_MAP[$fileInfo->getType()]))
				$type = static::FILE_TYPE_MAP[$fileInfo->getType()];
			
			// Build information array
			$info = [
				"id"          => $fileInfo->getUid(),
				"isReference" => $fileInfo->isFileReference(),
				"type"        => $type,
				"filename"    => $fileInfo->getFileName(),
				"mime"        => $fileInfo->getMimeType(),
				"size"        => $fileInfo->getSize(),
				"extension"   => $fileInfo->getExtension(),
				"url"         => $fileInfo->getUrl(),
			];
			
			// Build special information
			if ($fileInfo->isVideo()) {
				$videoInfo = $fileInfo->getVideoInfo();
				$info["video"] = [
					"title"       => $videoInfo->getTitle(),
					"description" => $videoInfo->getDescription(),
					"autoPlay"    => $videoInfo->isAutoPlay(),
					"isYouTube"   => $videoInfo->isYouTube(),
					"isVimeo"     => $videoInfo->isVimeo(),
					"videoId"     => $videoInfo->getVideoId(),
				];
			} else if ($fileInfo->isImage()) {
				$imageInfo = $fileInfo->getImageInfo();
				$info["image"] = [
					"alt"       => $imageInfo->getAlt(),
					"title"     => $imageInfo->getTitle(),
					"desc"      => $imageInfo->getDescription(),
					"width"     => $imageInfo->getWidth(),
					"height"    => $imageInfo->getHeight(),
					"alignment" => $imageInfo->getImageAlignment(),
				];
				
				// Handle crop variants or advanced endpoint
				if (!empty($this->ConfigRepository->tool()->get("imaging", FALSE))) {
					// Make the identifier
					$identifier = $fileInfo->getFileName();
					$identifier = substr($identifier, 0, -strlen($fileInfo->getExtension()) - 1);
					$identifier = Inflector::toSlug($identifier);
					$identifier = preg_replace("~-+~", "-", $identifier);
					$identifier .= "." . $fileInfo->getHash();
					$identifier .= "." . ($fileInfo->isFileReference() ? "r" : "f") . $fileInfo->getUid();
					$identifier .= "." . $fileInfo->getExtension();
					
					// Advanced endpoint
					$endpointPath = $this->ConfigRepository->tool()->get("imaging.options.endpointDirectoryPath");
					$endpointUrl = Path::makeRelative($endpointPath, $this->TypoContext->getPathAspect()->getPublicPath());
					$info["url"] = $this->Links->getHost() . "/" . $endpointUrl .
						"/imaging.php?file=" . urlencode($identifier);
					$info["image"]["variants"] = array_keys($imageInfo->getCropVariants());
				} else {
					// Build all crop variants
					$variants = [];
					$file = $fileInfo->isFileReference() ? $fileInfo->getFileReference() : $fileInfo->getFile();
					foreach ($imageInfo->getCropVariants() as $k => $conf) {
						$processed = $this->falFileService->getResizedImage($file, ["crop" => $k]);
						$variants[$k] = [
							"url"    => $this->Links->getHost() . "/" .
								$processed->getPublicUrl(FALSE) . "?hash=" . md5($processed->getSha1()),
							"width"  => (int)$processed->getProperty("width"),
							"height" => (int)$processed->getProperty("height"),
							"size"   => $processed->getSize(),
						];
					}
					$info["image"]["variants"] = $variants;
				}
			}
			
			// Done
			return $info;
		} catch (Throwable $e) {
			// Make sure that a missing file reference does not crash the entire page
			return ["id" => NULL, "error" => "Failed to gather the file information!"];
		}
	}
}