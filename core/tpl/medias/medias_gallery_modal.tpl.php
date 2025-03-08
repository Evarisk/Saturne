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
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Global variables definitions
global $action, $conf, $db, $langs, $moduleName, $moduleNameLowerCase, $moduleNameUpperCase, $subaction, $user;

// Initialize technical objects
$ecmdir  = new EcmDirectory($db);
$ecmfile = new EcmFiles($db);

// Initialize view objects
$form = new Form($db);

// Array for the sizes of thumbs
$mediaSizes = ['mini', 'small', 'medium', 'large'];

if (!(isset($error) && $error) && $subaction == 'uploadPhoto' && ! empty($conf->global->MAIN_UPLOAD_DOC)) {

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

if ($subaction == 'add_img') {
    global $object;

    $data = json_decode(file_get_contents('php://input'), true);

    $encodedImage = explode(',', $data['img'])[1];
    $decodedImage = base64_decode($encodedImage);
    $pathToECMImg = $conf->ecm->dir_output . '/' . $moduleNameLowerCase . '/medias';
    $fileName     = dol_print_date(dol_now(), 'dayhourlog') . '_img.png';

    if (!dol_is_dir($pathToECMImg)) {
        dol_mkdir($pathToECMImg);
    }

    file_put_contents($pathToECMImg . '/' . $fileName, $decodedImage);
    addFileIntoDatabaseIndex($pathToECMImg, $fileName, $pathToECMImg . '/' . $fileName);

    if (dol_strlen($object->ref) > 0) {
        $pathToObjectImg = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/' . $data['objectSubdir'];
        if (empty($object->{$data['objectSubType']})) {
            $object->setValueFrom($data['objectSubType'], $fileName, '', '', 'text', '', $user);
        }
    } else {
        $modObjectName       = dol_strtoupper($moduleNameLowerCase) . '_' . dol_strtoupper($object->element) . '_ADDON';
        $numberingModuleName = [$object->element => getDolGlobalString($modObjectName)];
        if ($numberingModuleName[$data['objectType']] != '') {
            list($modObject) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);
            $pathToObjectImg = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $object->element . '/tmp/' . $modObject->prefix . '0/' . $data['objectSubdir'];
        } else {
            $pathToObjectImg = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $data['objectType'] . '/tmp/' . $data['objectSubdir'] . '/';
        }
    }

    if (!dol_is_dir($pathToObjectImg)) {
        dol_mkdir($pathToObjectImg);
    }

    dol_copy($pathToECMImg . '/' . $fileName, $pathToObjectImg . '/' . $fileName);

    // Create thumbs
    foreach($mediaSizes as $size) {
        $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
        $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
        vignette($pathToECMImg . '/' . $fileName, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
        vignette($pathToObjectImg . '/' . $fileName, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
    }
}

if ($subaction == 'addFiles') {
    $data = json_decode(file_get_contents('php://input'), true);

    $objectType = $data['objectType'];
    $objectId   = $data['objectId'];

    $className = $objectType;
    $object    = new $className($db);
    $object->fetch($objectId);

    $pathToECMImg = $conf->ecm->multidir_output[$conf->entity] . '/'. $moduleNameLowerCase .'/medias';
    if (!dol_is_dir($pathToECMImg)) {
        dol_mkdir($pathToECMImg);
    }

    if (dol_strlen($object->ref) > 0) {
        $pathToObjectImg = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $objectType . '/' . $object->ref . '/' . $data['objectSubdir'];
    } else {
        $modObjectName       = dol_strtoupper($moduleNameLowerCase) . '_' . dol_strtoupper($objectType) . '_ADDON';
        $numberingModuleName = [$objectType => getDolGlobalString($modObjectName)];
        if ($numberingModuleName[$objectType] != '') {
            list($modObject) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);
            $pathToObjectImg = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $objectType . '/tmp/' . $modObject->prefix . '0/' . $data['objectSubdir'];
        } else {
            $pathToObjectImg = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $objectType . '/tmp/' . $data['objectSubdir'] . '/';
        }
    }

    if (!dol_is_dir($pathToObjectImg)) {
        dol_mkdir($pathToObjectImg);
    }

    if (strpos($data['filenames'], 'vVv') !== false) {
        $fileNames = explode('vVv', $data['filenames']);
        array_pop($fileNames);
    } else {
        $fileNames = [$data['filenames']];
    }

    if (!empty($fileNames)) {
        foreach ($fileNames as $fileName) {
            $fileName = dol_sanitizeFileName($fileName);
            if (empty($object->{$data['objectSubtype']})) {
                $object->{$data['objectSubtype']} = $fileName;
            }

            dol_copy($pathToECMImg . '/' . $fileName, $pathToObjectImg . '/' . $fileName);

            // Create thumbs
            foreach($mediaSizes as $size) {
                $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
                $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
                vignette($pathToObjectImg . '/' . $fileName, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
            }
        }
        if ($objectId > 0) {
            $object->setValueFrom($data['objectSubtype'], $object->{$data['objectSubtype']}, '', '', 'text', '', $user);
        }
    }
}

if ($subaction == 'delete_files') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (strpos($data['filenames'], 'vVv') !== false) {
        $fileNames = explode('vVv', $data['filenames']);
        array_pop($fileNames);
    } else {
        $fileNames = [$data['filenames']];
    }

    if (!empty($fileNames)) {
        foreach ($fileNames as $fileName) {
            $fileName       = dol_sanitizeFileName($fileName);
            $pathToECMPhoto = $conf->ecm->multidir_output[$conf->entity] . '/' . $moduleNameLowerCase . '/medias/' . $fileName;
            if (is_file($pathToECMPhoto)) {
                foreach($mediaSizes as $size) {
                    $thumbName = $conf->ecm->multidir_output[$conf->entity] . '/' . $moduleNameLowerCase . '/medias/thumbs/' . saturne_get_thumb_name($fileName, $size);
                    if (is_file($thumbName)) {
                        unlink($thumbName);
                    }
                }
                unlink($pathToECMPhoto);
            }
        }
    }
}

if ($subaction == 'unlinkFile') {
    $data = json_decode(file_get_contents('php://input'), true);

    $fullPath = $data['filepath'] . '/' . $data['filename'];
    if (is_file($fullPath)) {
        unlink($fullPath);

        foreach($mediaSizes as $size) {
            $thumbName = $data['filepath'] . '/thumbs/' . saturne_get_thumb_name($data['filename'], $size);
            if (is_file($thumbName)) {
                unlink($thumbName);
            }
        }
    }

    if ($data['objectId'] > 0) {
        $className = $data['objectType'];
        $object    = new $className($db);
        $object->fetch($data['objectId']);

        if (property_exists($object, $data['objectSubtype'])) {
            if ($object->{$data['objectSubtype']} == $data['filename']) {
                $fileArray = dol_dir_list($data['filepath'], 'files');
                if (count($fileArray) > 0) {
                    $firstFileName = array_shift($fileArray);
                    $object->{$data['objectSubtype']} = $firstFileName['name'];
                } else {
                    $object->{$data['objectSubtype']} = '';
                }
                $object->setValueFrom($data['objectSubtype'], $object->{$data['objectSubtype']}, '', '', 'text', '', $user);
            }
        }
    }
}

if ($subaction == 'addToFavorite') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data['objectId'] > 0) {
        $className = $data['objectType'];
        $object    = new $className($db);
        $object->fetch($data['objectId']);

        if (property_exists($object, $data['objectSubtype'])) {
            $object->{$data['objectSubtype']} = $data['filename'];
            $object->setValueFrom($data['objectSubtype'], $object->{$data['objectSubtype']}, '', '', 'text', '', $user);
        }
    }
}

