<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Controller;

use OCA\Files_FullTextSearch_Metadata\AppInfo\Application;
use OCA\Files_FullTextSearch_Metadata\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller {

	public function __construct(IRequest $request, private ConfigService $configService) {
		parent::__construct(Application::APP_NAME, $request);
	}

	public function getSettingsAdmin(): DataResponse {
		return new DataResponse($this->configService->getConfig(), Http::STATUS_OK);
	}

	public function setSettingsAdmin(array $data): DataResponse {
		$this->configService->setConfig($data);
		return $this->getSettingsAdmin();
	}
}
