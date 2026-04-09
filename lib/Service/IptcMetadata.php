<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Metadata\Service;

class IptcMetadata {

	private const MAP = [
		'2#005' => 'title',
		'2#025' => 'subject',
		'2#040' => 'instructions',
		'2#055' => 'dateCreated',
		'2#080' => 'creator',
		'2#085' => 'authorsPosition',
		'2#090' => 'city',
		'2#095' => 'state',
		'2#101' => 'country',
		'2#105' => 'headline',
		'2#110' => 'credit',
		'2#115' => 'source',
		'2#116' => 'rights',
		'2#120' => 'description',
		'2#122' => 'captionWriter'
	];

	private array $data = [];

	private function __construct(string $binary) {
		if (!function_exists('iptcparse')) {
			return;
		}

		$iptc = @iptcparse($binary);
		if (!is_array($iptc)) {
			return;
		}

		foreach (self::MAP as $tag => $key) {
			if (array_key_exists($tag, $iptc)) {
				$this->data[$key] = $iptc[$tag];
			}
		}
	}

	public static function fromData(string $binary): self {
		return new self($binary);
	}

	public function getArray(): array {
		return $this->data;
	}
}
