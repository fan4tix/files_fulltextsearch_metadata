<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class HeicMetadata extends BmffParser {

	private const EXIF_HEADER_SIZE = 10;

	private ?int $exifItemId = null;
	private ?int $exifOffset = null;
	private ?int $exifLength = null;
	private array $exif = [];
	private bool $itemInfosSeen = false;
	private array $itemExtents = [];

	public static function fromFile($handle): ?self {
		$obj = new self();
		$obj->readHeic($handle);
		return $obj->exif === [] ? null : $obj;
	}

	public function getExif(): array {
		return $this->exif;
	}

	private function readHeic($handle): void {
		if (!function_exists('exif_read_data')) {
			return;
		}

		$this->parseBmff($handle);
		if ($this->exifOffset === null || $this->exifLength === null) {
			return;
		}

		$stream = fopen('php://memory', 'rb+');
		if ($stream === false) {
			return;
		}

		try {
			fseek($handle, $this->exifOffset + self::EXIF_HEADER_SIZE);
			stream_copy_to_stream($handle, $stream, $this->exifLength - self::EXIF_HEADER_SIZE);
			rewind($stream);
			$exif = @exif_read_data($stream, null, true);
			if (is_array($exif)) {
				$this->exif = $exif;
			}
		} finally {
			fclose($stream);
		}
	}

	protected function processBox($handle, string $boxType, int $boxSize): bool {
		$ret = parent::processBox($handle, $boxType, $boxSize);
		if ($this->exifOffset !== null) {
			return false;
		}

		return $ret;
	}

	protected function processItemInfoBox(string $itemType, int $itemId): void {
		$this->itemInfosSeen = true;
		if ($itemType !== 'Exif') {
			return;
		}

		$this->exifItemId = $itemId;
		if (array_key_exists($itemId, $this->itemExtents)) {
			$extent = $this->itemExtents[$itemId];
			$this->exifOffset = $extent[0];
			$this->exifLength = $extent[1];
		}
	}

	protected function processItemExtent(int $itemId, int $extentOffset, int $extentLength): void {
		if ($this->itemInfosSeen) {
			if ($itemId === $this->exifItemId) {
				$this->exifOffset = $extentOffset;
				$this->exifLength = $extentLength;
			}
			return;
		}

		$this->itemExtents[$itemId] = [$extentOffset, $extentLength];
	}
}
