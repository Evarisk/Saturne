<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/medias/object/medias_gallery_modal.tpl.php
 * \ingroup saturne
 * \brief   Saturne medias gallery modal
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Global variables definitions
global $action, $conf, $db, $langs, $moduleName, $moduleNameLowerCase, $moduleNameUpperCase, $subaction, $user;

// Initialize technical objects
$ecmdir  = new EcmDirectory($db);
$ecmfile = new EcmFiles($db);

// Initialize view objects
$form = new Form($db);

if ( ! $error && $subaction == "uploadPhoto" && ! empty($conf->global->MAIN_UPLOAD_DOC)) {

	// Define relativepath and upload_dir
	$relativepath                                             = $moduleNameLowerCase . '/medias';
	$uploadDir                                                = $conf->ecm->dir_output . '/' . $relativepath;

	if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
	else $userfiles                                           = array($_FILES['userfile']['tmp_name']);

	foreach ($userfiles as $key => $userfile) {
		$error = 0;
		if (empty($_FILES['userfile']['tmp_name'][$key])) {
			$error++;
			if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
				setEventMessages($langs->transnoentitiesnoconv('ErrorThisFileSizeTooLarge', $_FILES['userfile']['name'][$key]), null, 'errors');
				$submitFileErrorText = array('message' => $langs->transnoentitiesnoconv('ErrorThisFileSizeTooLarge', $_FILES['userfile']['name'][$key]), 'code' => '1337');
			} else {
				setEventMessages($langs->transnoentitiesnoconv("ErrorThisFileSizeTooLarge", $_FILES['userfile']['name'][$key], $langs->transnoentitiesnoconv("File")), null, 'errors');
				$submitFileErrorText = array('message' => $langs->transnoentitiesnoconv('ErrorThisFileSizeTooLarge', $_FILES['userfile']['name'][$key]), 'code' => '1337');
			}
		}

		if ( ! $error) {
			$generatethumbs = 1;
			$res = dol_add_file_process($uploadDir, 0, 1, 'userfile', '', null, '', $generatethumbs);
			if ($res > 0) {
                $confWidthMedium  = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_MEDIUM';
                $confHeightMedium = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_MEDIUM';
                $confWidthLarge   = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_LARGE';
                $confHeightLarge  = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_LARGE';

                // Create thumbs
				$imgThumbLarge  = vignette($uploadDir . '/' . $_FILES['userfile']['name'][$key], $conf->global->$confWidthLarge, $conf->global->$confHeightLarge, '_large');
				$imgThumbMedium = vignette($uploadDir . '/' . $_FILES['userfile']['name'][$key], $conf->global->$confWidthMedium, $conf->global->$confHeightMedium, '_medium');
				$result         = $ecmdir->changeNbOfFiles('+');
			} else {
				setEventMessages($langs->transnoentitiesnoconv("ErrorThisFileExists", $_FILES['userfile']['name'][$key], $langs->transnoentitiesnoconv("File")), null, 'errors');
				$submitFileErrorText = array('message' => $langs->transnoentities('ErrorThisFileExists', $_FILES['userfile']['name'][$key]), 'code' => '1337');
			}
		}
	}
}

