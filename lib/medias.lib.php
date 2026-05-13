<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/medias.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne Medias
 */

include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

/**
 * Print medias from media gallery
 *
 * @param string $moduleName Module name
 * @param string $modulepart Submodule name
 * @param string $sdir       Directory path
 * @param string $size       Media size
 * @param int    $maxHeight  Media max height
 * @param int    $maxWidth   Media max width
 * @param int    $offset     Media gallery offset page
 */
function saturne_show_medias(string $moduleName, string $modulepart = 'ecm', string $sdir = '',string $size = '', int $maxHeight = 80, int $maxWidth = 80, int $offset = 1): void
{
	global $conf, $langs, $user, $moduleNameLowerCase;

	$sortfield = 'date';
	$sortorder = 'desc';
	$dir       = $sdir . '/';

	$nbphoto = 0;

	$filearray = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC));

    if (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS)) {
        $yesterdayTimeStamp = dol_time_plus_duree(dol_now(), -1, 'd');
        $filearray = array_filter($filearray, function($file) use ($yesterdayTimeStamp) {
            return $file['date'] > $yesterdayTimeStamp;
        });
    }
    if (getDolGlobalInt('SATURNE_MEDIA_GALLERY_SHOW_ALL_MEDIA_INFOS') && !empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS)) {
        $moduleObjectMedias = dol_dir_list($conf->$moduleNameLowerCase->multidir_output[$conf->entity ?? 1], 'files', 1, '', '.odt|.pdf|barcode|_mini|_medium|_small|_large');
        $filearray          = array_filter($filearray, function($file) use ($conf, $moduleNameLowerCase, $moduleObjectMedias) {
            $fileExists = array_search($file['name'], array_column($moduleObjectMedias, 'name'));
            return !$fileExists;
        });
    }

	$j         = 0;

	if (count($filearray)) {
		print '<div class="wpeo-gridlayout grid-5 grid-gap-3 grid-margin-2 ecm-photo-list ecm-photo-list">';

		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}

		$moduleImageNumberPerPageConf = strtoupper($moduleName) . '_DISPLAY_NUMBER_MEDIA_GALLERY';
		for ($i = (($offset - 1) * $conf->global->$moduleImageNumberPerPageConf); $i < ($conf->global->$moduleImageNumberPerPageConf + (($offset - 1) * $conf->global->$moduleImageNumberPerPageConf));  $i++) {

            $fileName = $filearray[$i]['name'];
            if (image_format_supported($fileName) >= 0) {
                $nbphoto++;

                if ($size == 'mini' || $size == 'small') {   // Format vignette
                    $relativepath = $moduleName . '/medias/thumbs';
                    $modulepart   = 'ecm';
                    $path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);

                    $file_infos = pathinfo($fileName);

                    // svg files aren't handled by vignette functions in images.lib, so they don't have thumbs
                    if ($file_infos['extension'] == 'svg') {
                        $path = preg_replace('/\/thumbs/', '', $path);
                        $shownFileName = $file_infos['filename'] . '.' . $file_infos['extension'];
                    } else {
                        $shownFileName = $file_infos['filename'] . '_' . $size . '.' . $file_infos['extension'];
                    }

                    ?>

                    <div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>">
                        <figure class="photo-image">
                            <?php
                            if (file_exists($filearray[$i]['path'] . '/thumbs/' . $shownFileName)) {
                                $advancedPreviewUrl = getAdvancedPreviewUrl($modulepart, $moduleName . '/medias/' . urlencode($fileName), 0, 'entity=' . $conf->entity);
                                $fullpath           = $path . '/' . urlencode($shownFileName) . '&entity=' . $conf->entity;
                                print '<input class="filename" type="hidden" value="' . $fileName . '">';
                                print '<a class="clicked-photo-preview" href="' . $advancedPreviewUrl . '"><i class="fas fa-2x fa-search-plus"></i></a>';
                                print '<img class="photo photo' . $j . '" width="' . $maxWidth . '" height="' . $maxHeight . '" data-src="' . $fullpath . '" loading="lazy">';
                            } else {
                                print '<input type="hidden" class="fullname" data-fullname="' . $filearray[$i]['fullname'] . '">';
                                print '<i class="clicked-photo-preview regenerate-thumbs fas fa-redo"></i>';
                                print '<img class="photo photo' . $j . '" width="' . $maxWidth . '" height="' . $maxHeight . '" data-src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" loading="lazy">';
                            } ?>
                        </figure>
                        <?php
                        if (getDolGlobalInt('SATURNE_MEDIA_GALLERY_SHOW_ALL_MEDIA_INFOS')) {
                            print saturne_get_media_linked_elements($moduleName, $fileName);
                        }
                        ?>
                        <div class="title"><?php echo $fileName; ?></div>
                    </div><?php
                    $j++;
                }
            }
		}
		print '</div>';
	} else {
		print '<br>';
		print '<div class="ecm-photo-list ecm-photo-list">';
		print $langs->trans('EmptyMediaGallery');
		print '</div>';
	}
}

