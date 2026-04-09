<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_EXIF\Service;

use OC\Files\View;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files_FullTextSearch\Model\AFilesDocument;
use OCP\FullTextSearch\Model\ISearchRequest;
use Psr\Log\LoggerInterface;
use Throwable;

class ExifService {

	private const PART_NAME = 'exif';

	public function __construct(
		private ConfigService $configService,
		private MetadataExtractionService $metadataExtractionService,
		private LoggerInterface $logger
	) {
	}

	public function onFileIndexing(GenericEvent $event): void {
		$file = $event->getArgument('file');
		if (!$file instanceof File) {
			return;
		}

		$document = $event->getArgument('document');
		if (!$document instanceof AFilesDocument) {
			return;
		}

		$this->extractAndAttachMetadata($document, $file);
	}

	public function onSearchRequest(GenericEvent $event): void {
		$request = $event->getArgument('request');
		if ($request instanceof ISearchRequest) {
			$request->addPart(self::PART_NAME);
		}
	}

	private function extractAndAttachMetadata(AFilesDocument $document, File $file): void {
		if (!$this->configService->optionIsSelected(ConfigService::EXIF_ENABLED)) {
			return;
		}

		$mimeType = $document->getMimetype();
		if (!$this->isMimeTypeEnabled($mimeType)) {
			return;
		}

		$maxSizeBytes = $this->configService->getMaxSizeBytes();
		if ($maxSizeBytes > 0 && $file->getSize() > $maxSizeBytes) {
			return;
		}

		try {
			$path = $this->getAbsolutePath($file);
			$metadata = $this->metadataExtractionService->extract($path, $mimeType);
			if ($metadata === []) {
				return;
			}

			$document->addPart(self::PART_NAME, MetadataTextFormatter::flatten($metadata));

			$more = method_exists($document, 'getMore') ? $document->getMore() : [];
			if (!is_array($more)) {
				$more = [];
			}
			$more['exif'] = $metadata;
			$document->setMore($more);
		} catch (Throwable $e) {
			$this->logger->debug('EXIF extraction failed', [
				'exception' => $e,
				'documentId' => $document->getId(),
				'path' => $document->getPath(),
				'mime' => $mimeType
			]);
		}
	}

	private function isMimeTypeEnabled(string $mimeType): bool {
		if (strpos($mimeType, 'image/jpeg') === 0) {
			return $this->configService->optionIsSelected(ConfigService::EXIF_FORMAT_JPEG);
		}

		if (strpos($mimeType, 'image/tiff') === 0) {
			return $this->configService->optionIsSelected(ConfigService::EXIF_FORMAT_TIFF);
		}

		if (strpos($mimeType, 'image/png') === 0) {
			return $this->configService->optionIsSelected(ConfigService::EXIF_FORMAT_PNG);
		}

		if (strpos($mimeType, 'image/heic') === 0) {
			return $this->configService->optionIsSelected(ConfigService::EXIF_FORMAT_HEIC);
		}

		return false;
	}

	private function getAbsolutePath(File $file): string {
		$view = new View('');

		return $view->getLocalFile($file->getPath());
	}
}