if ( ! $error && $subaction == "addFiles") {
	global $user;

	$data = json_decode(file_get_contents('php://input'), true);

	$filenames     = $data['filenames'];
	$objectId      = $data['objectId'];
	$objectType    = $data['objectType'];
	$objectSubtype = $data['objectSubtype'];
	$objectSubdir  = $data['objectSubdir'];

    if (strstr($objectType, '_')) {
        $className = preg_replace('/_/', '', $objectType);
    } else {
        $className = $objectType;
    }

    $object = new $className($db);
    $object->fetch($objectId);

	$modObjectName = strtoupper($moduleNameLowerCase) . '_' . strtoupper($className) . '_ADDON';

    $numberingModuleName = [
        $object->element => $conf->global->$modObjectName,
    ];

    list($modObject) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

	if (dol_strlen($object->ref) > 0) {
		$pathToObjectPhoto = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType .'/' . $object->ref . '/' . $objectSubdir;
	} else {
		$pathToObjectPhoto = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType .'/tmp/' . $modObject->prefix . '0/' . $objectSubdir ;
	}

	if (preg_match('/vVv/', $filenames)) {
		$filenames = preg_split('/vVv/', $filenames);
		array_pop($filenames);
	} else {
		$filenames = array($filenames);
	}

	if ( ! (empty($filenames))) {
		if ( ! is_dir($conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType . '/tmp/')) {
			dol_mkdir($conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType . '/tmp/');
		}

		if ( ! is_dir($conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType . '/' . (dol_strlen($object->ref) > 0 ? $object->ref : 'tmp/' . $modObject->prefix . '0/') )) {
			dol_mkdir($conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType . '/' . (dol_strlen($object->ref) > 0 ? $object->ref : 'tmp/' . $modObject->prefix . '0/'));
		}

		foreach ($filenames as $filename) {
			$entity = ($conf->entity > 1) ? '/' . $conf->entity : '';
			$filename = dol_sanitizeFileName($filename);
			if (empty($object->$objectSubtype)) {
				$object->$objectSubtype = $filename;
			}
			if (is_file($conf->ecm->multidir_output[$conf->entity] . '/'. $moduleNameLowerCase .'/medias/' . $filename)) {
				$pathToECMPhoto = $conf->ecm->multidir_output[$conf->entity] . '/'. $moduleNameLowerCase .'/medias/' . $filename;

				if ( ! is_dir($pathToObjectPhoto)) {
					mkdir($pathToObjectPhoto, 0777, true);
				}

				if (file_exists($pathToECMPhoto)) {
					copy($pathToECMPhoto, $pathToObjectPhoto . '/' . $filename);
					$ecmfile->fetch(0,'',($conf->entity > 1 ? $conf->entity . '/' : ''). 'ecm/'. $moduleNameLowerCase .'/medias/' . $filename);
					$date      = dol_print_date(dol_now(),'dayxcard');
					$extension = pathinfo($filename, PATHINFO_EXTENSION);

					$destfull = $pathToObjectPhoto . '/' . $filename;

					$confWidthMini    = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_MINI';
					$confHeightMini   = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_MINI';
					$confWidthSmall   = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_SMALL';
					$confHeightSmall  = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_SMALL';
					$confWidthMedium  = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_MEDIUM';
					$confHeightMedium = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_MEDIUM';
					$confWidthLarge   = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_LARGE';
					$confHeightLarge  = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_LARGE';

					// Create thumbs
					$imgThumbMini   = vignette($destfull, $conf->global->$confWidthMini, $conf->global->$confHeightMini, '_mini');
					$imgThumbSmall  = vignette($destfull, $conf->global->$confWidthSmall, $conf->global->$confHeightSmall, '_small');
					$imgThumbMedium = vignette($destfull, $conf->global->$confWidthMedium, $conf->global->$confHeightMedium, '_medium');
					$imgThumbLarge  = vignette($destfull, $conf->global->$confWidthLarge, $conf->global->$confHeightLarge, '_large');
					// Create mini thumbs for image (Ratio is near 16/9)
				}
			}
		}
        if ($objectId != 0){
            $object->update($user);
        }
	}
}

if ( ! $error && $subaction == "unlinkFile") {
	global $user;

	$data = json_decode(file_get_contents('php://input'), true);

	$filePath      = $data['filepath'];
	$fileName      = $data['filename'];
	$objectId      = $data['objectId'];
	$objectType    = $data['objectType'];
	$objectSubtype = $data['objectSubtype'];
	$objectSubdir  = $data['objectSubdir'];

	$fullPath = $filePath . '/' . $fileName;

    if (strstr($objectType, '_')) {
        $className = preg_replace('/_/', '', $objectType);
    } else {
        $className = $objectType;
    }

	if (is_file($fullPath)) {
		unlink($fullPath);
	}

	$sizesArray = [
		'mini',
		'small',
		'medium',
		'large'
	];

	foreach($sizesArray as $size) {
		$thumbName = $filePath . '/thumbs/' . saturne_get_thumb_name($fileName, $size);
		if (is_file($thumbName)) {
			unlink($thumbName);
		};
	}

	if ($objectId > 0) {
		$object = new $className($db);
		$object->fetch($objectId);

		if (property_exists($object, $objectSubtype)) {

			if ($object->$objectSubtype == $fileName) {
				$pathPhotos = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/'. $objectType .'/'. $object->ref . '/' . (dol_strlen($objectSubdir) > 0 ? $objectSubdir . '/' : '');
				$fileArray  = dol_dir_list($pathPhotos, 'files', 0, '', $fileName);

				if (count($fileArray) > 0) {
					$firstFileName = array_shift($fileArray);
					$object->$objectSubtype = $firstFileName['name'];
				} else {
					$object->$objectSubtype = '';
				}

				$object->update($user, true);
			}
		}
	}
}

