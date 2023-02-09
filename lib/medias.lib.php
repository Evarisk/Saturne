<?php

/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      Print medias from media gallery
 *
 *      @param      string				$moduleName 	Module name
 *      @param      string				$modulepart Submodule name
 *      @param      string				$sdir 		Directory path
 *      @param      integer				$size 		Media size
 *      @param      string				$maxHeight	Media max height
 *      @param      string				$maxWidth	Media max width
 *      @param      string				$offset		Media gallery offset page
 */
function saturne_show_medias($moduleName, $modulepart = 'ecm', $sdir, $size = 0, $maxHeight = 80, $maxWidth = 80, $offset = 1)
{
	global $conf, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'date';
	$sortorder = 'desc';
	$dir       = $sdir . '/';

	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC));
	$j         = 0;

	if (count($filearray)) {

		print '<div class="wpeo-gridlayout grid-5 grid-gap-3 grid-margin-2 ecm-photo-list ecm-photo-list">';

		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}

		$moduleImageNumberPerPageConf = strtoupper($moduleName) . '_DISPLAY_NUMBER_MEDIA_GALLERY';
		for ($i = (($offset - 1) * $conf->global->$moduleImageNumberPerPageConf); $i < ($conf->global->$moduleImageNumberPerPageConf + (($offset - 1) * $conf->global->$moduleImageNumberPerPageConf));  $i++) {
			$file = $filearray[$i]['name'];

			if (image_format_supported($file) >= 0) {
				$nbphoto++;

				if ($size == 'mini' || $size == 'small') {   // Format vignette
					$relativepath = $moduleName . '/medias/thumbs';
					$modulepart   = 'ecm';
					$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);

					$file_infos = pathinfo($file);
					$filename = $file_infos['filename'].'_'.$size.'.'.$file_infos['extension'];

					?>

					<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>">
						<figure class="photo-image">
							<?php
							$file = preg_replace("/'/", "\\'", $file);
							$urladvanced = getAdvancedPreviewUrl($modulepart, $moduleName . '/medias/' .$file, 0, 'entity=' . $conf->entity);
							?>
							<a class="clicked-photo-preview" href="<?php echo $urladvanced; ?>"><i class="fas fa-2x fa-search-plus"></i></a>
							<?php if (image_format_supported($file) >= 0) : ?>
								<?php $fullpath = $path . '/' . urlencode($filename) . '&entity=' . $conf->entity; ?>
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
		print '</div>';
	} else {
		print '<br>';
		print '<div class="ecm-photo-list ecm-photo-list">';
		print $langs->trans("EmptyMediaGallery");
		print '</div>';
	}
}

/**
 *      Show medias linked to an object
 *
 *      @param      string				$modulepart 			Submodule name
 *      @param      string				$sdir					Directory path
 *      @param      integer				$size					Medias size
 *      @param      integer				$nbmax					Max number of medias shown per page
 *      @param      integer				$nbbyrow				Number of images per row
 *      @param      integer				$showfilename			Show filename under image
 *      @param      integer				$showaction				Show icon with action links
 *      @param      integer				$maxHeight				Media max height
 *      @param      integer				$maxWidth				Media max width
 *      @param      integer				$nolink					Do not add href link to image
 *      @param      integer				$notitle				Do not add title tag on image
 *      @param      integer				$usesharelink			Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
 *      @param      string				$subdir					Subdir for file
 *      @param      object				$object					Object linked to show medias of
 *      @param      string				$favorite_field			Name of favorite sql field of object
 *      @param      integer				$show_favorite_button	Show or hide favorite button
 *      @param      integer				$show_unlink_button		Show or hide unlink button
 *      @param      integer				$use_mini_format		Use media mini format instead of small
 *      @param      integer				$show_only_favorite		Show only object favorite media
 *      @param      string				$morecss				Add more CSS on link
 *      @param      integer				$showdiv				Add div with "media-container" class
 *      @return     string				$return 				Show medias linked
 *
 */
