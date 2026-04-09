<?php
declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\Files_FullTextSearch_EXIF\AppInfo\Application;
use OCP\Util;

Util::addScript(Application::APP_NAME, 'admin.elements');
Util::addScript(Application::APP_NAME, 'admin.settings');
Util::addScript(Application::APP_NAME, 'admin');
?>

<div id="files-exif-metadata" class="section">
	<h2><?php p($l->t('Files - EXIF metadata indexing')) ?></h2>

	<div class="div-table">
		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Enable metadata extraction')) ?></span>
				<br/>
				<em><?php p($l->t('Extract metadata from supported image files and add it to the full text index.')) ?></em>
			</div>
			<div class="div-table-col">
				<input type="checkbox" id="exif_enabled" value="1"/>
			</div>
		</div>

		<div class="div-table-row exif_enabled">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Maximum file size (MB)')) ?></span>
				<br/>
				<em><?php p($l->t('Files larger than this are skipped. Set 0 for no limit.')) ?></em>
			</div>
			<div class="div-table-col">
				<input type="text" class="small" id="exif_max_size_mb" value=""/>
			</div>
		</div>

		<div class="div-table-row exif_enabled">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Formats')) ?></span>
				<br/>
				<em><?php p($l->t('Enable or disable metadata extraction per image format.')) ?></em>
			</div>
			<div class="div-table-col">
				<label><input type="checkbox" id="exif_format_jpeg" value="1"/> JPEG</label><br/>
				<label><input type="checkbox" id="exif_format_tiff" value="1"/> TIFF</label><br/>
				<label><input type="checkbox" id="exif_format_png" value="1"/> PNG</label><br/>
				<label><input type="checkbox" id="exif_format_heic" value="1"/> HEIC</label>
			</div>
		</div>
	</div>
</div>
