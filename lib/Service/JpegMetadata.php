<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class JpegMetadata extends FileReader {

	private const APP1 = "\xE1";
	private const APP13 = "\xED";

	private array $ifd0 = [];
	private array $xmp = [];
	private array $iptc = [];
	private array $gps = [];

	public static function fromFile($handle): self {
		$obj = new self();
		$obj->readJpeg($handle);
		return $obj;
	}

	public function getIfd0(): array {
		return $this->ifd0;
	}

	public function getXmp(): array {
		return $this->xmp;
	}

	public function getIptc(): array {
		return $this->iptc;
	}

	public function getGps(): array {
		return $this->gps;
	}

	private function readJpeg($handle): void {
		$data = fread($handle, 2);
		if ($data !== "\xFF\xD8") {
			return;
		}

		$data = fread($handle, 2);
		while (!feof($handle) && isset($data[0], $data[1]) && $data[0] === "\xFF" && $data[1] !== "\xDA" && $data[1] !== "\xD9") {
			$markerOrd = ord($data[1]);
			if ($markerOrd < 0xD0 || $markerOrd > 0xD7) {
				$size = self::readShort($handle, false);
				if ($size === false) {
					return;
				}
				$size -= 2;
				if ($size > 0) {
					$pos = ftell($handle);
					$this->processData($handle, $data[1], $size);
					fseek($handle, $pos + $size);
				}
			}
			$data = fread($handle, 2);
		}
	}

	private function processData($handle, string $marker, int $size): void {
		switch ($marker) {
			case self::APP1:
				$start = ftell($handle);
				if ($this->tryXmp($handle, $size)) {
					break;
				}
				fseek($handle, $start);
				$this->tryExif($handle, $size);
				break;
			case self::APP13:
				$iptcMetadata = IptcMetadata::fromData((string)fread($handle, $size));
				$this->iptc = $iptcMetadata->getArray();
				break;
		}
	}

	private function tryXmp($handle, int $size): bool {
		if ($size <= 29) {
			return false;
		}

		$data = fread($handle, 29);
		$size -= 29;
		if ($data !== "http://ns.adobe.com/xap/1.0/\x00") {
			return false;
		}

		$xmpMetadata = XmpMetadata::fromData((string)fread($handle, $size));
		$this->xmp = $xmpMetadata->getArray();
		return true;
	}

	private function tryExif($handle, int $size): bool {
		if ($size <= 14) {
			return false;
		}

		$data = fread($handle, 6);
		if ($data !== "Exif\x00\x00") {
			return false;
		}

		$pos = ftell($handle);
		$tiffMetadata = TiffMetadata::fromFileData($handle, $pos);
		$this->ifd0 = $tiffMetadata->getIfd0();
		$this->gps = $tiffMetadata->getGps();
		return true;
	}
}
