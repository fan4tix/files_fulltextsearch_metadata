<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

use OCA\Metadata\GetID3\getID3;
use Throwable;

class AvMetadataExtractionService {

	private static bool $getId3AutoloadRegistered = false;

	public function extract(string $path): array {
		if ($path === '' || !is_file($path) || !is_readable($path)) {
			return [];
		}

		if (!$this->loadGetId3()) {
			return [];
		}

		$handle = fopen($path, 'rb');
		if ($handle === false) {
			return [];
		}

		try {
			$analyzer = new getID3();
			$analyzer->option_save_attachments = false;
			$analyzer->option_tags_html = false;
			$analyzer->option_md5_data = false;
			$analyzer->option_sha1_data = false;

			$analysis = $analyzer->analyze(
				basename($path),
				max(0, (int)filesize($path)),
				basename($path),
				$handle
			);
			if (!is_array($analysis)) {
				return [];
			}

			return $this->normalize($analysis);
		} catch (Throwable $e) {
			return [];
		} finally {
			if (is_resource($handle)) {
				fclose($handle);
			}
		}
	}

	private function loadGetId3(): bool {
		$this->registerGetId3Autoloader();

		if (class_exists(getID3::class)) {
			return true;
		}

		$bootstrap = __DIR__ . '/../GetID3/getID3.php';
		if (!is_file($bootstrap)) {
			return false;
		}

		require_once $bootstrap;

		return class_exists(getID3::class);
	}

	private function registerGetId3Autoloader(): void {
		if (self::$getId3AutoloadRegistered) {
			return;
		}

		spl_autoload_register(static function (string $class): void {
			$prefix = 'OCA\\Metadata\\GetID3\\';
			if (!str_starts_with($class, $prefix)) {
				return;
			}

			$relative = substr($class, strlen($prefix));
			$path = __DIR__ . '/../GetID3/' . str_replace('\\', '/', $relative) . '.php';
			if (is_file($path)) {
				require_once $path;
			}
		});

		self::$getId3AutoloadRegistered = true;
	}

	private function normalize(array $analysis): array {
		$metadata = [];

		$av = $this->extractAvTags($analysis);
		if ($av !== []) {
			$metadata['AV'] = $av;
		}

		$audio = $this->extractAudioInfo($analysis);
		if ($audio !== []) {
			$metadata['AUDIO'] = $audio;
		}

		$video = $this->extractVideoInfo($analysis);
		if ($video !== []) {
			$metadata['VIDEO'] = $video;
		}

		$container = $this->extractContainerInfo($analysis);
		if ($container !== []) {
			$metadata['CONTAINER'] = $container;
		}

		return $metadata;
	}

	private function extractAvTags(array $analysis): array {
		$tags = [];
		$tagFamilies = $this->firstArray($analysis['tags'] ?? null);

		$this->addIfNotEmpty($tags, 'Title', $this->firstTagValue($tagFamilies, ['title']));
		$this->addIfNotEmpty($tags, 'Artist', $this->firstTagValue($tagFamilies, ['artist', 'album_artist']));
		$this->addIfNotEmpty($tags, 'Album', $this->firstTagValue($tagFamilies, ['album']));
		$this->addIfNotEmpty($tags, 'Genre', $this->firstTagValue($tagFamilies, ['genre']));
		$this->addIfNotEmpty($tags, 'Comment', $this->firstTagValue($tagFamilies, ['comment', 'description']));
		$this->addIfNotEmpty($tags, 'Composer', $this->firstTagValue($tagFamilies, ['composer']));
		$this->addIfNotEmpty($tags, 'Year', $this->firstTagValue($tagFamilies, ['year', 'date']));
		$this->addIfNotEmpty($tags, 'Track', $this->firstTagValue($tagFamilies, ['track_number', 'track']));
		$this->addIfNotEmpty($tags, 'Keywords', $this->firstTagValue($tagFamilies, ['keywords', 'keyword', 'category']));

		return $tags;
	}

