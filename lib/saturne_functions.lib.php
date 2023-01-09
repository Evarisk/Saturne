<?php

/**
 *      Print llxHeader with Saturne custom enhancements
 *
 *      @param      string				$module
 *      @param      string				$action
 *      @param      string				$subaction
 *      @param      integer				$load_media_gallery
 *      @param      string				$head
 *      @param      string				$title
 *      @param      string				$help_url
 *      @param      string				$target
 *      @param      integer				$disablejs
 *      @param      integer				$disablehead
 * 		@param		array				$arrayofjs
 * 		@param		array				$arrayofcss
 * 		@param		string				$morequerystring
 * 		@param		string				$morecssonbody
 * 		@param		string				$replacemainareaby
 * 		@param		integer				$disablenofollow
 * 		@param		integer				$disablenoindex
 */
function saturneHeader($module, $action, $subaction, $load_media_gallery, $head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0) {

	global $langs, $conf;

	//CSS
	$arrayofcss[] = '/saturne/css/saturne.css';

	//JS
	$arrayofjs[]  = '/saturne/js/saturne.js.php';

	llxHeader($head, $title, $help_url, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody, $replacemainareaby, $disablenofollow, $disablenoindex);

	if ($load_media_gallery) {
		//Media gallery
		include __DIR__ . '/../core/tpl/medias_gallery_modal.tpl.php';
	}
}

/**
 *      Print medias from media gallery
 *
 *      @param      string				$module
 *      @param      string				$modulepart
 *      @param      string				$sdir
 *      @param      integer				$size
 *      @param      string				$maxHeight
 *      @param      string				$maxWidth
 *      @param      string				$offset
 */