/**
 * Show medias linked to an object
 *
 * @param  string      $modulepart           Submodule name
 * @param  string      $sdir                 Directory path
 * @param  int|string  $size                 Medias size
 * @param  int|string  $nbmax                Max number of medias shown per page
 * @param  int         $nbbyrow              Number of images per row
 * @param  int         $showfilename         Show filename under image
 * @param  int         $showaction           Show icon with action links
 * @param  int         $maxHeight            Media max height
 * @param  int         $maxWidth             Media max width
 * @param  int         $nolink 	             Do not add href link to image
 * @param  int         $notitle              Do not add title tag on image
 * @param  int         $usesharelink         Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
 * @param  string      $subdir               Subdir for file
 * @param  object|null $object               Object linked to show medias of
 * @param  string      $favorite_field       Name of favorite sql field of object
 * @param  int         $show_favorite_button Show or hide favorite button
 * @param  int         $show_unlink_button   Show or hide unlink button
 * @param  int         $use_mini_format      Use media mini format instead of small
 * @param  int         $show_only_favorite   Show only object favorite media
 * @param  string      $morecss              Add more CSS on link
 * @param  int         $showdiv              Add div with "media-container" class
 * @return string      $return               Show medias linked
 */
function saturne_show_medias_linked(string $modulepart = 'ecm', string $sdir, $size = 0, $nbmax = 0, int $nbbyrow = 5, int $showfilename = 0, int $showaction = 0, int $maxHeight = 120, int $maxWidth = 160, int $nolink = 0, int $notitle = 0, int $usesharelink = 0, string $subdir = '', object $object = null, string $favorite_field = 'photo', int $show_favorite_button = 1, int $show_unlink_button = 1 , int $use_mini_format = 0, int $show_only_favorite = 0, string $morecss = '', int $showdiv = 1, array $moreParams = []): string
{
	global $conf, $langs, $moduleNameUpperCase;

	$sortfield = 'position_name';
	$sortorder = 'desc';

	//	$dir  = $sdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');
	//	$pdir = $subdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');

	$dir  = $sdir . (substr($sdir, -1) == '/' ? '' : '/');
	$pdir = $subdir . (substr($subdir, -1) == '/' ? '' : '/');

	$dirthumb  = $dir . 'thumbs/';
	$pdirthumb = $pdir . 'thumbs/';

	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, 'files', 0,  $moreParams['filter'] ?? '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

	$i = 0;
	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		$favoriteExists = 0;
		foreach ($filearray as $file) {
			if ($file['name'] == $object->$favorite_field) {
				$favoriteExists = 1;
			}
		}

		foreach ($filearray as $file) {
			$photo    = '';
			$fileName = $file['name'];
			$filePath = $file['path'];

			if (($show_only_favorite && ($object->$favorite_field == $fileName || !$favoriteExists)) || !$show_only_favorite) {
				if ($showdiv) {
					$return .= '<div class="media-container">';
				}

				$return .= '<input hidden class="file-path" value="'. $filePath .'">';
				$return .= '<input hidden class="file-name" value="'. $fileName .'">';
				if (image_format_supported($fileName) >= 0) {
					$nbphoto++;
					$photo        = $fileName;
					$viewfilename = $fileName;

					if ($size == 1 || $size == 'small') {   // Format vignette
						// Find name of thumb file
						if ($use_mini_format) {
							$photo_vignette = basename(getImageFileNameForSize($dir . $fileName, '_mini'));
						} else {
							$photo_vignette = basename(getImageFileNameForSize($dir . $fileName, '_small'));
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
							if ($urladvanced) $return .= '<a class="clicked-photo-preview" href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}

						// Show image (width height=$maxHeight)
						$alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
						$alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
						if ($notitle) $alt = '';
						if ($usesharelink) {
                            if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
                                $return .= '<!-- Show thumb file -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=' . $modulepart . '&entity=' . $object->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            } else {
                                $return .= '<!-- Show original file -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" src="' . DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=' . $modulepart . '&entity=' . $object->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            }
						} else {
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
								$return .= '<!-- Show thumb file -->';
								$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .'"  src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
							}
						}

						if (empty($nolink)) $return .= '</a>';
						$return                     .= "\n";
						if ($showfilename) $return  .= '<br>' . $viewfilename;
						if ($showaction) {
							$return .= '<br>';
							if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
								$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=addthumb&amp;file=' . urlencode($pdir . $viewfilename) . '">' . img_picto($langs->trans('GenerateThumb'), 'refresh') . '&nbsp;&nbsp;</a>';
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
						$return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" data-object-id="' . $object->id . '">';
						if ($showfilename) {
							$return .= '<br>' . $viewfilename;
						}
					}

					if ($size == 'large' || $size == 'medium') {
						$relativefile = preg_replace('/^\//', '', $pdir . $photo);
						if (empty($nolink)) {
							$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
							if ($urladvanced) $return .= '<a class="clicked-photo-preview" href="' . $urladvanced . '">';
							else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
						}
						$widthName  = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_' . strtoupper($size);
						$heightName = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_' . strtoupper($size);
						$return .= '<img width="' . $conf->global->$widthName . '" height="' . $conf->global->$heightName . '" class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" data-object-id="' . $object->id . '">';
						if ($showfilename) {
                            $return .= '<br>' . $viewfilename;
                        }
					}
				}

				if ($show_favorite_button) {

					$favorite = (($object->$favorite_field == '' || $favoriteExists == 0) && $i == 0) ? 'favorite' : ($object->$favorite_field == $photo ? 'favorite' : '');
					$return .=
						'<div class="wpeo-button button-square-50 button-blue ' . $object->element . ' media-gallery-favorite ' . $favorite . '" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="' . ($favorite == 'favorite' ? 'fas' : 'far') . ' fa-star button-icon"></i>
						</div>';
				}
				if ($show_unlink_button) {
                    $confirmationParams = [
                        'picto'             => 'fontawesome_fa-unlink_fas_#e05353',
                        'color'             => '#e05353',
                        'confirmationTitle' => 'ConfirmUnlinkMedia',
                        'buttonParams'      => ['No' => 'button-blue marginrightonly confirmation-close', 'Yes' => 'button-red confirmation-delete']
                    ];
                    require __DIR__ . '/../core/tpl/utils/confirmation_view.tpl.php';
                    $return .=
						'<div class="wpeo-button button-square-50 button-grey ' . $object->element . ' media-gallery-unlink" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="fas fa-unlink button-icon"></i>
						</div>';
				}

				// ADDED_FOR_AI
				if (isModEnabled('ai') && !empty($moreParams['useAi']) &&
					(getDolGlobalString('AI_API_SERVICE') && getDolGlobalString('AI_API_' . dol_strtoupper(getDolGlobalString('AI_API_SERVICE')) . '_KEY') && getDolGlobalString('AI_API_' . dol_strtoupper(getDolGlobalString('AI_API_SERVICE')) . '_URL'))
				) {
					$return .=
						'<div class="wpeo-button button-square-50 button-blue ' . $object->element . ' media-gallery-ai" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="fas fa-magic button-icon"></i>
						</div>';
				}
				if ($showdiv) {
					$return .= "</div>\n";
				}

                // On continue ou on arrete de boucler ?
                if ($nbmax && $nbphoto >= $nbmax) break;

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
	} else {
        $return .= '<img  width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . $langs->trans('NoPhotoYet') . '">';
    }

	if (is_object($object)) {
		$object->nbphoto = $nbphoto;
	}
	return $return;
}

/**
 * Return file specified thumb name
 *
 * @param  string $filename  File name
 * @param  string $thumbType Thumb type (small, mini, large, medium)
 * @return string|int        Returns the full thumb filename, or -1 on error
 *
 */
function saturne_get_thumb_name(string $filename, string $thumbType = 'small', string $filePath = '')
{
    $fileName      = pathinfo($filename, PATHINFO_FILENAME);
    $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

    if (!empty($filePath)) {
        $filePathThumb = $filePath . '/thumbs';
        if (!dol_is_dir($filePathThumb)) {
            dol_mkdir($filePathThumb);
        }

        $files = dol_dir_list($filePathThumb, 'files', 0, '', '', 'name', SORT_DESC , 1);
        if (!empty($files)) {
            if (in_array($fileName . '_' . $thumbType . '.' . $fileExtension, array_column($files, 'name'))) {
                return $fileName . '_' . $thumbType . '.' . $fileExtension;
            }
        }

        $confWidth  = getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($thumbType));
        $confHeight = getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($thumbType));
        if (!$confWidth || !$confHeight) {
            return -1; //@todo throw error ?
        }

        return saturne_vignette($filePath . '/' . $filename, $confWidth, $confHeight, '_' . $thumbType);
    }

    return $fileName . '_' . $thumbType . '.' . $fileExtension;
}

