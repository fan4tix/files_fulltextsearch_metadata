<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_EXIF\Service;

class PngMetadata {

	private array $textChunks = [];

	public static function fromFile($handle): self {
		$obj = new self();
		$obj->readPng($handle);
		return $obj;
	}

	public function getTextChunks(): array {
		return $this->textChunks;
	}

	private function readPng($handle): void {
		if (fread($handle, 8) !== "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a") {
			return;
		}

		while (($chunkHeader = fread($handle, 8)) !== false && strlen($chunkHeader) === 8) {
			$chunk = unpack('Nsize/a4type', $chunkHeader);
			$size = (int)$chunk['size'];
			$type = (string)$chunk['type'];

			switch ($type) {
				case 'tEXt':
					$data = $this->readChunk($handle, $size);
					$value = explode("\x00", trim($data), 2);
					if (count($value) === 2) {
						$this->textChunks[] = ['keyword' => $value[0], 'text' => $value[1]];
					}
					break;
				case 'zTXt':
					$data = $this->readChunk($handle, $size);
					$value = explode("\x00", trim($data), 2);
					if (count($value) === 2 && isset($value[1][0])) {
						$contents = substr($value[1], 1);
						$this->textChunks[] = [
							'keyword' => $value[0],
							'text' => $this->uncompress($value[1][0], $contents)
						];
					}
					break;
				case 'iTXt':
					$data = $this->readChunk($handle, $size);
					$value = explode("\x00", trim($data), 2);
					if (count($value) === 2 && strlen($value[1]) >= 2) {
						$contents = explode("\x00", substr($value[1], 2), 3);
						if (count($contents) === 3) {
							$this->textChunks[] = [
								'keyword' => $value[0],
								'text' => $this->uncompress($value[1][1], $contents[2], $value[1][0]),
								'language' => $contents[0],
								'translated' => $contents[1]
							];
						}
					}
					break;
				default:
					fseek($handle, $size + 4, SEEK_CUR);
			}
		}
	}

	private function readChunk($handle, int $size): string {
		$data = (string)fread($handle, $size);
		fseek($handle, 4, SEEK_CUR);
		return $data;
	}

	private function uncompress(string $method, string $contents, string $flag = "\x01"): string|false {
		if ($flag !== "\x01") {
			return $contents;
		}
		if ($method !== "\x00") {
			return false;
		}

		return @gzuncompress($contents);
	}
}
