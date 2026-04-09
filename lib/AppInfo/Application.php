<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\AppInfo;

use OCA\Files_FullTextSearch_Metadata\Listeners\GenericListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\GenericEvent;

class Application extends App implements IBootstrap {

	public const APP_NAME = 'files_fulltextsearch_metadata';

	public function __construct(array $params = []) {
		parent::__construct(self::APP_NAME, $params);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(GenericEvent::class, GenericListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