if ( ! $error && $subaction == "addToFavorite") {
	global $user;

	$data = json_decode(file_get_contents('php://input'), true);

	$fileName      = $data['filename'];
	$objectId      = $data['objectId'];
	$objectType    = $data['objectType'];
	$objectSubtype = $data['objectSubtype'];
	$objectSubdir  = $data['objectSubdir'];

    if (strstr($objectType, '_')) {
        $className = preg_replace('/_/', '', $objectType);
    } else {
        $className = $objectType;
    }

	if ($objectId > 0) {
		$object = new $className($db);
		$object->fetch($objectId);
		if (property_exists($object, $objectSubtype)) {
			$object->$objectSubtype = $fileName;
			$object->update($user, true);
		}
	}
}

if ( ! $error && $subaction == "pagination") {
	$data = json_decode(file_get_contents('php://input'), true);

	$offset       = $data['offset'];
	$pagesCounter = $data['pagesCounter'];

	$loadedPageArray = saturne_load_pagination($pagesCounter, [], $offset);
}

if ( ! $error && $subaction == "toggleTodayMedias") {
    $toggleValue = GETPOST('toggle_today_medias');

    $tabparam['SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS'] = $toggleValue;

    dol_set_user_param($db, $conf,$user, $tabparam);
}

if ( ! $error && $subaction == "toggleUnlinkedMedias") {
    $toggleValue = GETPOST('toggle_unlinked_medias');

    $tabparam['SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS'] = $toggleValue;

    dol_set_user_param($db, $conf,$user, $tabparam);
}

