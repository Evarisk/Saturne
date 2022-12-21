<?php

function saturneHeader($module, $head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0) {

	global $langs, $conf;

	//CSS
	$arrayofcss[] = '/saturne/css/saturne.css';

	//JS
	$arrayofjs[]  = '/saturne/js/saturne.js';

	llxHeader($head, $title, $help_url, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody, $replacemainareaby, $disablenofollow, $disablenoindex);

	//Media gallery
	include __DIR__ . '/../core/tpl/medias_gallery_modal.tpl.php';
}

function saturne_show_medias($module, $modulepart = 'ecm', $sdir, $size = 0, $maxHeight = 80, $maxWidth = 80, $offset = 0)
{
	global $conf;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'date';
	$sortorder = 'desc';
	$dir       = $sdir . '/';


	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC));
	$j         = 0;

	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		$moduleImageNumberPerPageConf = strtoupper($module) . '_DISPLAY_NUMBER_MEDIA_GALLERY';
		for ($i = 0 + ($offset * $conf->global->$moduleImageNumberPerPageConf); $i < $conf->global->$moduleImageNumberPerPageConf + ($offset * $conf->global->$moduleImageNumberPerPageConf);  $i++) {
			$file = $filearray[$i]['name'];

			if (image_format_supported($file) >= 0) {
				$nbphoto++;

				if ($size == 'mini' || $size == 'small') {   // Format vignette
					$relativepath = $module . '/medias/thumbs';
					$modulepart   = 'ecm';
					$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);

					$filename = preg_split('/\./',  $file);
					$filename = $filename[0].'_'.$size.'.'.$filename[1];

					?>

				<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>" element="risk-evaluation">
					<figure class="photo-image">
						<?php
						$urladvanced = getAdvancedPreviewUrl($modulepart, $module . '/medias/' .$file, 0, 'entity=' . $conf->entity); ?>
						<a class="clicked-photo-preview" href="<?php echo $urladvanced; ?>"><i class="fas fa-2x fa-search-plus"></i></a>
						<?php if (image_format_supported($file) >= 0) : ?>
							<?php $fullpath = $path . '/' . $filename . '&entity=' . $conf->entity; ?>
							<input class="filename" type="hidden" value="<?php echo $file; ?>">
							<img class="photo photo<?php echo $j ?>" height="<?php echo $maxHeight; ?>" width="<?php echo $maxWidth; ?>" src="<?php echo $fullpath; ?>">
						<?php endif; ?>
					</figure>
					<div class="title"><?php echo $file; ?></div>
					</div><?php
					$j++;
				}
			}
		}
	}

	return $return;
}

