<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Listeners;

use OCA\Files_FullTextSearch_Metadata\Service\ConfigService;
use OCA\Files_FullTextSearch_Metadata\Service\ExifService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventListener;

class GenericListener implements IEventListener {

	public function __construct(
		private ConfigService $configService,
		private ExifService $exifService
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof GenericEvent)) {
			return;
		}

		$subject = $event->getSubject();
		if (substr($subject, 0, 21) !== 'Files_FullTextSearch.') {
			return;
		}

		$action = substr($subject, 21);
		switch ($action) {
			case 'onGetConfig':
				$this->configService->onGetConfig($event);
				break;
			case 'onFileIndexing':
				$this->exifService->onFileIndexing($event);
				break;
			case 'onSearchRequest':
				$this->exifService->onSearchRequest($event);
				break;
		}
	}
}
