<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class AcdsCategories {

	private const EL_CATEGORY = 'Category';

	private $parser;
	private ?string $text = null;
	private array $data = [];
	private array $path = [];
	private string $assigned = '';

	private function __construct() {
		if (!function_exists('xml_parser_create')) {
			$this->parser = null;
			return;
		}

		$this->parser = xml_parser_create('UTF-8');
		if ($this->parser === false) {
			$this->parser = null;
			return;
		}

		@xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		@xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);
		@xml_set_element_handler($this->parser, [$this, 'startElement'], [$this, 'endElement']);
		@xml_set_character_data_handler($this->parser, [$this, 'charData']);
	}

	public function __destruct() {
		if (is_resource($this->parser)) {
			xml_parser_free($this->parser);
		}
	}

	public static function fromData(string $xml): self {
		$obj = new self();
		if (is_resource($obj->parser) || is_object($obj->parser)) {
			@xml_parse($obj->parser, $xml, true);
		}
		return $obj;
	}

	public function getArray(): array {
		return $this->data;
	}

	public function startElement($parser, string $name, array $attributes): void {
		if ($name === self::EL_CATEGORY) {
			$this->handleCurrent();
			$this->text = null;
			$this->assigned = $attributes['Assigned'] ?? '';
		}
	}

	public function endElement($parser, string $name): void {
		if ($name === self::EL_CATEGORY) {
			$this->handleCurrent();
			$this->text = null;
			$this->assigned = '';
			array_pop($this->path);
		}
	}

	public function charData($parser, string $data): void {
		$this->text .= $data;
	}

	private function handleCurrent(): void {
		if ($this->text === null || $this->text === '') {
			return;
		}

		$this->path[] = $this->text;
		if ($this->assigned === '1') {
			$this->data[] = implode('/', $this->path);
		}
	}
}
