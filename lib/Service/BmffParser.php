<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class BmffParser extends FileReader {

	public function parseBmff($handle, int $size = 0): void {
		$current = ftell($handle);
		$limit = $current + $size;
		$continue = true;

		while (!feof($handle) && $continue !== false) {
			if ($size > 0 && $current >= $limit) {
				return;
			}

			$continue = $this->readBox($handle, $current);
		}
	}

	private function readBox($handle, int &$current): bool {
		$boxSize = self::readInt($handle, false);
		$boxType = self::readString($handle, 4);
		if ($boxSize === false || $boxType === false) {
			return false;
		}

		if ($boxSize === 1) {
			$boxSize = self::readLong($handle, false);
			if ($boxSize === false) {
				return false;
			}
		}

		$ret = $this->processBox($handle, $boxType, $boxSize);

		if ($boxSize > 1) {
			$current += $boxSize;
			fseek($handle, $current);
		} else {
			fseek($handle, 0, SEEK_END);
			$current = ftell($handle);
		}

		return $ret;
	}

	private function continueFullBox($handle, int &$version): void {
		$version = self::readByte($handle) ?: 0;
		self::readByte($handle);
		self::readShort($handle, false);
	}

	protected function processBox($handle, string $boxType, int $boxSize): bool {
		switch ($boxType) {
			case 'meta':
				$this->continueFullBox($handle, $version);
				$this->parseBmff($handle, $boxSize);
				break;
			case 'iinf':
				$this->continueFullBox($handle, $version);
				$entryCount = ($version === 0) ? self::readShort($handle, false) : self::readInt($handle, false);
				$current = ftell($handle);
				for ($i = 0; $i < (int)$entryCount; $i++) {
					$this->readBox($handle, $current);
				}
				break;
			case 'infe':
				$this->continueFullBox($handle, $version);
				if ($version >= 2) {
					$itemId = ($version === 2) ? self::readShort($handle, false) : (($version === 3) ? self::readInt($handle, false) : null);
					self::readShort($handle, false);
					$itemType = self::readString($handle, 4);
					if ($itemId !== null && $itemType !== false) {
						$this->processItemInfoBox($itemType, (int)$itemId);
					}
				}
				break;
			case 'iloc':
				$this->continueFullBox($handle, $version);
				$offsetAndLengthSizes = self::readByte($handle) ?: 0;
				$offsetSize = $offsetAndLengthSizes >> 4;
				$lengthSize = $offsetAndLengthSizes & 0xf;
				$baseOffsetAndIndexSizes = self::readByte($handle) ?: 0;
				$baseOffsetSize = $baseOffsetAndIndexSizes >> 4;
				$indexSize = (($version === 1) || ($version === 2)) ? ($baseOffsetAndIndexSizes & 0xf) : 0;
				$itemCount = ($version < 2) ? self::readShort($handle, false) : (($version === 2) ? self::readInt($handle, false) : 0);
				for ($i = 0; $i < (int)$itemCount; $i++) {
					$itemId = ($version < 2) ? self::readShort($handle, false) : self::readInt($handle, false);
					if ($itemId === false) {
						return true;
					}
					if (($version === 1) || ($version === 2)) {
						self::readShort($handle, false);
					}
					self::readShort($handle, false);
					$baseOffset = $baseOffsetSize > 0 ? (self::readN($handle, $baseOffsetSize, false) ?: 0) : 0;
					$extentCount = self::readShort($handle, false) ?: 0;
					for ($j = 0; $j < $extentCount; $j++) {
						if ($indexSize > 0) {
							fread($handle, $indexSize);
						}
						$extentOffset = self::readN($handle, $offsetSize, false) ?: 0;
						$extentLength = self::readN($handle, $lengthSize, false) ?: 0;
						$this->processItemExtent((int)$itemId, $baseOffset + $extentOffset, $extentLength);
					}
				}
				break;
		}

		return true;
	}

	protected function processItemInfoBox(string $itemType, int $itemId): void {
	}

	protected function processItemExtent(int $itemId, int $extentOffset, int $extentLength): void {
	}
}
