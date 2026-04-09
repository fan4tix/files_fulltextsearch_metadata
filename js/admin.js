/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: fts_exif_elements */
/** global: fts_exif_settings */

$(document).ready(function () {
	var Fts_exif = function () {
		$.extend(Fts_exif.prototype, fts_exif_elements);
		$.extend(Fts_exif.prototype, fts_exif_settings);

		fts_exif_elements.init();
		fts_exif_settings.refreshSettingPage();
	};

	OCA.FullTextSearchAdmin.fts_exif = Fts_exif;
	OCA.FullTextSearchAdmin.fts_exif.settings = new Fts_exif();
});