function saturne_show_medias_linked($modulepart = 'ecm', $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = "", $object = null, $favorite_field = 'photo', $show_favorite_button = 1, $show_unlink_button = 1 , $use_mini_format = 0, $show_only_favorite = 0, $morecss = '', $showdiv = 1)
{
	global $conf, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	//	$dir  = $sdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');
	//	$pdir = $subdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');

	$dir  = $sdir . '/';
	$pdir = $subdir . '/';

	$dirthumb  = $dir . 'thumbs/';
	$pdirthumb = $pdir . 'thumbs/';

	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

	$i = 0;
	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}

		foreach ($filearray as $file) {
			$photo   = '';
			$filename    = $file['name'];

			$fileFullName = $file['fullname'];

			if (($show_only_favorite && $object->$favorite_field == $filename) || !$show_only_favorite) {
				if ($showdiv) {
					$return .= '<div class="media-container">';
				}

				$return .= '<input hidden class="file-path" value="'. $fileFullName .'">';
				if (image_format_supported($filename) >= 0) {
					$nbphoto++;
					$photo        = $filename;
					$viewfilename = $filename;

					if ($size == 1 || $size == 'small') {   // Format vignette
						// Find name of thumb file
						if ($use_mini_format) {
							$photo_vignette = basename(getImageFileNameForSize($dir . $filename, '_mini'));
						} else {
							$photo_vignette = basename(getImageFileNameForSize($dir . $filename, '_small'));
						}

						if ( ! dol_is_file($dirthumb . $photo_vignette)) $photo_vignette = '';

						// Get filesize of original file
						$imgarray = dol_getImageSize($dir . $photo);

						if ($nbbyrow > 0) {
							if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

							if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
							$return                               .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%" class="photo">';
						} elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

						$return .= "\n";

						$relativefile = preg_replace('/^\//', '', $pdir . $photo);
						if (empty($nolink)) {
							$relativefile              = preg_replace("/'/", "\\'", $relativefile);
							$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
							if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}

						// Show image (width height=$maxHeight)
						$alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
						$alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
						if ($notitle) $alt = '';
						if ($usesharelink) {
							if ($file['share']) {
								if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
									$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
									$return .= '<img width="65" height="65" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($file['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
								} else {
									$return .= '<!-- Show original file -->';
									$return .= '<img  width="65" height="65" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($file['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
								}
							} else {
								$return .= '<!-- Show nophoto file (because file is not shared) -->';
								$return .= '<img  width="65" height="65" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . dol_escape_htmltag($alt) . '">';
							}
						} else {
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
								$return .= '<!-- Show thumb -->';
								$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .'"  src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '">';
							}
						}

						if (empty($nolink)) $return .= '</a>';
						$return                     .= "\n";
						if ($showfilename) $return  .= '<br>' . $viewfilename;
						if ($showaction) {
							$return .= '<br>';
							if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
								$return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=addthumb&amp;file=' . urlencode($pdir . $viewfilename) . '">' . img_picto($langs->trans('GenerateThumb'), 'refresh') . '&nbsp;&nbsp;</a>';
							}
						}
						$return .= "\n";

						if ($nbbyrow > 0) {
							$return                                 .= '</td>';
							if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
						} elseif ($nbbyrow < 0) $return .= '</td>';
					}

					if (empty($size)) {
						// Format origine
						$return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';
						if ($showfilename) {
							$return .= '<br>' . $viewfilename;
						}
					}

					if ($size == 'large' || $size == 'medium') {
						$relativefile = preg_replace('/^\//', '', $pdir . $photo);
						if (empty($nolink)) {
							$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
							if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}
						$widthName = 'SATURNE_MEDIA_MAX_WIDTH_' . strtoupper($size);
						$heightName = 'SATURNE_MEDIA_MAX_HEIGHT_' . strtoupper($size);
						$return .= '<img width="' . $conf->global->$widthName . '" height="' . $conf->global->$heightName . '" class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';
						if ($showfilename) $return .= '<br>' . $viewfilename;
					}

					// On continue ou on arrete de boucler ?
					if ($nbmax && $nbphoto >= $nbmax) break;
				}

				if ($show_favorite_button) {
					$return .=
						'<div class="wpeo-button button-square-50 button-blue media-gallery-favorite '. ($object->$favorite_field == '' && $i == 0 ? 'favorite' : ($object->$favorite_field == $photo ? 'favorite' : '')) .'" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="' . ($object->$favorite_field == '' && $i == 0 ? 'fas' : ($object->$favorite_field == $photo ? 'fas' : 'far')) . ' fa-star button-icon"></i>
						</div>';
				}
				if ($show_unlink_button) {
					$return .=
						'<div class="wpeo-button button-square-50 button-grey media-gallery-unlink" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="fas fa-unlink button-icon"></i>
						</div>';
				}
				if ($showdiv) {
					$return .= "</div>\n";
				}
				$i++;
			}
		}

		if ($size == 1 || $size == 'small') {
			if ($nbbyrow > 0) {
				// Ferme tableau
				while ($nbphoto % $nbbyrow) {
					$return .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return .= '</table>';
			}
		}
	}
	if (is_object($object)) {
		$object->nbphoto = $nbphoto;
	}
	return $return;
}