/**
 * Return media linked elements count
 *
 * @param  string $moduleName Module name
 * @param  string $fileName       File name
 * @return string $output     Show media linked element count
 *
 */
function saturne_get_media_linked_elements(string $moduleName, string $fileName): string
{
    global $conf, $db, $langs;

    $moduleNameLowerCase    = dol_strtolower($moduleName);
    $regexFormattedFileName = '^' . preg_quote($fileName, '/');

    $dir                 = $conf->$moduleNameLowerCase->multidir_output[$conf->entity ?? 1];
    $fileArrays          = dol_dir_list($dir, 'files', 1, $regexFormattedFileName, '.odt|.pdf|barcode|_mini|_medium|_small|_large');
    $moduleClasses       = dol_dir_list(__DIR__ . '/../../' . $moduleNameLowerCase . '/class/', 'files', 1);

    $mediaLinkedElements = [];
    foreach ($fileArrays as $fileArray) {
        $element   = preg_split('/\//', $fileArray['relativename']);
        $classKey  = array_search($element[0] . '.class.php', array_column($moduleClasses, 'name'));
        $classPath = $moduleClasses[$classKey]['fullname'];

        require_once $classPath;

        $className = ucfirst($element[0]);
        $object    = new $className($db);

        $mediaLinkedElements[$fileArray['name']][$element[0]]['picto'] = $object->picto;
        $mediaLinkedElements[$fileArray['name']][$element[0]]['value']++;
    }

    $output = '<div class="linked-element">';
    foreach ($mediaLinkedElements as $mediaLinkedElement) {
        foreach ($mediaLinkedElement as $key => $linkedElement) {
            $output .= '<span class="paddingleft paddingright">' . img_picto($langs->trans(ucfirst($key)), $linkedElement['picto'], 'class="paddingright"');
            $output .= $linkedElement['value'];
            $output .= '</span>';
        }
    }
    $output .= '</div>';

    return $output;
}

