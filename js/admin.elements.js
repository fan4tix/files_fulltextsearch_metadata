/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: fts_admin_settings */
/** global: fts_exif_settings */

var fts_exif_elements = {
	exif_div: null,
	exif_enabled: null,
	exif_max_size_mb: null,
	exif_format_jpeg: null,
	exif_format_tiff: null,
	exif_format_png: null,
	exif_format_heic: null,

	init: function () {
		fts_exif_elements.exif_div = $('#files-exif-metadata');
		fts_exif_elements.exif_enabled = $('#exif_enabled');
		fts_exif_elements.exif_max_size_mb = $('#exif_max_size_mb');
		fts_exif_elements.exif_format_jpeg = $('#exif_format_jpeg');
		fts_exif_elements.exif_format_tiff = $('#exif_format_tiff');
		fts_exif_elements.exif_format_png = $('#exif_format_png');
		fts_exif_elements.exif_format_heic = $('#exif_format_heic');

		fts_exif_elements.exif_enabled.on('change', fts_exif_elements.updateSettings);
		fts_exif_elements.exif_max_size_mb.on('change', fts_exif_elements.updateSettings);
		fts_exif_elements.exif_format_jpeg.on('change', fts_exif_elements.updateSettings);
		fts_exif_elements.exif_format_tiff.on('change', fts_exif_elements.updateSettings);
		fts_exif_elements.exif_format_png.on('change', fts_exif_elements.updateSettings);
		fts_exif_elements.exif_format_heic.on('change', fts_exif_elements.updateSettings);
	},

	updateSettings: function () {
		fts_admin_settings.tagSettingsAsNotSaved($(this));
		fts_exif_settings.saveSettings();
	}
};
