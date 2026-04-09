<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_EXIF\Service;

class TiffMetadata extends TiffParser {

	private const RATING = 0x4746;
	private const XMP = 0x02BC;
	private const IPTC = 0x83BB;
	private const GPS = 0x8825;

	private array $ifd0 = [];
	private array $xmp = [];
	private array $iptc = [];
	private array $gps = [];

	public static function fromFile($handle): self {
		$obj = new self();
		$obj->parseTiff($handle, 0);
		return $obj;
	}

	public static function fromFileData($handle, int $pos): self {
		$obj = new self();
		$obj->parseTiff($handle, $pos);
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

	protected function processTag($handle, int $pos, bool $intel, int $tagId, int $tagType, int $count, int $size, mixed $offsetOrData): void {
		switch ($tagId) {
			case self::RATING:
				$this->ifd0['Rating'] = $offsetOrData;
				break;
			case self::XMP:
				fseek($handle, (int)$offsetOrData);
				$xmpMetadata = XmpMetadata::fromData((string)fread($handle, $size));
				$this->xmp = $xmpMetadata->getArray();
				break;
			case self::IPTC:
				fseek($handle, (int)$offsetOrData);
				$iptcMetadata = IptcMetadata::fromData((string)fread($handle, $size));
				$this->iptc = $iptcMetadata->getArray();
				break;
			case self::GPS:
				$gpsParser = new class extends TiffParser {
					public array $gps = [];

					protected function processTag($handle, int $pos, bool $intel, int $tagId, int $tagType, int $count, int $size, mixed $offsetOrData): void {
						switch ($tagId) {
							case 0x01:
								$this->gps['GPSLatitudeRef'] = $offsetOrData;
								break;
							case 0x02:
								fseek($handle, $pos + (int)$offsetOrData);
								$this->gps['GPSLatitude'] = [
									self::readRat($handle, $intel),
									self::readRat($handle, $intel),
									self::readRat($handle, $intel)
								];
								break;
							case 0x03:
								$this->gps['GPSLongitudeRef'] = $offsetOrData;
								break;
							case 0x04:
								fseek($handle, $pos + (int)$offsetOrData);
								$this->gps['GPSLongitude'] = [
									self::readRat($handle, $intel),
									self::readRat($handle, $intel),
									self::readRat($handle, $intel)
								];
								break;
							case 0x05:
								$this->gps['GPSAltitudeRef'] = $offsetOrData;
								break;
							case 0x06:
								fseek($handle, $pos + (int)$offsetOrData);
								$this->gps['GPSAltitude'] = self::readRat($handle, $intel);
								break;
						}
					}
				};
				$gpsParser->parseTiffIfd($handle, $pos, $intel, (int)$offsetOrData);
				$this->gps = $gpsParser->gps;
				break;
		}
	}
}
