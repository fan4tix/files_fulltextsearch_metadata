<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class MetadataTextFormatter {

	private const PRIORITY_EXACT_PATHS = [
		'AV.Keywords',
		'AV.Title',
		'AV.Artist',
		'AV.Album',
		'WINXP.Keywords',
		'IPTC.Keywords',
		'XMP.dc.subject',
		'XMP.lr.hierarchicalSubject',
		'XMP.photoshop.Category',
		'XMP.dc.title',
		'IFD0.Make',
		'IFD0.Model',
		'EXIF.DateTimeOriginal'
	];

	private const PRIORITY_PREFIXES = [
		'AV.',
		'AUDIO.',
		'VIDEO.',
		'WINXP.',
		'IPTC.',
		'XMP.',
		'IFD0.',
		'EXIF.',
		'GPS.'
	];

	private const SKIP_PREFIXES = [
		'INTEROP.',
		'COMPUTED.Thumbnail.',
		'THUMBNAIL.'
	];

	private const SKIP_EXACT_PATHS = [
		'COMPUTED.FileType',
		'COMPUTED.MimeType',
		'COMPUTED.html',
		'COMPUTED.IsColor',
		'COMPUTED.ByteOrderMotorola',
		'FILE.FileType',
		'FILE.MimeType'
	];

	public static function flatten(array $metadata): string {
		$lines = [];
		self::appendFlattened($metadata, '', $lines);
		$lines = self::sortByRelevance($lines);

		return implode("\n", array_unique(array_filter($lines)));
	}

	private static function appendFlattened(mixed $value, string $prefix, array &$lines): void {
		if (is_array($value)) {
			foreach ($value as $key => $child) {
				$keyPrefix = $prefix;
				if (!is_int($key)) {
					$keyPrefix = $prefix === '' ? (string)$key : $prefix . '.' . (string)$key;
				}
				self::appendFlattened($child, $keyPrefix, $lines);
			}

			return;
		}

		if (!is_scalar($value) || $value === '') {
			return;
		}

		$val = trim((string)$value);
		if ($val === '') {
			return;
		}

		if ($prefix !== '') {
			if (self::shouldSkipPath($prefix)) {
				return;
			}

			$lines[] = $prefix . ': ' . $val;
		} else {
			$lines[] = $val;
		}
	}

	private static function shouldSkipPath(string $path): bool {
		if (in_array($path, self::SKIP_EXACT_PATHS, true)) {
			return true;
		}

		foreach (self::SKIP_PREFIXES as $prefix) {
			if (str_starts_with($path, $prefix)) {
				return true;
			}
		}

		return false;
	}

	private static function sortByRelevance(array $lines): array {
		$decorated = [];
		foreach ($lines as $index => $line) {
			$path = self::extractPath((string)$line);
			$decorated[] = [
				'line' => (string)$line,
				'index' => (int)$index,
				'priority' => self::priorityForPath($path)
			];
		}

		usort($decorated, static function (array $a, array $b): int {
			if ($a['priority'] !== $b['priority']) {
				return $a['priority'] <=> $b['priority'];
			}

			return $a['index'] <=> $b['index'];
		});

		return array_map(static fn(array $entry): string => $entry['line'], $decorated);
	}

	private static function extractPath(string $line): string {
		$separator = strpos($line, ':');
		if ($separator === false) {
			return '';
		}

		return trim(substr($line, 0, $separator));
	}

	private static function priorityForPath(string $path): int {
		$exactIndex = array_search($path, self::PRIORITY_EXACT_PATHS, true);
		if ($exactIndex !== false) {
			return (int)$exactIndex;
		}

		$base = count(self::PRIORITY_EXACT_PATHS);
		foreach (self::PRIORITY_PREFIXES as $idx => $prefix) {
			if (str_starts_with($path, $prefix)) {
				return $base + $idx;
			}
		}

		return 1000;
	}
}