if (!(isset($error) && $error) && $subaction == 'pagination') {
	$data = json_decode(file_get_contents('php://input'), true);

	$offset       = $data['offset'];
	$pagesCounter = $data['pagesCounter'];

	$loadedPageArray = saturne_load_pagination($pagesCounter, [], $offset);
}

if (!(isset($error) && $error) && $subaction == 'toggleTodayMedias') {
    $toggleValue = GETPOST('toggle_today_medias');

    $tabparam['SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS'] = $toggleValue;

    dol_set_user_param($db, $conf,$user, $tabparam);
}

if (!(isset($error) && $error) && $subaction == 'toggleUnlinkedMedias') {
    $toggleValue = GETPOST('toggle_unlinked_medias');

    $tabparam['SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS'] = $toggleValue;

    dol_set_user_param($db, $conf,$user, $tabparam);
}

if (!(isset($error) && $error) && $subaction == 'regenerate_thumbs') {
    $data = json_decode(file_get_contents('php://input'), true);

    foreach($mediaSizes as $size) {
        $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
        $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
        vignette($data['fullname'], $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
    }
}

if (!empty($submitFileErrorText) && is_array($submitFileErrorText)) {
	print '<input class="error-medias" value="'. htmlspecialchars(json_encode($submitFileErrorText)) .'">';
}

require_once __DIR__ . '/media_editor_modal.tpl.php'; ?>

<!-- START MEDIA GALLERY MODAL -->
<div class="wpeo-modal modal-photo" id="media_gallery" data-id="<?php echo (isset($object) && $object) ? $object->id : 0 ?>">
	<div class="modal-container wpeo-modal-event">
		<!-- Modal-Header -->
		<div class="modal-header">
			<h2 class="modal-title"><?php echo $langs->trans('MediaGallery')?></h2>
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
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <strong><?php echo $langs->trans('AddFile'); ?></strong>
                    <input type="file" id="add_media_to_gallery" class="flat minwidth400 maxwidth200onsmartphone" name="userfile[]" multiple accept>
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
                        if (isset($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) && $user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) {
                            print '<span id="del_unlinked_medias" value="0" class="valignmiddle linkobject toggle-unlinked-medias ' . (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) ? '' : 'hideobject') . '">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</span>';
                        } else {
                            print '<span id="set_unlinked_medias" value="1" class="valignmiddle linkobject toggle-unlinked-medias ' . (!empty($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) ? 'hideobject' : '') . '">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</span>';
                        } ?>
                    </div>
                    <div>
                        <?php print img_picto($langs->trans('Calendar'), 'calendar') . ' ' . $form->textwithpicto($langs->trans('Today'), $langs->trans('ShowOnlyMediasAddedToday'));
                        if (isset($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) && $user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) {
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
            if (isset($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) && $user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS == 1) {
                $yesterdayTimeStamp = dol_time_plus_duree(dol_now(), -1, 'd');
                $filearray = array_filter($filearray, function($file) use ($yesterdayTimeStamp) {
                    return $file['date'] > $yesterdayTimeStamp;
                });
            }
            if (isset($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) && $user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS == 1) {
                $filearray = array_filter($filearray, function($file) use ($conf, $moduleNameLowerCase) {
                    $regexFormattedFileName = preg_quote($file['name'], '/');
                    $fileArrays             = dol_dir_list($conf->$moduleNameLowerCase->multidir_output[$conf->entity ?? 1], 'files', 1, $regexFormattedFileName, '.odt|.pdf|barcode|_mini|_medium|_small|_large');

                    return count($fileArrays) == 0;
                });
           }
            $allMediasNumber = count($filearray);
			$pagesCounter    = $conf->global->$moduleImageNumberPerPageConf ? ceil($allMediasNumber/($conf->global->$moduleImageNumberPerPageConf ?: 1)) : 1;
			$page_array      = saturne_load_pagination($pagesCounter, $loadedPageArray ?? [], $offset ?? 0);

			print saturne_show_pagination($pagesCounter, $page_array, $offset ?? 0); ?>
			<div class="save-photo wpeo-button button-blue button-disable" value="">
                <span><?php echo $langs->trans('Add'); ?></span>
			</div>
            <div class="wpeo-button button-red button-disable delete-photo">
                <i class="fas fa-trash-alt"></i>
            </div>
            <?php
            $confirmationParams = [
                'picto'             => 'fontawesome_fa-trash-alt_fas_#e05353',
                'color'             => '#e05353',
                'confirmationTitle' => 'DeleteFiles',
                'buttonParams'      => ['No' => 'button-blue marginrightonly confirmation-close', 'Yes' => 'button-red confirmation-delete']
            ];
            require __DIR__ . '/../utils/confirmation_view.tpl.php'; ?>
        </div>
	</div>
</div>
<!-- END MEDIA GALLERY MODAL -->
