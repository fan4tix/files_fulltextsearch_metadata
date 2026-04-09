/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OC */
/** global: fts_admin_settings */
/** global: fts_exif_elements */

var fts_exif_settings = {
	refreshSettingPage: function () {
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/files_fulltextsearch_exif/admin/settings')
		}).done(function (res) {
			fts_exif_settings.updateSettingPage(res);
		});
	},

	updateSettingPage: function (result) {
		fts_exif_elements.exif_enabled.prop('checked', (result.exif_enabled === '1'));
		fts_exif_elements.exif_max_size_mb.val(result.exif_max_size_mb);
		fts_exif_elements.exif_format_jpeg.prop('checked', (result.exif_format_jpeg === '1'));
		fts_exif_elements.exif_format_tiff.prop('checked', (result.exif_format_tiff === '1'));
		fts_exif_elements.exif_format_png.prop('checked', (result.exif_format_png === '1'));
		fts_exif_elements.exif_format_heic.prop('checked', (result.exif_format_heic === '1'));

		fts_admin_settings.tagSettingsAsSaved(fts_exif_elements.exif_div);

		if (result.exif_enabled === '1') {
			fts_exif_elements.exif_div.find('.exif_enabled').fadeTo(300, 1);
			fts_exif_elements.exif_div.find('.exif_enabled').find('*').prop('disabled', false);
		} else {
			fts_exif_elements.exif_div.find('.exif_enabled').fadeTo(300, 0.6);
			fts_exif_elements.exif_div.find('.exif_enabled').find('*').prop('disabled', true);
		}
	},

	saveSettings: function () {
		var data = {
			exif_enabled: (fts_exif_elements.exif_enabled.is(':checked')) ? 1 : 0,
			exif_max_size_mb: fts_exif_elements.exif_max_size_mb.val(),
			exif_format_jpeg: (fts_exif_elements.exif_format_jpeg.is(':checked')) ? 1 : 0,
			exif_format_tiff: (fts_exif_elements.exif_format_tiff.is(':checked')) ? 1 : 0,
			exif_format_png: (fts_exif_elements.exif_format_png.is(':checked')) ? 1 : 0,
			exif_format_heic: (fts_exif_elements.exif_format_heic.is(':checked')) ? 1 : 0
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/files_fulltextsearch_exif/admin/settings'),
			data: {
				data: data
			}
		}).done(function (res) {
			fts_exif_settings.updateSettingPage(res);
		});
	}
};
