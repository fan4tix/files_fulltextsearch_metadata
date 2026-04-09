<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Settings;

use Exception;
use OCA\Files_FullTextSearch_Metadata\AppInfo\Application;
use OCA\Files_FullTextSearch_Metadata\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ConfigService $configService
	) {
	}

	/**
	 * @throws Exception
	 */
	public function getForm(): TemplateResponse {
		return new TemplateResponse(Application::APP_NAME, 'settings.admin', []);
	}

	public function getSection(): string {
		return 'fulltextsearch';
	}

	public function getPriority(): int {
		return 52;
	}
}
