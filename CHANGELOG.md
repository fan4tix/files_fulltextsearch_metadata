<!--
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog

## 0.1.1

- Renamed app ID from files_fulltextsearch_exif to files_fulltextsearch_metadata
- Added audio/video metadata extraction support (ID3/container tags)
- Added admin toggles for audio/video metadata extraction
- Improved smoke test reliability with search retry logic after indexing

## 0.1.0

- Initial implementation of EXIF metadata indexing extension
- Added JPEG/TIFF/PNG/HEIC metadata extraction
- Added admin settings and fail-silent parser hardening
- Added local lint and lightweight parser/formatter tests
