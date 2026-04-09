<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_EXIF\Service;

use Throwable;

class MetadataExtractionService {

	public function extract(string $path, string $mimeType): array {
		if ($path === '' || !is_file($path) || !is_readable($path)) {
			return [];
		}

		try {
			return match (true) {
				strpos($mimeType, 'image/jpeg') === 0 => $this->extractJpeg($path),
				strpos($mimeType, 'image/tiff') === 0 => $this->extractTiff($path),
				strpos($mimeType, 'image/png') === 0 => $this->extractPng($path),
				strpos($mimeType, 'image/heic') === 0 => $this->extractHeic($path),
				default => []
			};
		} catch (Throwable $e) {
			return [];
		}
	}

	private function extractJpeg(string $path): array {
		$sections = $this->readExif($path);
		$handle = fopen($path, 'rb');
		if ($handle !== false) {
			try {
				$jpegMetadata = JpegMetadata::fromFile($handle);
				if ($jpegMetadata instanceof JpegMetadata) {
					$sections['XMP'] = array_merge($jpegMetadata->getIptc(), $jpegMetadata->getXmp());
					$sections['IFD0'] = array_merge((array)($sections['IFD0'] ?? []), $jpegMetadata->getIfd0());
					if (!array_key_exists('GPS', $sections)) {
						$sections['GPS'] = $jpegMetadata->getGps();
					}
				}
			} catch (Throwable $e) {
				return [];
			} finally {
				fclose($handle);
			}
		}

		return $this->normalize($sections);
	}

	private function extractTiff(string $path): array {
		$sections = $this->readExif($path);
		$handle = fopen($path, 'rb');
		if ($handle !== false) {
			try {
				$tiffMetadata = TiffMetadata::fromFile($handle);
				if ($tiffMetadata instanceof TiffMetadata) {
					$sections['XMP'] = array_merge($tiffMetadata->getIptc(), $tiffMetadata->getXmp());
					$sections['IFD0'] = array_merge((array)($sections['IFD0'] ?? []), $tiffMetadata->getIfd0());
				}
			} catch (Throwable $e) {
				return [];
			} finally {
				fclose($handle);
			}
		}

		return $this->normalize($sections);
	}

	private function extractPng(string $path): array {
		$sections = [];
		$size = @getimagesize($path);
		if ($size !== false) {
			$sections['COMPUTED'] = [
				'Width' => $size[0],
				'Height' => $size[1]
			];
		}

		$handle = fopen($path, 'rb');
		if ($handle !== false) {
			try {
				$pngMetadata = PngMetadata::fromFile($handle);
				if ($pngMetadata instanceof PngMetadata) {
					$sections['PNG_TEXT_CHUNKS'] = $pngMetadata->getTextChunks();
				}
			} catch (Throwable $e) {
				return [];
			} finally {
				fclose($handle);
			}
		}

		return $this->normalize($sections);
	}

	private function extractHeic(string $path): array {
		$handle = fopen($path, 'rb');
		if ($handle === false) {
			return [];
		}

		try {
			$heicMetadata = HeicMetadata::fromFile($handle);
			if ($heicMetadata instanceof HeicMetadata) {
				return $this->normalize($heicMetadata->getExif());
			}
		} catch (Throwable $e) {
			return [];
		} finally {
			fclose($handle);
		}

		return [];
	}

	private function readExif(string $path): array {
		if (!function_exists('exif_read_data')) {
			return [];
		}

		$sections = @exif_read_data($path, null, true);
		if (!is_array($sections)) {
			return [];
		}

		return $sections;
	}

	private function normalize(array $sections): array {
		$output = [];
		foreach ($sections as $sectionKey => $sectionValue) {
			if (!is_array($sectionValue)) {
				$output[(string)$sectionKey] = $this->normalizeValue($sectionValue);
				continue;
			}
			$output[(string)$sectionKey] = $this->normalizeArray($sectionValue);
		}

		return $output;
	}

	private function normalizeArray(array $values): array {
		$output = [];
		foreach ($values as $key => $value) {
			$output[(string)$key] = $this->normalizeValue($value);
		}

		return $output;
	}

	private function normalizeValue(mixed $value): mixed {
		if (is_array($value)) {
			return $this->normalizeArray($value);
		}

		if (is_scalar($value) || $value === null) {
			return $value;
		}

		return (string)$value;
	}
}