if (is_array($submitFileErrorText)) {
	print '<input class="error-medias" value="'. htmlspecialchars(json_encode($submitFileErrorText)) .'">';
}
?>
<!-- START MEDIA GALLERY MODAL -->
<div class="wpeo-modal modal-photo" id="media_gallery" data-id="<?php echo $object->id ?: 0?>">
	<div class="modal-container wpeo-modal-event">
		<!-- Modal-Header -->
		<div class="modal-header">
			<h2 class="modal-title"><?php echo $langs->trans('ModalAddPhoto')?></h2>
			<div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
		</div>
		<!-- Modal-Content -->
		<div class="modal-content" id="#modalMediaGalleryContent">
			<div class="messageSuccessSendPhoto notice hidden">
				<div class="wpeo-notice notice-success send-photo-success-notice">
					<div class="notice-content">
						<div class="notice-title"><?php echo $langs->trans('PhotoWellSent') ?></div>
					</div>
					<div class="notice-close"><i class="fas fa-times"></i></div>
				</div>
			</div>
			<div class="messageErrorSendPhoto notice hidden">
				<div class="wpeo-notice notice-error send-photo-error-notice">
					<div class="notice-content">
						<div class="notice-title"><?php echo $langs->trans('PhotoNotSent') ?></div>
						<div class="notice-subtitle"></div>
					</div>
					<div class="notice-close"><i class="fas fa-times"></i></div>
				</div>
			</div>
			<div class="wpeo-gridlayout grid-3">
				<div class="modal-add-media">
					<?php
					print '<input type="hidden" name="token" value="'.newToken().'">';
					if (( ! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || ! empty($section)) {
						$sectiondir = GETPOST('file', 'alpha') ? GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');
						print '<!-- Start form to attach new file in '. $moduleNameLowerCase .'_photo_view.tpl.php sectionid=' . $section . ' sectiondir=' . $sectiondir . ' -->' . "\n";
						include_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
						print '<strong>' . $langs->trans('AddFile') . '</strong>'
						?>
						<input type="file" id="add_media_to_gallery" class="flat minwidth400 maxwidth200onsmartphone" name="userfile[]" multiple accept>
					<?php } else print '&nbsp;'; ?>
					<div class="underbanner clearboth"></div>
				</div>
				<div class="form-element">
					<span class="form-label"><strong><?php print $langs->trans('SearchFile') ?></strong></span>
					<div class="form-field-container">
						<div class="wpeo-autocomplete">
							<label class="autocomplete-label" for="media-gallery-search">
								<i class="autocomplete-icon-before fas fa-search"></i>
								<input id="search_in_gallery" placeholder="<?php echo $langs->trans('Search') . '...' ?>" class="autocomplete-search-input" type="text" />
							</label>
						</div>
					</div>
				</div>
                <div>
                    <div>
                        <?php print img_picto($langs->trans('Link'), 'link') . ' ' . $form->textwithpicto($langs->trans('UnlinkedMedias'), $langs->trans('ShowOnlyUnlinkedMedias'));
                        if ($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) {
                            print '<span id="del_unlinked_medias" value="0" class="valignmiddle linkobject toggle-unlinked-medias ' . (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) ? '' : 'hideobject') . '">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</span>';
                        } else {
                            print '<span id="set_unlinked_medias" value="1" class="valignmiddle linkobject toggle-unlinked-medias ' . (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) ? 'hideobject' : '') . '">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</span>';
                        } ?>
                    </div>
                    <div>
                        <?php print img_picto($langs->trans('Calendar'), 'calendar') . ' ' . $form->textwithpicto($langs->trans('Today'), $langs->trans('ShowOnlyMediasAddedToday'));
                        if ($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) {
                            print '<span id="del_today_medias" value="0" class="valignmiddle linkobject toggle-today-medias ' . (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) ? '' : 'hideobject') . '">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</span>';
                        } else {
                            print '<span id="set_today_medias" value="1" class="valignmiddle linkobject toggle-today-medias ' . (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) ? 'hideobject' : '') . '">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</span>';
                        } ?>
                    </div>
                </div>
			</div>
			<div id="progressBarContainer" style="display: none;">
				<div id="progressBar"></div>
			</div>
			<div class="ecm-photo-list-content">
				<?php
				$relativepath = $moduleNameLowerCase . '/medias/thumbs';
				print saturne_show_medias($moduleNameLowerCase, 'ecm', $conf->ecm->multidir_output[$conf->entity] . '/'. $moduleNameLowerCase .'/medias', ($conf->browser->layout == 'phone' ? 'mini' : 'small'), 80, 80, (!empty($offset) ? $offset : 1));
				?>
			</div>
		</div>
		<!-- Modal-Footer -->
		<div class="modal-footer">
			<?php
			$filearray                    = dol_dir_list($conf->ecm->multidir_output[$conf->entity] . '/'. $moduleNameLowerCase .'/medias/', "files", 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
			$moduleImageNumberPerPageConf = strtoupper($moduleNameLowerCase) . '_DISPLAY_NUMBER_MEDIA_GALLERY';
            if ($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS == 1) {
                $yesterdayTimeStamp = dol_time_plus_duree(dol_now(), -1, 'd');
                $filearray = array_filter($filearray, function($file) use ($yesterdayTimeStamp) {
                    return $file['date'] > $yesterdayTimeStamp;
                });
            }
            if ($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS == 1) {
                $filearray = array_filter($filearray, function($file) use ($conf, $moduleNameLowerCase) {
                    $regexFormattedFileName = preg_quote($file['name'], '/');
                    $fileArrays             = dol_dir_list($conf->$moduleNameLowerCase->multidir_output[$conf->entity ?? 1], 'files', 1, $regexFormattedFileName, '.odt|.pdf|barcode|_mini|_medium|_small|_large');

                    return count($fileArrays) == 0;
                });
           }
            $allMediasNumber              = count($filearray);
			$pagesCounter                 = $conf->global->$moduleImageNumberPerPageConf ? ceil($allMediasNumber/($conf->global->$moduleImageNumberPerPageConf ?: 1)) : 1;
			$page_array                   = saturne_load_pagination($pagesCounter, $loadedPageArray, $offset);

			print saturne_show_pagination($pagesCounter, $page_array, $offset);
			?>
			<div class="save-photo wpeo-button button-blue button-disable" value="">
				<span><?php echo $langs->trans('Add'); ?></span>
			</div>
		</div>
	</div>
</div>
<!-- END MEDIA GALLERY MODAL -->