	private function extractAudioInfo(array $analysis): array {
		$audio = [];
		$audioInfo = $this->firstArray($analysis['audio'] ?? null);

		$this->addIfNotEmpty($audio, 'Codec', $this->stringValue($audioInfo['codec'] ?? null));
		$this->addIfNotEmpty($audio, 'Format', $this->stringValue($audioInfo['dataformat'] ?? null));
		$this->addIfNotEmpty($audio, 'Channels', $this->stringValue($audioInfo['channels'] ?? null));
		$this->addIfNotEmpty($audio, 'SampleRate', $this->stringValue($audioInfo['sample_rate'] ?? null));
		$this->addIfNotEmpty($audio, 'Bitrate', $this->stringValue($audioInfo['bitrate'] ?? null));

		return $audio;
	}

	private function extractVideoInfo(array $analysis): array {
		$video = [];
		$videoInfo = $this->firstArray($analysis['video'] ?? null);

		$this->addIfNotEmpty($video, 'Codec', $this->stringValue($videoInfo['codec'] ?? null));
		$this->addIfNotEmpty($video, 'Format', $this->stringValue($videoInfo['dataformat'] ?? null));
		$this->addIfNotEmpty($video, 'FrameRate', $this->stringValue($videoInfo['frame_rate'] ?? null));
		$this->addIfNotEmpty($video, 'Bitrate', $this->stringValue($videoInfo['bitrate'] ?? null));

		$resolutionX = $this->stringValue($videoInfo['resolution_x'] ?? null);
		$resolutionY = $this->stringValue($videoInfo['resolution_y'] ?? null);
		if ($resolutionX !== '' && $resolutionY !== '') {
			$video['Resolution'] = $resolutionX . 'x' . $resolutionY;
		}

		return $video;
	}

	private function extractContainerInfo(array $analysis): array {
		$container = [];

		$this->addIfNotEmpty($container, 'MimeType', $this->stringValue($analysis['mime_type'] ?? null));
		$this->addIfNotEmpty($container, 'FileFormat', $this->stringValue($analysis['fileformat'] ?? null));
		$this->addIfNotEmpty($container, 'Playtime', $this->stringValue($analysis['playtime_string'] ?? null));
		$this->addIfNotEmpty($container, 'Bitrate', $this->stringValue($analysis['bitrate'] ?? null));

		return $container;
	}

	private function firstTagValue(array $tagFamilies, array $keys): string {
		if ($tagFamilies === []) {
			return '';
		}

		foreach ($tagFamilies as $familyValues) {
			if (!is_array($familyValues)) {
				continue;
			}

			foreach ($keys as $key) {
				if (!array_key_exists($key, $familyValues)) {
					continue;
				}

				$value = $this->stringValue($familyValues[$key]);
				if ($value !== '') {
					return $value;
				}
			}
		}

		return '';
	}

	private function addIfNotEmpty(array &$target, string $key, string $value): void {
		if ($value !== '') {
			$target[$key] = $value;
		}
	}

	private function firstArray(mixed $value): array {
		return is_array($value) ? $value : [];
	}

	private function stringValue(mixed $value): string {
		if (is_string($value)) {
			return trim($value);
		}

		if (is_int($value) || is_float($value) || is_bool($value)) {
			return (string)$value;
		}

		if (!is_array($value)) {
			return '';
		}

		$parts = [];
		foreach ($value as $entry) {
			if (is_array($entry)) {
				$entry = $this->stringValue($entry);
			}

			if (is_string($entry)) {
				$entry = trim($entry);
				if ($entry !== '') {
					$parts[] = $entry;
				}
			} elseif (is_int($entry) || is_float($entry) || is_bool($entry)) {
				$parts[] = (string)$entry;
			}
		}

		return implode('; ', array_unique($parts));
	}
}
