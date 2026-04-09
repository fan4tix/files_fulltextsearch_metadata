<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class TiffParser extends FileReader {

	public const BYTE = 1;
	public const ASCII = 2;
	public const SHORT = 3;
	public const LONG = 4;
	public const RATIONAL = 5;
	public const SBYTE = 6;
	public const UNDEFINED = 7;
	public const SSHORT = 8;
	public const SLONG = 9;
	public const SRATIONAL = 10;
	public const FLOAT = 11;
	public const DOUBLE = 12;

	public function parseTiff($handle, int $pos): void {
		$data = fread($handle, 4);
		if ($data !== "II\x2A\x00" && $data !== "MM\x00\x2A") {
			return;
		}

		$intel = ($data[0] === 'I');
		$ifdOffset = self::readInt($handle, $intel);
		if ($ifdOffset === false) {
			return;
		}

		$this->parseTiffIfd($handle, $pos, $intel, $ifdOffset);
	}

	public function parseTiffIfd($handle, int $pos, bool $intel, int $ifdOffset): void {
		while (!feof($handle) && $ifdOffset !== 0) {
			fseek($handle, $pos + $ifdOffset);
			$tagCount = self::readShort($handle, $intel);
			if ($tagCount === false) {
				return;
			}

			for ($i = 0; $i < $tagCount; $i++) {
				$tagId = self::readShort($handle, $intel);
				$tagType = self::readShort($handle, $intel);
				$count = self::readInt($handle, $intel);
				$offsetOrData = fread($handle, 4);
				if ($tagId === false || $tagType === false || $count === false || strlen($offsetOrData) !== 4) {
					return;
				}

				$size = -1;
				switch ($tagType) {
					case self::BYTE:
					case self::SBYTE:
					case self::UNDEFINED:
						$size = $count;
						if ($size <= 4) {
							$offsetOrData = substr($offsetOrData, 0, $size);
						}
						break;
					case self::ASCII:
						$size = $count;
						if ($size <= 4) {
							$offsetOrData = substr($offsetOrData, 0, max(0, $size - 1));
						}
						break;
					case self::SHORT:
					case self::SSHORT:
						$size = $count * 2;
						if ($size <= 4) {
							$offsetOrData = substr($offsetOrData, 0, $size);
							if ($count === 1) {
								$offsetOrData = self::unpackShort($intel, $offsetOrData);
							} elseif ($count === 2) {
								$format = $intel ? 'v' : 'n';
								$decoded = unpack($format . 'a/' . $format . 'b', $offsetOrData);
								$offsetOrData = [$decoded['a'], $decoded['b']];
							}
						}
						break;
					case self::LONG:
					case self::SLONG:
						$size = $count * 4;
						if ($size <= 4) {
							$offsetOrData = self::unpackInt($intel, $offsetOrData);
						}
						break;
					case self::RATIONAL:
					case self::SRATIONAL:
						$size = $count * 8;
						break;
				}

				if ($size > 4) {
					$offsetOrData = self::unpackInt($intel, $offsetOrData);
				}

				if ($size > 0) {
					$current = ftell($handle);
					$this->processTag($handle, $pos, $intel, $tagId, $tagType, $count, $size, $offsetOrData);
					fseek($handle, $current);
				}
			}

			$ifdOffset = self::readInt($handle, $intel) ?: 0;
			if ($ifdOffset !== 0 && $ifdOffset < ftell($handle)) {
				$ifdOffset = 0;
			}
		}
	}

	protected function processTag($handle, int $pos, bool $intel, int $tagId, int $tagType, int $count, int $size, mixed $offsetOrData): void {
	}
}
