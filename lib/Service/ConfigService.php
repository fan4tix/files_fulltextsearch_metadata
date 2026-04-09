<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

use OCA\Files_FullTextSearch_Metadata\AppInfo\Application;
use OCP\EventDispatcher\GenericEvent;
use OCP\IConfig;

class ConfigService {

	public const EXIF_ENABLED = 'exif_enabled';
	public const EXIF_MAX_SIZE_MB = 'exif_max_size_mb';
	public const EXIF_FORMAT_JPEG = 'exif_format_jpeg';
	public const EXIF_FORMAT_TIFF = 'exif_format_tiff';
	public const EXIF_FORMAT_PNG = 'exif_format_png';
	public const EXIF_FORMAT_HEIC = 'exif_format_heic';
	public const EXIF_FORMAT_AUDIO = 'exif_format_audio';
	public const EXIF_FORMAT_VIDEO = 'exif_format_video';

	public array $defaults = [
		self::EXIF_ENABLED => '1',
		self::EXIF_MAX_SIZE_MB => '20',
		self::EXIF_FORMAT_JPEG => '1',
		self::EXIF_FORMAT_TIFF => '1',
		self::EXIF_FORMAT_PNG => '1',
		self::EXIF_FORMAT_HEIC => '1',
		self::EXIF_FORMAT_AUDIO => '1',
		self::EXIF_FORMAT_VIDEO => '1'
	];

	public function __construct(private IConfig $config) {
	}

	public function onGetConfig(GenericEvent $event): void {
		$config = $event->getArgument('config');
		$config['files_fulltextsearch_metadata'] = [
			'version' => $this->getAppValue('installed_version'),
			'enabled' => $this->getAppValue(self::EXIF_ENABLED),
			'max_size_mb' => $this->getAppValue(self::EXIF_MAX_SIZE_MB),
			'jpeg' => $this->getAppValue(self::EXIF_FORMAT_JPEG),
			'tiff' => $this->getAppValue(self::EXIF_FORMAT_TIFF),
			'png' => $this->getAppValue(self::EXIF_FORMAT_PNG),
			'heic' => $this->getAppValue(self::EXIF_FORMAT_HEIC),
			'audio' => $this->getAppValue(self::EXIF_FORMAT_AUDIO),
			'video' => $this->getAppValue(self::EXIF_FORMAT_VIDEO)
		];
		$event->setArgument('config', $config);
	}

	public function getConfig(): array {
		$data = [];
		foreach (array_keys($this->defaults) as $key) {
			$data[$key] = $this->getAppValue($key);
		}

		return $data;
	}

	public function setConfig(array $save): void {
		foreach (array_keys($this->defaults) as $key) {
			if (array_key_exists($key, $save)) {
				$this->setAppValue($key, (string)$save[$key]);
			}
		}
	}

	public function getAppValue(string $key): string {
		$defaultValue = $this->defaults[$key] ?? null;

		return $this->config->getAppValue(Application::APP_NAME, $key, $defaultValue);
	}

	public function setAppValue(string $key, string $value): void {
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	public function optionIsSelected(string $key): bool {
		return $this->getAppValue($key) === '1';
	}

	public function getMaxSizeBytes(): int {
		$mb = (int)$this->getAppValue(self::EXIF_MAX_SIZE_MB);
		if ($mb <= 0) {
			return 0;
		}

		return $mb * 1024 * 1024;
	}
}
