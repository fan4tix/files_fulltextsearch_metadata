<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_EXIF\Service;

class MetadataTextFormatter {

	public static function flatten(array $metadata): string {
		$lines = [];
		self::appendFlattened($metadata, '', $lines);

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
			$lines[] = $prefix . ': ' . $val;
		} else {
			$lines[] = $val;
		}
	}
}
