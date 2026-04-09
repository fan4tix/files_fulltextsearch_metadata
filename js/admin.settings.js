/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OC */
/** global: fts_admin_settings */
/** global: fts_exif_elements */

var fts_exif_settings = {
	isEnabled: function (value) {
		return value === '1' || value === 1 || value === true;
	},

	refreshSettingPage: function () {
		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/files_fulltextsearch_metadata/admin/settings')
		}).done(function (res) {
			fts_exif_settings.updateSettingPage(res);
		});
	},

	updateSettingPage: function (result) {
		fts_exif_elements.exif_enabled.prop('checked', fts_exif_settings.isEnabled(result.exif_enabled));
		fts_exif_elements.exif_max_size_mb.val(result.exif_max_size_mb);
		fts_exif_elements.exif_format_jpeg.prop('checked', fts_exif_settings.isEnabled(result.exif_format_jpeg));
		fts_exif_elements.exif_format_tiff.prop('checked', fts_exif_settings.isEnabled(result.exif_format_tiff));
		fts_exif_elements.exif_format_png.prop('checked', fts_exif_settings.isEnabled(result.exif_format_png));
		fts_exif_elements.exif_format_heic.prop('checked', fts_exif_settings.isEnabled(result.exif_format_heic));
		fts_exif_elements.exif_format_audio.prop('checked', fts_exif_settings.isEnabled(result.exif_format_audio));
		fts_exif_elements.exif_format_video.prop('checked', fts_exif_settings.isEnabled(result.exif_format_video));

		fts_admin_settings.tagSettingsAsSaved(fts_exif_elements.exif_div);

		if (fts_exif_settings.isEnabled(result.exif_enabled)) {
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
			exif_format_heic: (fts_exif_elements.exif_format_heic.is(':checked')) ? 1 : 0,
			exif_format_audio: (fts_exif_elements.exif_format_audio.is(':checked')) ? 1 : 0,
			exif_format_video: (fts_exif_elements.exif_format_video.is(':checked')) ? 1 : 0
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/files_fulltextsearch_metadata/admin/settings'),
			data: {
				data: data
			}
		}).done(function (res) {
			fts_exif_settings.updateSettingPage(res);
		});
	}
};