/**
 * Render the Saturne Media Block (Photo + Audio) for external modules.
 *
 * Usage example:
 *   print saturne_render_media_block('saturne', 'test_medias', '', '', ['show_photo' => true, 'show_audio' => false]);
 *   print saturne_render_media_block('saturne', 'test_medias', '', '', ['show_photo' => false, 'show_audio' => true]);
 *
 * @param  string $moduleName  Module name (e.g. 'saturne', 'digiquali')
 * @param  string $subDir      Sub-directory under module dir_output (e.g. 'photos', 'test_medias')
 * @param  string $prefix      Optional prefix for file names and HTML element ids
 * @param  string $rightString The rights to check on API side (e.g. 'fraispro,creer')
 * @param  array  $options     Rendering options:
 *                               - show_photo   (bool, default true)  Show photo upload section
 *                               - show_audio   (bool, default true)  Show audio recording section
 *                               - show_gallery (bool, default true)  Show gallery of existing files
 * @return string              HTML block string
 */
function saturne_render_media_block(string $moduleName, string $subDir = '', string $prefix = '', string $rightString = '', array $options = []): string
{
    global $conf, $langs;

    $langs->load('medias@saturne');

    $showPhoto   = isset($options['show_photo'])   ? $options['show_photo']   : true;
    $showAudio   = isset($options['show_audio'])   ? $options['show_audio']   : true;
    $showGallery = isset($options['show_gallery']) ? $options['show_gallery'] : true;
    $showUpload  = isset($options['show_upload'])  ? $options['show_upload']  : true;

    $moduleNameLowerCase = dol_strtolower($moduleName);
    // Use only the last path segment as CSS class — subDir may contain slashes for deep paths
    $containerClass      = !empty($subDir) ? basename($subDir) : 'media_dyn';

    // Compute the storage directory path.
    $uploadDir = !empty($conf->$moduleNameLowerCase->dir_output)
        ? $conf->$moduleNameLowerCase->dir_output
        : $conf->ecm->dir_output . '/' . $moduleNameLowerCase;
    if (!empty($subDir)) {
        $uploadDir .= '/' . $subDir;
    }

    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    $idPrefix = $prefix ? dol_escape_htmltag($prefix) . '-' : '';
    $out      = '';

    if ($showPhoto) {
        $out .= '<div class="linked-medias medias ' . dol_escape_htmltag($containerClass) . '" id="' . $idPrefix . 'master-media-row-container-photo">';
        $out .= '  <div class="fast-upload-options" data-from-type="' . dol_escape_htmltag($moduleNameLowerCase) . '" data-from-subtype="' . dol_escape_htmltag($containerClass) . '" data-from-subdir="' . dol_escape_htmltag($subDir) . '" data-prefix="' . dol_escape_htmltag($prefix) . '" data-rights="' . dol_escape_htmltag($rightString) . '"></div>';
        $out .= '  <div class="saturne-media-upload-block" data-module="' . dol_escape_htmltag($moduleNameLowerCase) . '" data-subdir="' . dol_escape_htmltag($subDir) . '">';

        if ($showUpload) {
            $out .= '    <label for="' . $idPrefix . 'upload-media" class="saturne-upload-label">';
            $out .= '      <i class="fas fa-camera"></i>';
            $out .= '      <input type="file" id="' . $idPrefix . 'upload-media" class="saturne-photo-upload" name="userfile[]" accept="image/*" data-error-not-image="' . dol_escape_htmltag($langs->trans('ErrorFileNotAnImage')) . '" multiple>';
            $out .= '    </label>';
        }

        $out .= '    <div class="saturne-media-gallery">';

        if ($showGallery) {
            $photoFiles = dol_dir_list($uploadDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
            $imageFiles = [];
            foreach ($photoFiles as $file) {
                if (image_format_supported($file['name']) >= 0) {
                    $imageFiles[] = $file;
                }
            }
            $totalPhotos = count($imageFiles);

            if ($totalPhotos > 0) {
                $urls = [];
                foreach ($imageFiles as $file) {
                    if (!empty($conf->$moduleNameLowerCase->dir_output)) {
                        $urls[] = DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($moduleNameLowerCase) . '&entity=' . $conf->entity . '&file=' . urlencode($subDir . '/' . $file['name']);
                    } else {
                        $urls[] = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=' . $conf->entity . '&file=' . urlencode($moduleNameLowerCase . '/' . $subDir . '/' . $file['name']);
                    }
                }
                $urlsJson = htmlspecialchars(json_encode($urls), ENT_QUOTES, 'UTF-8');
                $firstImg = dol_escape_htmltag($urls[0]);

                $out .= '<div class="open-media-editor-as-gallery" data-json="' . $urlsJson . '">';
                $out .= '  <img src="' . $firstImg . '" />';
                $out .= '  <span class="saturne-media-count-badge">' . $totalPhotos . '</span>';
                $out .= '</div>';
            }
        }

        $out .= '    </div>';
        $out .= '  </div>';
        $out .= '</div>';
    }

    if ($showAudio) {
        $audioContainerClass = $containerClass . '_audio';

        $audioFiles = [];
        if (dol_is_dir($uploadDir)) {
            $filearray = dol_dir_list($uploadDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
            foreach ($filearray as $file) {
                if (preg_match('/\.(wav|mp3|ogg|m4a)$/i', $file['name'])) {
                    $audioFiles[] = $file;
                }
            }
        }

        $hasAudio      = count($audioFiles) > 0;
        $latestUrlHtml = '';
        if ($hasAudio) {
            $latestFile = $audioFiles[0];
            if (!empty($conf->$moduleNameLowerCase->dir_output)) {
                $latestUrlHtml = dol_escape_htmltag(DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($moduleNameLowerCase) . '&entity=' . $conf->entity . '&file=' . urlencode($subDir . '/' . $latestFile['name']));
            } else {
                $latestUrlHtml = dol_escape_htmltag(DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=' . $conf->entity . '&file=' . urlencode($moduleNameLowerCase . '/' . $subDir . '/' . $latestFile['name']));
            }
        }

        $disabled = $hasAudio ? '' : ' disabled';

        $out .= '<div class="linked-medias medias ' . dol_escape_htmltag($audioContainerClass) . '" id="' . $idPrefix . 'master-media-row-container-audio">';
        $out .= '  <div class="fast-upload-options" data-from-type="' . dol_escape_htmltag($moduleNameLowerCase) . '" data-from-subtype="' . dol_escape_htmltag($audioContainerClass) . '" data-from-subdir="' . dol_escape_htmltag($subDir) . '" data-prefix="' . dol_escape_htmltag($prefix) . '" data-rights="' . dol_escape_htmltag($rightString) . '"></div>';
        $out .= '  <div class="saturne-audio-controls">';

        $out .= '    <button type="button" class="saturne-media-btn saturne-start-recording" id="' . $idPrefix . 'start-recording">';
        $out .= '      <i class="fas fa-microphone"></i>';
        $out .= '    </button>';

        $out .= '    <div class="saturne-play-recording-wrapper">';
        $out .= '      <button type="button" class="saturne-media-btn saturne-play-recording" id="' . $idPrefix . 'play-recording" data-url="' . $latestUrlHtml . '"' . $disabled . '>';
        $out .= '        <i class="fas fa-play"></i>';
        $out .= '      </button>';
        if ($hasAudio) {
            $out .= '      <span class="saturne-audio-badge saturne-open-audio-library">' . count($audioFiles) . '</span>';
        }
        $out .= '      <button type="button" class="saturne-delete-recording" id="' . $idPrefix . 'delete-recording">';
        $out .= '        <i class="fas fa-times"></i>';
        $out .= '      </button>';
        $out .= '    </div>';

        $out .= '    <div id="' . $idPrefix . 'recording-indicator" class="blinking recording-indicator saturne-recording-indicator" data-label-upload="' . dol_escape_htmltag($langs->trans('UploadInProgress')) . '" data-label-recording="' . dol_escape_htmltag($langs->trans('Recording')) . '">' . $langs->trans('Recording') . '</div>';

        $out .= '  </div>';

        $out .= '</div>';

        if ($showGallery) {
            $blockId = $idPrefix . 'master-media-row-container-audio';
            $out .= '<div class="wpeo-modal saturne-audio-library-modal" id="' . $idPrefix . 'audio-library-modal"'
                . ' data-block-id="' . dol_escape_htmltag($blockId) . '"'
                . ' data-module="' . dol_escape_htmltag($moduleNameLowerCase) . '"'
                . ' data-subdir="' . dol_escape_htmltag($subDir) . '"'
                . '>';
            $out .= '  <div class="modal-container modal-flex">';
            $out .= '    <div class="modal-header">';
            $out .= '      <span class="modal-title">' . $langs->trans('AudioLibrary') . '</span>';
            $out .= '      <span class="modal-close"><i class="fas fa-times"></i></span>';
            $out .= '    </div>';
            $out .= '    <div class="modal-content">';

            if ($hasAudio) {
                $out .= '<div class="saturne-audio-library-content">';
                foreach ($audioFiles as $file) {
                    if (!empty($conf->$moduleNameLowerCase->dir_output)) {
                        $fUrl = DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($moduleNameLowerCase) . '&entity=' . $conf->entity . '&file=' . urlencode($subDir . '/' . $file['name']);
                    } else {
                        $fUrl = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=' . $conf->entity . '&file=' . urlencode($moduleNameLowerCase . '/' . $subDir . '/' . $file['name']);
                    }
                    $fNameHtml = dol_escape_htmltag($file['name']);
                    $fUrlHtml  = dol_escape_htmltag($fUrl);
                    $fileDate  = dol_print_date($file['date'], 'dayhour');

                    $out .= '<div class="saturne-audio-item">';
                    $out .= '  <audio controls src="' . $fUrlHtml . '"></audio>';
                    $out .= '  <span class="saturne-audio-date" title="' . $fNameHtml . '">' . $fileDate . '</span>';
                    $out .= '  <button type="button" class="saturne-delete-media-icon" data-filename="' . $fNameHtml . '"><i class="fas fa-trash-alt"></i></button>';
                    $out .= '</div>';
                }
                $out .= '</div>';
            } else {
                $out .= '<p class="saturne-no-audio">' . $langs->trans('NoAudioRecording') . '</p>';
            }

            $out .= '    </div>';
            $out .= '  </div>';
            $out .= '</div>';
        }
    }

    return $out;
}

/**
 * Token d'upload isolé par utilisateur et par contexte (Pour Développeurs)
 *
 * Génère ou récupère un token unique lié à la session PHP et à un contexte
 * métier. Ce token sert de sous-dossier d'upload pour isoler les fichiers
 * de chaque utilisateur — même si plusieurs personnes ouvrent la même page
 * simultanément, leurs uploads ne se mélangent pas. Le token survit au F5.
 *
 * Structure des dossiers générée :
 *   uploads/medias/{token}/   ← propre à l'utilisateur + contexte
 *
 * Usage complet (formulaire → sauvegarde) :
 *   // 1. Dans la vue — générer le token et passer le sous-dossier au bloc média
 *   $context = $object->element . '_' . $object->id . '_photos';
 *   $subDir  = 'photos/' . saturne_get_upload_token($context);
 *   print saturne_render_media_block('mymodule', $subDir, '', 'mymodule,write');
 *
 *   // 2. Après F5 — le même token est retrouvé automatiquement depuis $_SESSION
 *
 *   // 3. À la sauvegarde — lire les fichiers puis invalider le token
 *   foreach (saturne_get_media_files('mymodule', $subDir) as $file) {
 *       dol_move($file['fullname'], $finalDir . '/' . $file['name']);
 *   }
 *   saturne_invalidate_upload_token($context, 'mymodule', 'photos');
 *
 * @param  string $context Identifiant unique du contexte d'upload, ex: 'inspection_42_photos'
 * @return string          Token hexadécimal de 64 caractères, stable pour la durée de la session
 */
function saturne_get_upload_token(string $context): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['saturne_upload_tokens'][$context])) {
        $_SESSION['saturne_upload_tokens'][$context] = bin2hex(random_bytes(32));
    }

    return $_SESSION['saturne_upload_tokens'][$context];
}

