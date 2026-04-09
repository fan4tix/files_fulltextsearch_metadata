<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

spl_autoload_register(static function (string $class): void {
	$prefix = 'OCA\\Files_FullTextSearch_EXIF\\';
	if (!str_starts_with($class, $prefix)) {
		return;
	}

	$relative = substr($class, strlen($prefix));
	$path = __DIR__ . '/../lib/' . str_replace('\\', '/', $relative) . '.php';
	if (is_file($path)) {
		require_once $path;
	}
});

use OCA\Files_FullTextSearch_EXIF\Service\AcdsCategories;
use OCA\Files_FullTextSearch_EXIF\Service\HeicMetadata;
use OCA\Files_FullTextSearch_EXIF\Service\MetadataExtractionService;
use OCA\Files_FullTextSearch_EXIF\Service\MetadataTextFormatter;
use OCA\Files_FullTextSearch_EXIF\Service\PngMetadata;
use OCA\Files_FullTextSearch_EXIF\Service\XmpMetadata;

$tests = [];

$tests['formatter flattens nested metadata'] = static function (): void {
	$text = MetadataTextFormatter::flatten([
		'IFD0' => ['Model' => 'Canon EOS R8', 'Make' => 'Canon'],
		'XMP' => ['tags' => ['travel', 'sea']]
	]);

	assertContains($text, 'IFD0.Model: Canon EOS R8');
	assertContains($text, 'IFD0.Make: Canon');
	assertContains($text, 'XMP.tags: travel');
	assertContains($text, 'XMP.tags: sea');
};

$tests['extract handles missing file silently'] = static function (): void {
	$service = new MetadataExtractionService();
	$res = $service->extract('/definitely/does/not/exist.jpg', 'image/jpeg');
	assertTrue(is_array($res));
	assertSame([], $res);
};

$tests['extract handles malformed jpeg silently'] = static function (): void {
	$service = new MetadataExtractionService();
	$file = tempnam(sys_get_temp_dir(), 'exif-jpg-');
	file_put_contents($file, "not-a-jpeg\n\x00\x01");
	try {
		$res = $service->extract($file, 'image/jpeg');
		assertTrue(is_array($res));
	} finally {
		@unlink($file);
	}
};

$tests['extract handles malformed png silently'] = static function (): void {
	$service = new MetadataExtractionService();
	$file = tempnam(sys_get_temp_dir(), 'exif-png-');
	file_put_contents($file, "\x89PNG\r\n\x1A\n" . str_repeat("\x00", 64));
	try {
		$res = $service->extract($file, 'image/png');
		assertTrue(is_array($res));
	} finally {
		@unlink($file);
	}
};

$tests['xmp parser ignores malformed xml'] = static function (): void {
	$xmp = XmpMetadata::fromData('<rdf:Description><broken></rdf:Description>');
	assertTrue(is_array($xmp->getArray()));
};

$tests['acds parser ignores malformed xml'] = static function (): void {
	$acds = AcdsCategories::fromData('<Category Assigned="1"><Category>oops');
	assertTrue(is_array($acds->getArray()));
};

$tests['png parser handles garbage stream'] = static function (): void {
	$stream = fopen('php://memory', 'rb+');
	fwrite($stream, 'garbage');
	rewind($stream);
	try {
		$png = PngMetadata::fromFile($stream);
		assertSame([], $png->getTextChunks());
	} finally {
		fclose($stream);
	}
};

$tests['heic parser handles garbage stream'] = static function (): void {
	$stream = fopen('php://memory', 'rb+');
	fwrite($stream, str_repeat('x', 64));
	rewind($stream);
	try {
		$heic = HeicMetadata::fromFile($stream);
		assertTrue($heic === null || is_array($heic->getExif()));
	} finally {
		fclose($stream);
	}
};

$passed = 0;
$failed = 0;

foreach ($tests as $name => $test) {
	try {
		$test();
		echo "PASS: {$name}\n";
		$passed++;
	} catch (Throwable $e) {
		echo "FAIL: {$name}\n";
		echo '  ' . $e->getMessage() . "\n";
		$failed++;
	}
}

echo "\nSummary: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);

function assertTrue(bool $value, string $message = 'Expected true'): void {
	if (!$value) {
		throw new RuntimeException($message);
	}
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void {
	if ($expected !== $actual) {
		$msg = $message !== '' ? $message : 'Expected values to be identical';
		throw new RuntimeException($msg . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
	}
}

function assertContains(string $haystack, string $needle, string $message = ''): void {
	if (!str_contains($haystack, $needle)) {
		$msg = $message !== '' ? $message : 'Expected string to contain substring';
		throw new RuntimeException($msg . ': missing ' . $needle);
	}
}
