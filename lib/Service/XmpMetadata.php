<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class XmpMetadata {

	private const EL_MWG_RS_REGIONS = 'mwg-rs:Regions';
	private const EL_MWG_RS_NAME = 'mwg-rs:Name';
	private const EL_MWG_RS_TYPE = 'mwg-rs:Type';
	private const EL_DIGIKAM_TAGS_LIST = 'digiKam:TagsList';
	private const EL_PS_AUTHORS_POSITION = 'photoshop:AuthorsPosition';
	private const EL_PS_CAPTION_WRITER = 'photoshop:CaptionWriter';
	private const EL_PS_CITY = 'photoshop:City';
	private const EL_PS_COUNTRY = 'photoshop:Country';
	private const EL_PS_CREDIT = 'photoshop:Credit';
	private const EL_PS_DATE_CREATED = 'photoshop:DateCreated';
	private const EL_PS_HEADLINE = 'photoshop:Headline';
	private const EL_PS_INSTRUCTIONS = 'photoshop:Instructions';
	private const EL_PS_SOURCE = 'photoshop:Source';
	private const EL_PS_STATE = 'photoshop:State';
	private const EL_AS_CAPTION = 'acdsee:caption';
	private const EL_AS_CATEGORIES = 'acdsee:categories';
	private const EL_DC_CREATOR = 'dc:creator';
	private const EL_DC_DESCRIPTION = 'dc:description';
	private const EL_DC_RIGHTS = 'dc:rights';
	private const EL_DC_SUBJECT = 'dc:subject';
	private const EL_DC_TITLE = 'dc:title';
	private const EL_RDF_DESCRIPTION = 'rdf:Description';
	private const EL_RDF_LI = 'rdf:li';

	private $parser;
	private ?string $text = null;
	private array $data = [];
	private array $context = [];
	private ?string $rsName = null;
	private ?string $rsType = null;

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

	public static function fromFile($handle): self {
		$obj = new self();
		if (!is_resource($obj->parser) && !is_object($obj->parser)) {
			return $obj;
		}

		while (($chunk = fread($handle, 8192))) {
			@xml_parse($obj->parser, $chunk);
		}
		@xml_parse($obj->parser, '', true);
		return $obj;
	}

	public function getArray(): array {
		return $this->data;
	}

	public function startElement($parser, string $name, array $attributes): void {
		$this->text = null;

		switch ($name) {
			case self::EL_MWG_RS_REGIONS:
			case self::EL_DIGIKAM_TAGS_LIST:
			case self::EL_DC_CREATOR:
			case self::EL_DC_DESCRIPTION:
			case self::EL_DC_RIGHTS:
			case self::EL_DC_SUBJECT:
			case self::EL_DC_TITLE:
				$this->contextPush($name);
				break;
			case self::EL_RDF_DESCRIPTION:
				if ($this->contextPeek() === self::EL_MWG_RS_REGIONS) {
					$this->rsName = $attributes[self::EL_MWG_RS_NAME] ?? $this->rsName;
					$this->rsType = $attributes[self::EL_MWG_RS_TYPE] ?? $this->rsType;
					break;
				}
				if ($this->contextPeek() === null) {
					$this->addValIfExists(self::EL_PS_AUTHORS_POSITION, $attributes);
					$this->addValIfExists(self::EL_PS_CAPTION_WRITER, $attributes);
					$this->addValIfExists(self::EL_PS_CITY, $attributes);
					$this->addValIfExists(self::EL_PS_COUNTRY, $attributes);
					$this->addValIfExists(self::EL_PS_CREDIT, $attributes);
					$this->addValIfExists(self::EL_PS_DATE_CREATED, $attributes);
					$this->addValIfExists(self::EL_PS_HEADLINE, $attributes);
					$this->addValIfExists(self::EL_PS_INSTRUCTIONS, $attributes);
					$this->addValIfExists(self::EL_PS_SOURCE, $attributes);
					$this->addValIfExists(self::EL_PS_STATE, $attributes);
				}
				break;
		}
	}

	public function endElement($parser, string $name): void {
		if ($this->contextPeek() === $name) {
			$this->contextPop();
		}

		switch ($name) {
			case self::EL_MWG_RS_NAME:
				$this->rsName = $this->text;
				break;
			case self::EL_MWG_RS_TYPE:
				$this->rsType = $this->text;
				break;
			case self::EL_PS_AUTHORS_POSITION:
			case self::EL_PS_CAPTION_WRITER:
			case self::EL_PS_CITY:
			case self::EL_PS_COUNTRY:
			case self::EL_PS_CREDIT:
			case self::EL_PS_DATE_CREATED:
			case self::EL_PS_HEADLINE:
			case self::EL_PS_INSTRUCTIONS:
			case self::EL_PS_SOURCE:
			case self::EL_PS_STATE:
			case self::EL_AS_CAPTION:
				$this->addVal($this->formatKey($name), $this->text);
				break;
			case self::EL_AS_CATEGORIES:
				if ($this->text !== null) {
					$categories = AcdsCategories::fromData($this->text)->getArray();
					$this->addVal($this->formatKey($name), $categories);
				}
				break;
			case self::EL_RDF_LI:
				$parent = $this->contextPeek();
				if ($parent === self::EL_MWG_RS_REGIONS) {
					if ($this->rsType === 'Face' && $this->rsName !== null) {
						$this->addVal('people', $this->rsName);
					}
					$this->rsName = null;
					$this->rsType = null;
					break;
				}
				if ($parent === self::EL_DIGIKAM_TAGS_LIST && !empty($this->text)) {
					$this->addHierVal('tags', $this->text);
					break;
				}
				if (in_array($parent, [self::EL_DC_CREATOR, self::EL_DC_DESCRIPTION, self::EL_DC_RIGHTS, self::EL_DC_SUBJECT, self::EL_DC_TITLE], true)) {
					$this->addVal($this->formatKey($parent), $this->text);
				}
				break;
		}
	}

	public function charData($parser, string $data): void {
		$this->text .= $data;
	}

	private function addValIfExists(string $key, array $attributes): void {
		if (array_key_exists($key, $attributes)) {
			$this->addVal($this->formatKey($key), $attributes[$key]);
		}
	}

	private function addVal(string $key, mixed $value): void {
		if ($value === null || $value === '' || $value === []) {
			return;
		}

		if (is_string($value) && $key === 'dateCreated' && strlen($value) > 10 && $value[10] === 'T') {
			$value[10] = ' ';
		}

		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [$value];
		} else {
			$this->data[$key][] = $value;
		}
	}

	private function addHierVal(string $key, string $value): void {
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [$value];
			return;
		}

		$prevIndex = count($this->data[$key]) - 1;
		if ($prevIndex >= 0) {
			$previous = $this->data[$key][$prevIndex];
			if (is_string($previous) && $previous === substr($value, 0, strlen($previous))) {
				$this->data[$key][$prevIndex] = $value;
				return;
			}
		}

		$this->data[$key][] = $value;
	}

	private function formatKey(string $key): string {
		$pos = strrpos($key, ':');
		if ($pos !== false) {
			$key = substr($key, $pos + 1);
		}

		return lcfirst($key);
	}

	private function contextPush(string $value): void {
		$this->context[] = $value;
	}

	private function contextPop(): ?string {
		return array_pop($this->context);
	}

	private function contextPeek(): ?string {
		if ($this->context === []) {
			return null;
		}

		return $this->context[count($this->context) - 1];
	}
}