/**
 * Suppression du token d'upload et nettoyage du dossier temporaire (Pour Développeurs)
 *
 * À appeler après avoir déplacé les fichiers uploadés vers leur emplacement
 * définitif. Supprime le token de la session et efface le dossier temporaire
 * associé pour éviter l'accumulation de fichiers orphelins sur le serveur.
 *
 * Usage exemple :
 *   // Après sauvegarde de l'objet, nettoyer le token et le dossier temporaire
 *   saturne_invalidate_upload_token('inspection_42_photos', 'mymodule', 'photos');
 *
 *   // Sans suppression de dossier (token seul) :
 *   saturne_invalidate_upload_token('inspection_42_photos');
 *
 * @param  string $context      Même identifiant de contexte que celui passé à saturne_get_upload_token()
 * @param  string $moduleName   Nom du module, utilisé pour résoudre le chemin de base des uploads
 * @param  string $subDirPrefix Préfixe du sous-dossier avant le token, ex: 'photos', 'medias'
 * @return void
 */
function saturne_invalidate_upload_token(string $context, string $moduleName = '', string $subDirPrefix = ''): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['saturne_upload_tokens'][$context])) {
        return;
    }

    $token = $_SESSION['saturne_upload_tokens'][$context];

    if (!empty($moduleName) && !empty($subDirPrefix)) {
        global $conf;

        $moduleNameLowerCase = dol_strtolower($moduleName);
        $uploadBase          = !empty($conf->$moduleNameLowerCase->dir_output)
            ? $conf->$moduleNameLowerCase->dir_output
            : $conf->ecm->dir_output . '/' . $moduleNameLowerCase;

        $tokenDir = $uploadBase . '/' . $subDirPrefix . '/' . $token;
        if (dol_is_dir($tokenDir)) {
            dol_delete_dir_recursive($tokenDir);
        }
    }

    unset($_SESSION['saturne_upload_tokens'][$context]);
}

