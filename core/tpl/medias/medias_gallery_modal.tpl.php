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
global $action, $conf, $db, $langs, $mediaListInPage, $moduleName, $moduleNameLowerCase, $moduleNameUpperCase, $subaction, $user;

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

    if (is_array($_FILES['userfile']['tmp_name'])) {
        $userfiles = $_FILES['userfile']['tmp_name'];
    } else {
        $userfiles                                           = array($_FILES['userfile']['tmp_name']);
    }

    $addedMedias = [];

    foreach ($userfiles as $key => $userfile) {
        $error = 0;
        if (empty($_FILES['userfile']['tmp_name'][$key])) {
            $error++;
            $maxUploadSizeMo = round($conf->global->MAIN_UPLOAD_DOC / 1024, 1);
            $linkToSettings = '<a href="' . DOL_URL_ROOT . '/admin/security_file.php?mainmenu=home&leftmenu=setup_security" target="_blank" style="text-decoration: underline;">' . $langs->trans("Setup") . '</a>';
            $errorMessage = $langs->transnoentitiesnoconv('ErrorThisFileSizeTooLarge', (string) ($_FILES['userfile']['name'][$key] ?? '')) . ' (' . $maxUploadSizeMo . ' Mo) - ' . $linkToSettings;

            if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
                setEventMessages($errorMessage, null, 'errors');
                $submitFileErrorText = array('message' => $errorMessage, 'code' => '1337');
            } else {
                setEventMessages($errorMessage, null, 'errors');
                $submitFileErrorText = array('message' => $errorMessage, 'code' => '1337');
            }
        }

        if (! $error) {
            $generatethumbs = 1;
            // An edited media is sent back under its own name: it must replace the original
            // instead of landing beside it as a numbered copy
            $allowOverwrite = GETPOSTINT('overwritemedia');
            $res = dol_add_file_process($uploadDir, $allowOverwrite, 1, 'userfile', '', null, '', $generatethumbs);
            if ($res > 0) {
                $confWidthMedium  = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_MEDIUM';
                $confHeightMedium = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_MEDIUM';
                $confWidthLarge   = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_LARGE';
                $confHeightLarge  = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_LARGE';

                // Create thumbs
                $imgThumbLarge  = saturne_vignette($uploadDir . '/' . $_FILES['userfile']['name'][$key], $conf->global->$confWidthLarge, $conf->global->$confHeightLarge, '_large');
                $imgThumbMedium = saturne_vignette($uploadDir . '/' . $_FILES['userfile']['name'][$key], $conf->global->$confWidthMedium, $conf->global->$confHeightMedium, '_medium');
                $result         = $ecmdir->changeNbOfFiles('+');

                // An edited media comes back under its own name: replacing a file is not adding one
                if (empty($allowOverwrite)) {
                    $addedMedias[] = $_FILES['userfile']['name'][$key];
                }
            } else {
                setEventMessages($langs->transnoentitiesnoconv("ErrorThisFileExists", (string) ($_FILES['userfile']['name'][$key] ?? ''), $langs->transnoentitiesnoconv("File")), null, 'errors');
                $submitFileErrorText = array('message' => $langs->transnoentities('ErrorThisFileExists', (string) ($_FILES['userfile']['name'][$key] ?? '')), 'code' => '1337');
            }
        }
    }

    saturne_media_library_event($addedMedias);
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
        if (saturne_favorite_media_needs_update($object, $data['objectSubType'], $pathToObjectImg)) {
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
    foreach ($mediaSizes as $size) {
        $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
        $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
        saturne_vignette($pathToECMImg . '/' . $fileName, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
        saturne_vignette($pathToObjectImg . '/' . $fileName, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
    }
}

if ($subaction == 'addFiles') {
    $data = json_decode(file_get_contents('php://input'), true);

    $objectType = $data['objectType'];
    $objectId   = $data['objectId'];

    $className = $objectType;
    $object    = new $className($db);
    $object->fetch($objectId);

    $pathToECMImg = $conf->ecm->multidir_output[$conf->entity] . '/' . $moduleNameLowerCase . '/medias';
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
        $favoriteNeedsUpdate = saturne_favorite_media_needs_update($object, $data['objectSubtype'], $pathToObjectImg);
        foreach ($fileNames as $fileName) {
            $fileName = dol_sanitizeFileName($fileName);
            if ($favoriteNeedsUpdate) {
                $object->{$data['objectSubtype']} = $fileName;
                $favoriteNeedsUpdate             = false;
            }

            dol_copy($pathToECMImg . '/' . $fileName, $pathToObjectImg . '/' . $fileName);

            // Create thumbs
            foreach ($mediaSizes as $size) {
                $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
                $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
                saturne_vignette($pathToObjectImg . '/' . $fileName, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
            }
        }
        if ($objectId > 0) {
            $object->setValueFrom($data['objectSubtype'], $object->{$data['objectSubtype']}, '', '', 'text', '', $user);

            // The medias now belong to the object: record it in its agenda
            $object->context['medias'] = $fileNames;
            $object->call_trigger('SATURNE_MEDIA_LINK', $user);
        }
    }
}

// An edited media is written back over the file it came from, in the folder of the object that
// owns it: the shared gallery library keeps the untouched original
if ($subaction == 'editObjectMedia' && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    $objectType = GETPOST('object_type', 'aZ09');
    $objectId   = GETPOSTINT('object_id');
    $objectSubDir = GETPOST('object_subdir', 'alphanohtml');
    $fileName   = dol_sanitizeFileName(GETPOST('file_name', 'alphanohtml'));

    if ($objectId > 0 && class_exists($objectType) && dol_strlen($fileName) > 0 && !empty($_FILES['userfile']['tmp_name'][0])) {
        $object = new $objectType($db);
        $object->fetch($objectId);

        $filePath = saturne_object_media_dir($object, $objectType, $objectSubDir) . '/' . $fileName;

        // Only a media already sitting there may be replaced, never a path the caller made up
        if (dol_is_file($filePath) && dol_move_uploaded_file($_FILES['userfile']['tmp_name'][0], $filePath, 1) > 0) {
            foreach ($mediaSizes as $size) {
                $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
                $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
                saturne_vignette($filePath, $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
            }
        }
    }
}

// Records a pending media can be assigned to, answered as JSON to the list view
if ($subaction == 'listMediaTargets') {
    require_once __DIR__ . '/../../../lib/object.lib.php';

    $targetMetadata = saturne_get_objects_metadata(GETPOST('object_type', 'aZ09'));
    $targets        = [];

    if (!empty($targetMetadata['table_element']) && !empty($targetMetadata['name_field'])) {
        $nameField  = $targetMetadata['name_field'];
        $labelField = $targetMetadata['label_field'] ?? '';

        $sql  = 'SELECT t.rowid, t.' . $nameField . ' as target_name';
        $sql .= (dol_strlen($labelField) > 0 && $labelField != $nameField) ? ', t.' . $labelField . ' as target_label' : '';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $targetMetadata['table_element'] . ' as t';

        // Not every table of the list is entity aware, so the scope is only added when it is
        $entityColumn = $db->DDLDescTable(MAIN_DB_PREFIX . $targetMetadata['table_element'], 'entity');
        if ($entityColumn && $db->num_rows($entityColumn) > 0) {
            $sql .= ' WHERE t.entity IN (' . getEntity($targetMetadata['table_element']) . ')';
        }

        $sql .= ' ORDER BY t.rowid DESC LIMIT 300';

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                // Several objects name and label the same column, showing it twice reads as a bug
                $label = $obj->target_name;
                $label .= (!empty($obj->target_label) && $obj->target_label != $obj->target_name) ? ' - ' . $obj->target_label : '';
                // A long label would stretch the select past the footer, the full one stays in the title
                $label  = dol_trunc($label, 60);
                $targets[] = ['id' => $obj->rowid, 'label' => $label];
            }
        }
    }
}

// Assign pending medias to a record, the way the gallery does for a single one
if ($subaction == 'assignMedias') {
    $data = json_decode(file_get_contents('php://input'), true);

    require_once __DIR__ . '/../../../lib/object.lib.php';

    $targetType     = $data['objectType'] ?? '';
    $targetId       = (int) ($data['objectId'] ?? 0);
    $targetFiles    = $data['filenames'] ?? [];
    $targetMetadata = saturne_get_objects_metadata($targetType);

    if ($targetId > 0 && !empty($targetFiles) && !empty($targetMetadata['class_name'])) {
        dol_include_once('/' . $targetMetadata['class_path']);

        $targetClass  = $targetMetadata['class_name'];
        $targetObject = new $targetClass($db);

        if ($targetObject->fetch($targetId) > 0) {
            $libraryDir = $conf->ecm->multidir_output[$conf->entity] . '/' . $moduleNameLowerCase . '/medias';
            // The metadata key prefixes the module name, the media folder is named after the
            // element the object itself declares
            $targetDir  = saturne_object_media_dir($targetObject, $targetObject->element);

            if (!dol_is_dir($targetDir)) {
                dol_mkdir($targetDir);
            }

            $assigned = [];
            foreach ($targetFiles as $targetFile) {
                $targetFile = dol_sanitizeFileName($targetFile);

                if (empty($targetFile) || !dol_is_file($libraryDir . '/' . $targetFile)) {
                    continue;
                }

                if (dol_copy($libraryDir . '/' . $targetFile, $targetDir . '/' . $targetFile) <= 0) {
                    continue;
                }

                foreach ($mediaSizes as $size) {
                    $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
                    $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
                    saturne_vignette($targetDir . '/' . $targetFile, getDolGlobalInt($confWidth), getDolGlobalInt($confHeight), '_' . $size);
                }

                $assigned[] = $targetFile;
            }

            if (!empty($assigned)) {
                $targetObject->context['medias'] = $assigned;
                $targetObject->call_trigger('SATURNE_MEDIA_LINK', $user);
                $langs->load('medias@saturne');
                $msg = $langs->trans('Medias') . ' : ' . implode(', ', $assigned) . ' ' . mb_strtolower($langs->trans('AssignedTo'), 'UTF-8') . ' ' . $targetObject->ref;
                echo '<span id="saturne-assign-medias-response" style="display:none;">' . json_encode(['success' => true, 'message' => $msg]) . '</span>';
                exit;
            }
            echo '<span id="saturne-assign-medias-response" style="display:none;">' . json_encode(['success' => true]) . '</span>';
            exit;
        }
    }
    echo '<span id="saturne-assign-medias-response" style="display:none;">' . json_encode(['success' => false]) . '</span>';
    exit;
}

// Description of a library media, kept in the ECM index next to the file
if ($subaction == 'setMediaDescription') {
    $data = json_decode(file_get_contents('php://input'), true);

    $mediaName = dol_sanitizeFileName($data['filename'] ?? '');

    if (dol_strlen($mediaName) > 0) {
        $mediaRelative = $moduleNameLowerCase . '/medias';
        $mediaIndexed  = 'ecm/' . $mediaRelative;
        $mediaPath     = $conf->ecm->multidir_output[$conf->entity] . '/' . $mediaRelative . '/' . $mediaName;

        if (dol_is_file($mediaPath)) {
            // A media added outside the gallery may not be indexed yet, index it before describing it
            // The index keeps the path from the documents root, the ecm/ prefix included
            if ($ecmfile->fetch(0, '', $mediaIndexed . '/' . $mediaName) <= 0) {
                addFileIntoDatabaseIndex($conf->ecm->multidir_output[$conf->entity] . '/' . $mediaRelative, $mediaName, $mediaPath);
                $ecmfile->fetch(0, '', $mediaIndexed . '/' . $mediaName);
            }

            if ($ecmfile->id > 0) {
                $ecmfile->description = $data['description'] ?? '';
                $ecmfile->update($user);
            }
        }
    }
}

// Take a media off a record without touching the library copy: the media simply goes back to
// being pending. The record folder is resolved here rather than taken from the caller, so no
// path travels in from the browser.
if ($subaction == 'unlinkMediaFromRecord') {
    $data = json_decode(file_get_contents('php://input'), true);

    $unlinkElement  = dol_sanitizeFileName($data['element'] ?? '');
    $unlinkRef      = dol_sanitizeFileName($data['ref'] ?? '');
    $unlinkFileName = dol_sanitizeFileName($data['filename'] ?? '');

    if (dol_strlen($unlinkElement) > 0 && dol_strlen($unlinkRef) > 0 && dol_strlen($unlinkFileName) > 0) {
        $unlinkDir  = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $unlinkElement . '/' . $unlinkRef;
        $unlinkPath = $unlinkDir . '/' . $unlinkFileName;

        if (dol_is_file($unlinkPath)) {
            dol_delete_file($unlinkPath);

            foreach ($mediaSizes as $size) {
                $unlinkThumb = $unlinkDir . '/thumbs/' . saturne_get_thumb_name($unlinkFileName, $size);

                if (dol_is_file($unlinkThumb)) {
                    dol_delete_file($unlinkThumb);
                }
            }

            // The record displayed that media: hand the role over to one it still holds
            $unlinkRecord = saturne_media_record_object($moduleNameLowerCase, $unlinkElement, $unlinkRef);

            if ($unlinkRecord !== null && property_exists($unlinkRecord, 'photo') && $unlinkRecord->photo == $unlinkFileName) {
                $remainingFiles      = dol_dir_list($unlinkDir, 'files', 0, '', '(\.meta|_preview.*\.png)$');
                $unlinkRecord->photo = !empty($remainingFiles) ? $remainingFiles[0]['name'] : '';

                $unlinkRecord->setValueFrom('photo', $unlinkRecord->photo, '', '', 'text', '', $user);
            }
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
                foreach ($mediaSizes as $size) {
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

        foreach ($mediaSizes as $size) {
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

    dol_set_user_param($db, $conf, $user, $tabparam);
}

if (!(isset($error) && $error) && $subaction == 'toggleUnlinkedMedias') {
    $toggleValue = GETPOST('toggle_unlinked_medias');

    $tabparam['SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS'] = $toggleValue;

    dol_set_user_param($db, $conf, $user, $tabparam);
}

if (!(isset($error) && $error) && $subaction == 'regenerate_thumbs') {
    $data = json_decode(file_get_contents('php://input'), true);

    foreach ($mediaSizes as $size) {
        $confWidth  = 'SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size);
        $confHeight = 'SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size);
        saturne_vignette($data['fullname'], $conf->global->$confWidth, $conf->global->$confHeight, '_' . $size);
    }
}

if (!empty($submitFileErrorText) && is_array($submitFileErrorText)) {
    print '<input class="error-medias" value="' . htmlspecialchars(json_encode($submitFileErrorText)) . '">';
}

$mediaResolutionParts = explode('-', getDolGlobalString('SATURNE_MEDIA_RESOLUTION_USED'));
$mediaResolution      = explode('x', $mediaResolutionParts[1] ?? '1920x1080');

require_once __DIR__ . '/media_editor_modal.tpl.php';

// Clicking a media hands it over to the photo editor, whose markup must be on the page
include __DIR__ . '/photo_editor_modal.tpl.php'; ?>

<!-- START MEDIA GALLERY MODAL -->
<div class="wpeo-modal modal-photo" id="media_gallery" data-id="<?php echo (isset($object) && $object) ? $object->id : 0 ?>">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header media-gallery-header">
                        <div class="media-gallery-title-wrapper" style="display: flex; flex-direction: column; align-items: center; margin-right: 10px;">
                <h2 class="modal-title" style="text-transform: none; margin-bottom: 0; line-height: 1;"><?php echo $langs->trans('Medias')?></h2>
                <?php
                $maxUploadSizeMo = round($conf->global->MAIN_UPLOAD_DOC / 1024, 1);
                $linkToSettings = DOL_URL_ROOT . '/admin/security_file.php?mainmenu=home&leftmenu=setup_security';
                ?>
                <a href="<?php echo $linkToSettings; ?>" target="_blank" class="media-gallery-max-size" style="font-size: 0.65rem; color: #888; text-decoration: none; margin-top: 2px;">(<?php echo $maxUploadSizeMo; ?> Mo)</a>
            </div>
            
            <div class="media-gallery-search-container">
                <div class="wpeo-autocomplete">
                    <label class="autocomplete-label" for="search_in_gallery">
                        <i class="autocomplete-icon-before fas fa-search"></i>
                        <input id="search_in_gallery" placeholder="<?php echo $langs->trans('Search') . '...' ?>" class="autocomplete-search-input" type="text" />
                    </label>
                </div>
            </div>

            <div class="media-gallery-actions">
                <!-- Toggle Unlinked -->
                <?php if (getDolGlobalInt('SATURNE_MEDIA_GALLERY_SHOW_ALL_MEDIA_INFOS')) : ?>
                    <span class="media-header-action" title="<?php echo dol_escape_htmltag($langs->trans('ShowOnlyUnlinkedMedias')); ?>">
                        <?php if (isset($user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) && $user->conf->SATURNE_MEDIA_GALLERY_SHOW_UNLINKED_MEDIAS) : ?>
                            <span id="del_unlinked_medias" value="0" class="linkobject toggle-unlinked-medias"><i class="fas fa-link" style="color:var(--color-primary);"></i></span>
                        <?php else : ?>
                            <span id="set_unlinked_medias" value="1" class="linkobject toggle-unlinked-medias"><i class="fas fa-link" style="color:#aaa;"></i></span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                
                <!-- Toggle Today -->
                <span class="media-header-action" title="<?php echo dol_escape_htmltag($langs->trans('ShowOnlyMediasAddedToday')); ?>">
                    <?php if (isset($user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) && $user->conf->SATURNE_MEDIA_GALLERY_SHOW_TODAY_MEDIAS) : ?>
                        <span id="del_today_medias" value="0" class="linkobject toggle-today-medias"><i class="fas fa-calendar-day" style="color:var(--color-primary);"></i></span>
                    <?php else : ?>
                        <span id="set_today_medias" value="1" class="linkobject toggle-today-medias"><i class="fas fa-calendar-day" style="color:#aaa;"></i></span>
                    <?php endif; ?>
                </span>

                <label for="add_media_to_gallery" class="media-add-btn" title="<?php echo dol_escape_htmltag($langs->trans('AddFile')); ?>">
                    <i class="fas fa-plus"></i>
                </label>
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <input type="file" id="add_media_to_gallery" class="hidden" name="userfile[]" multiple accept="image/*" style="display:none;">
                
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>
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
            
            <div id="progressBarContainer" style="display: none;">
                <div id="progressBarText">
                    <span class="upload-filename"></span>
                    <span class="upload-count"></span>
                </div>
                <div id="progressBarTrack">
                    <div id="progressBar"></div>
                </div>
            </div>
            <div class="saturne-media-tabs">
                <span class="saturne-media-tab active" data-tab="gallery"><i class="fas fa-th"></i> <?php echo $langs->trans('Gallery'); ?></span>
                <?php if (empty($mediaListInPage)) : ?>
                <span class="saturne-media-tab" data-tab="pending"><i class="fas fa-hourglass-half"></i> <?php echo $langs->trans('PendingMedias'); ?></span>
                <?php endif; ?>
            </div>
            <div class="saturne-media-tab-pane" data-tab="gallery">
                <div class="ecm-photo-list-content">
                    <?php
                    $relativepath = $moduleNameLowerCase . '/medias/thumbs';
                    print saturne_show_medias($moduleNameLowerCase, 'ecm', $conf->ecm->multidir_output[$conf->entity] . '/' . $moduleNameLowerCase . '/medias', 'small', 80, 80, (!empty($offset) ? $offset : 1));
                    ?>
                </div>
            </div>
            <?php if (empty($mediaListInPage)) : ?>
            <div class="saturne-media-tab-pane hidden" data-tab="pending" data-loaded="0">
                <?php
                // Telling pending medias apart walks the whole module folder: only build the list
                // when its tab asks for it, never on a plain page load
                if ($subaction == 'pendingMedias') {
                    require __DIR__ . '/medias_pending_list.tpl.php';
                }
                ?>
            </div>
            <?php endif; ?>

            <?php
            // Records to assign to. The list asking for them may be this modal or the media page,
            // so they are answered outside the pane the page replaces
            if ($subaction == 'listMediaTargets') { ?>
                <select class="saturne-pending-target-options hidden">
                    <option value=""></option>
                    <?php foreach ($targets as $target) : ?>
                        <option value="<?php echo (int) $target['id']; ?>"><?php echo dol_escape_htmltag($target['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php } ?>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            
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
