<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_EXIF\Service;

class FileReader {

	public static function readByte($handle): int|false {
		$data = fread($handle, 1);
		return ($data !== false && $data !== '') ? self::unpackByte($data) : false;
	}

	public static function readShort($handle, bool $intel): int|false {
		$data = fread($handle, 2);
		return ($data !== false && strlen($data) === 2) ? self::unpackShort($intel, $data) : false;
	}

	public static function readInt($handle, bool $intel): int|false {
		$data = fread($handle, 4);
		return ($data !== false && strlen($data) === 4) ? self::unpackInt($intel, $data) : false;
	}

	public static function readLong($handle, bool $intel): int|false {
		$data = fread($handle, 8);
		return ($data !== false && strlen($data) === 8) ? self::unpackLong($intel, $data) : false;
	}

	public static function readN($handle, int $n, bool $intel): int|false {
		$data = fread($handle, $n);
		return ($data !== false && strlen($data) === $n) ? self::unpackN($intel, $n, $data) : false;
	}

	public static function readRat($handle, bool $intel): string|false {
		$a = self::readInt($handle, $intel);
		$b = self::readInt($handle, $intel);
		if ($a === false || $b === false || $b === 0) {
			return false;
		}

		return $a . '/' . $b;
	}

	public static function readString($handle, int $len): string|false {
		$data = fread($handle, $len);
		return ($data !== false && strlen($data) === $len) ? $data : false;
	}

	protected static function unpackByte(string $data, int $offset = 0): int {
		return unpack('Cd', $data, $offset)['d'];
	}

	protected static function unpackShort(bool $intel, string $data, int $offset = 0): int {
		return unpack(($intel ? 'v' : 'n') . 'd', $data, $offset)['d'];
	}

	protected static function unpackInt(bool $intel, string $data, int $offset = 0): int {
		return unpack(($intel ? 'V' : 'N') . 'd', $data, $offset)['d'];
	}

	protected static function unpackLong(bool $intel, string $data): int {
		return unpack(($intel ? 'P' : 'J') . 'd', $data)['d'];
	}

	protected static function unpackN(bool $intel, int $n, string $data): int {
		return match ($n) {
			1 => self::unpackByte($data),
			2 => self::unpackShort($intel, $data),
			4 => self::unpackInt($intel, $data),
			8 => self::unpackLong($intel, $data),
			default => throw new \RuntimeException('Unsupported integer width: ' . $n)
		};
	}
}