/**
 * Récupération backend des fichiers uploadés — images et/ou audios (Pour Développeurs)
 *
 * Retourne la liste des fichiers présents dans un dossier d'upload sans aucun
 * rendu HTML. Partage la même signature que saturne_render_media_block() pour
 * pouvoir être appelé avec les mêmes arguments. Chaque entrée du tableau retourné
 * contient : name, fullname, path, url, date, size, type ('image'|'audio').
 *
 * Usage exemple :
 *   // Récupérer tous les médias (images + audios)
 *   $files = saturne_get_media_files('mymodule', 'photos');
 *
 *   // Images uniquement
 *   $files = saturne_get_media_files('mymodule', 'photos', '', '', ['type' => 'image']);
 *
 *   // Audios uniquement, du plus ancien au plus récent
 *   $files = saturne_get_media_files('mymodule', 'photos', '', '', ['type' => 'audio', 'sort_order' => 'asc']);
 *
 * @param  string $moduleName   Module name (e.g. 'saturne', 'digiquali')
 * @param  string $subDir       Sub-directory under module dir_output (e.g. 'photos', 'test_medias')
 * @param  string $prefix       Unused — kept for signature parity with saturne_render_media_block()
 * @param  string $rightString  Unused — kept for signature parity with saturne_render_media_block()
 * @param  array  $options      Retrieval options:
 *                                - type        (string, default '')      Filter: 'image', 'audio', or '' for both
 *                                - sort_field  (string, default 'date')  Sort field among dol_dir_list keys
 *                                - sort_order  (string, default 'desc')  'asc' or 'desc'
 * @return array<int, array{name: string, fullname: string, path: string, url: string, date: int, size: int, type: string}>
 */
