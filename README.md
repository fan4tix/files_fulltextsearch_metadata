<!--
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# files_fulltextsearch_metadata

Index image metadata (EXIF/IPTC/XMP and PNG text chunks where available) and audio/video tag metadata into Nextcloud Full text search.

This app is an extension for files_fulltextsearch and listens to Files_FullTextSearch events.

## Supported formats (v1)

- JPEG
- TIFF
- PNG
- HEIC
- Audio (ID3/container tags)
- Video (container tags)

## Indexed output

- Searchable part: exif
- Structured payload: document more field under exif

## Admin settings

- Enable/disable metadata extraction
- Maximum indexed file size (MB)
- Per-format toggles for JPEG, TIFF, PNG, HEIC, Audio, Video

## Local development

### Lint

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

### Run lightweight tests

```bash
php tests/run.php
```

The test runner is dependency-free and validates flattening behavior plus parser hardening on malformed input.

## Acknowledgements

This project reuses and adapts substantial implementation ideas and code from the following Nextcloud projects:

- files_fulltextsearch_tesseract:
  https://github.com/nextcloud/files_fulltextsearch_tesseract
- nextcloud-metadata:
  https://github.com/nextcloud/metadata

Notable borrowed/adapted areas include app wiring patterns, metadata extraction architecture, and bundled media metadata parsing integrations.