function saturne_show_medias($module, $modulepart = 'ecm', $sdir, $size = 0, $maxHeight = 80, $maxWidth = 80, $offset = 1)
{
	global $conf;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'date';
	$sortorder = 'desc';
	$dir       = $sdir . '/';

	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC));
	$j         = 0;

	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		$moduleImageNumberPerPageConf = strtoupper($module) . '_DISPLAY_NUMBER_MEDIA_GALLERY';
		for ($i = (($offset - 1) * $conf->global->$moduleImageNumberPerPageConf); $i < ($conf->global->$moduleImageNumberPerPageConf + (($offset - 1) * $conf->global->$moduleImageNumberPerPageConf));  $i++) {
			$file = $filearray[$i]['name'];

			if (image_format_supported($file) >= 0) {
				$nbphoto++;

				if ($size == 'mini' || $size == 'small') {   // Format vignette
					$relativepath = $module . '/medias/thumbs';
					$modulepart   = 'ecm';
					$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);

					$file_infos = pathinfo($file);
					$filename = $file_infos['filename'].'_'.$size.'.'.$file_infos['extension'];

					?>

					<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>">
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
}

/**
 *      Show medias linked to an object
 *
 *      @param      string				$modulepart
 *      @param      string				$sdir
 *      @param      integer				$size
 *      @param      integer				$nbmax
 *      @param      integer				$nbbyrow
 *      @param      integer				$showfilename
 *      @param      integer				$showaction
 *      @param      integer				$maxHeight
 *      @param      integer				$maxWidth
 *      @param      integer				$nolink
 *      @param      integer				$notitle
 *      @param      integer				$usesharelink
 *      @param      string				$subdir
 *      @param      object				$object
 *      @param      string				$favorite_field
 *      @param      integer				$show_favorite_button
 *      @param      integer				$show_unlink_button
 *      @param      integer				$use_mini_format
 *      @param      integer				$show_only_favorite
 *      @param      string				$morecss
 *      @param      integer				$showdiv
 *      @return     string				Show medias linked
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
				//if (! utf8_check($filename)) $filename=utf8_encode($filename);	// To be sure file is stored in UTF8 in memory

				//if (dol_is_file($dir.$filename) && image_format_supported($filename) >= 0)
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
							$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
							if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}

						// Show image (width height=$maxHeight)
						// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
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
							// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
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

					if (empty($size)) {     // Format origine
						$return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';

						if ($showfilename) $return .= '<br>' . $viewfilename;
					}

					if ($size == 'large') {
						$relativefile = preg_replace('/^\//', '', $pdir . $photo);
						if (empty($nolink)) {
							$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
							if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}
						$return .= '<img width="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_LARGE . '" height="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_LARGE . '" class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';
						if ($showfilename) $return .= '<br>' . $viewfilename;
					}

					if ($size == 'medium') {
						$relativefile = preg_replace('/^\//', '', $pdir . $photo);
						if (empty($nolink)) {
							$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
							if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}
						$return .= '<img width="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_MEDIUM . '" height="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_MEDIUM . '" class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';
						if ($showfilename) $return .= '<br>' . $viewfilename;
					}

					// On continue ou on arrete de boucler ?
					if ($nbmax && $nbphoto >= $nbmax) break;
				}

				//$return .= '<div>';

				if ($show_favorite_button) {
					$return .= '
				<div class="wpeo-button button-square-50 button-blue media-gallery-favorite '. ($object->$favorite_field == '' && $i == 0 ? 'favorite' : ($object->$favorite_field == $photo ? 'favorite' : '')) .'" value="' . $object->id . '">
					<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
					<input class="filename" type="hidden" value="' . $photo . '">
					<i class="' . ($object->$favorite_field == '' && $i == 0 ? 'fas' : ($object->$favorite_field == $photo ? 'fas' : 'far')) . ' fa-star button-icon"></i>
				</div>';
				}
				if ($show_unlink_button) {
					$return .= '
				<div class="wpeo-button button-square-50 button-grey media-gallery-unlink" value="' . $object->id . '">
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
		//$return .= "</div>\n";

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

/**
 *      Load array of pages to display
 *
 *      @param      integer				$pagesCounter
 *      @param      array				$page_array
 *      @param      integer				$offset
 *      @return     array				Pages number array
 *
 */
function saturne_load_pagination($pagesCounter, $page_array, $offset) {

	if (!is_array($page_array) || empty($page_array)) {
		$offset = $offset ?: 1;
		$page_array = [];
		$page_array[] = '<i class="fas fa-arrow-left"></i>';

		if ($pagesCounter > 4) {
			if ($offset > 2) {
				$page_array[] = '...';
			}

			if ($offset == 1) {
				$page_array[] = $offset;
				$page_array[] = $offset + 1;
				$page_array[] = $offset + 2;
				$page_array[] = $offset + 3;
			} else if ($offset > 1 && $offset < $pagesCounter) {
				if ($offset == $pagesCounter - 1) {
					$page_array[] = $offset - 2;
				}
				$page_array[] = $offset - 1;
				$page_array[] = $offset;
				$page_array[] = $offset + 1;
				if ($offset == 2) {
					$page_array[] = $offset + 2;
				}
			}  else if ($offset == $pagesCounter) {
				$page_array[] = $offset - 3;
				$page_array[] = $offset - 2;
				$page_array[] = $offset - 1;
				$page_array[] = $offset;
			}

			if ($pagesCounter > 3 && $offset < $pagesCounter - 1) {
				$page_array[] = '...';
			}
		} else {
			for ($i = 1; $i <= $pagesCounter; $i++) {
				$page_array[] = $i;
			}
		}

		$page_array[] = '<i class="fas fa-arrow-right"></i>';
	}

	return $page_array;
}

/**
 *      Show pages based on loaded pages array
 *
 *      @param      integer				$pagesCounter
 *      @param      array				$page_array
 *      @param      integer				$offset
 *      @return     string				Pages html content
 *
 */
function saturne_show_pagination($pagesCounter, $page_array, $offset) {
	$offset = $offset ?: 1;
	$return = '<ul class="wpeo-pagination">';
	$return .= '<input hidden id="pagesCounter" value="'. ($pagesCounter) .'">';
	$return .= '<input hidden id="containerToRefresh" value="media_gallery">';
	$return .= '<input hidden id="currentOffset" value="'. ($offset ?: 1) .'">';

	foreach ($page_array as $pageNumber) {

		$return .= '<li class="pagination-element ' . ($pageNumber == $offset ? 'pagination-current' : ($pageNumber == 1 && !$offset ? 'pagination-current' : '')) . '">';

		if ($pageNumber == '...') {
			$return .= '<span>'. $pageNumber .'</span>';
		} else if ($pageNumber == '<i class="fas fa-arrow-left"></i>') {
			$return .= '<a class="select-page arrow arrow-left" value="' . max(($offset - 1), 1) . '"><i class="fas fa-arrow-left"></i></a>';
		} else if ($pageNumber == '<i class="fas fa-arrow-right"></i>') {
			$return .= '<a class="select-page arrow arrow-right" value="' . min(($offset + 1), $pagesCounter) . '"><i class="fas fa-arrow-right"></i></a>';
		} else {
			$return .= '<a class="select-page" value="' . $pageNumber . '">' . $pageNumber . '</a>';
		}

		$return .= '</li>';
	}

	$return .= '</ul>';
	return $return;
}

/**
 *      Show pages based on loaded pages array
 *
 *      @param      integer				$pagesCounter
 *      @param      array				$page_array
 *      @param      integer				$offset
 *      @return     string				Pages html content
 *
 */
function saturne_check_access($module, $object, $permission) {

	global $conf, $langs, $user;

	if (!$permission)     accessforbidden();
	if ($user->socid > 0) accessforbidden();

	$langs->loadLangs(['saturne@saturne']);

	if ($conf->multicompany->enabled) {
		if ($conf->$module->enabled) {
			if ($object->id > 0) {
				if ($object->entity != $conf->entity) {
					setEventMessage($langs->trans('ChangeEntityRedirection'), 'warnings');
					$urltogo = dol_buildpath('/custom/' . $module . '/' . $module . 'index.php?mainmenu=' . $module, 1);
					header("Location: " . $urltogo);
					exit;
				}
			}
		} else {
			setEventMessage($langs->trans('EnableModule', ucfirst($module)), 'warnings');
			$urltogo = dol_buildpath('/admin/modules.php?search_nature=external_Evarisk', 1);
			header("Location: " . $urltogo);
			exit;
		}
	}
}