function saturne_get_media_files(string $moduleName, string $subDir = '', string $prefix = '', string $rightString = '', array $options = []): array
{
    global $conf;

    // Kept for signature parity with saturne_render_media_block(), not used here.
    unset($prefix, $rightString);

    $type      = $options['type']       ?? '';
    $sortField = $options['sort_field'] ?? 'date';
    $sortOrder = $options['sort_order'] ?? 'desc';

    $moduleNameLowerCase = dol_strtolower($moduleName);

    $uploadDir = !empty($conf->$moduleNameLowerCase->dir_output)
        ? $conf->$moduleNameLowerCase->dir_output
        : $conf->ecm->dir_output . '/' . $moduleNameLowerCase;

    if (!empty($subDir)) {
        $uploadDir .= '/' . $subDir;
    }

    if (!dol_is_dir($uploadDir)) {
        return [];
    }

    $rawFiles = dol_dir_list($uploadDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortField, (strtolower($sortOrder) == 'desc' ? SORT_DESC : SORT_ASC));

    $result = [];

    foreach ($rawFiles as $file) {
        $fileName  = $file['name'];
        $mediaType = '';

        if (image_format_supported($fileName) >= 0) {
            $mediaType = 'image';
        } elseif (preg_match('/\.(wav|mp3|ogg|m4a)$/i', $fileName)) {
            $mediaType = 'audio';
        }

        if (empty($mediaType)) {
            continue;
        }

        if (!empty($type) && $mediaType !== $type) {
            continue;
        }

        if (!empty($conf->$moduleNameLowerCase->dir_output)) {
            $url = DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($moduleNameLowerCase) . '&entity=' . $conf->entity . '&file=' . urlencode($subDir . '/' . $fileName);
        } else {
            $url = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=' . $conf->entity . '&file=' . urlencode($moduleNameLowerCase . '/' . $subDir . '/' . $fileName);
        }

        $result[] = [
            'name'     => $fileName,
            'fullname' => $file['fullname'],
            'path'     => $file['path'],
            'url'      => $url,
            'date'     => (int) $file['date'],
            'size'     => (int) $file['size'],
            'type'     => $mediaType,
        ];
    }

    return $result;
}

function saturne_show_media_buttons(): string
{
    global $object, $onPhone;

    if (empty($object) || !is_object($object)) {
        return '';
    }

    $fastUploadImprovement = getDolGlobalInt('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT') ? '-improvement' : '';

    // Define the output HTML
    $output = <<<HTML
    <div class="add-medias">
        <input type="hidden" class="fast-upload-options" data-from-subtype="photo" data-from-subdir="photos" />
        <input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?>" />
        <label>
            <button class="wpeo-button button-square-40">
                <span class="fas fa-camera" aria-hidden="true"></span><span class="button-add fas fa-plus-circle" aria-hidden="true"></span>
            </button>
            <input type="file" class="fast-upload{$fastUploadImprovement}" id="fast-upload-photo-default" name="userfile[]" capture="environment" accept="image/*" hidden multiple />
        </label>
        <label>
            <button class="wpeo-button button-square-40 modal-open">
                <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id ?>" data-from-type="control" data-from-subtype="photo" data-from-subdir="photos" />
                <span class="fas fa-folder-open"></span><span class="fas fa-plus-circle button-add"></span>
            </button>
        </label>
    </div>
    HTML;

    return $output;
}
