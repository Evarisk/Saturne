<?php

/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
 * \file    admin/mediastest.php
 * \ingroup saturne
 * \brief   Saturne medias test page
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../lib/saturne.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;
/** @var \Conf $conf */
/** @var \DoliDB $db */
/** @var \Translate $langs */
/** @var \User $user */

// Load translation files required by the page
saturne_load_langs(['admin', 'saturne@saturne']);

// Security check
/** @phpstan-ignore-next-line */
$permissiontoread = $user->rights->saturne->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */
$subaction = GETPOST('subaction');

if ($subaction == 'save_image_override') {
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $base64 = isset($_POST['base64']) ? $_POST['base64'] : '';
    
    header('Content-Type: application/json');
    
    if (empty($filename)) { echo json_encode(['status' => 'error', 'msg' => 'Nom du fichier manquant']); exit; }
    if (empty($base64)) { echo json_encode(['status' => 'error', 'msg' => 'Donnees image manquantes']); exit; }
    
    /** @phpstan-ignore-next-line */
    $pathToECMImg = $conf->ecm->dir_output . '/saturne/test_medias';
    if (!dol_is_dir($pathToECMImg)) {
        echo json_encode(['status' => 'error', 'msg' => 'Dossier ECM introuvable']); exit; 
    }
    
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);
        if ($data !== false) {
             $bytes = file_put_contents($pathToECMImg . '/' . $filename, $data);
             if ($bytes === false) {
                 echo json_encode(['status' => 'error', 'msg' => 'Echec ecriture fichier sur disque']); exit;
             }
             // regenerate thumb visually
             $confWidthSmall  = !empty($conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL : 120;
             $confHeightSmall = !empty($conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL : 120;
             saturne_vignette($pathToECMImg . '/' . $filename, $confWidthSmall, $confHeightSmall, '_small');
             echo json_encode(['status' => 'ok']);
             exit;
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Echec decryptage base64']); exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Format base64 non reconnu ou entete manque']); exit;
    }
}

if ($subaction == 'add_img') {
    $objectSubType = GETPOST('objectSubType');
    if (empty($objectSubType)) $objectSubType = 'test'; // Fallback
    
    // Always render the container so the UI doesn't disappear
    print '<div class="linked-medias ' . $objectSubType . '">';
    print '  <div class="fast-upload-options" data-from-type="saturne" data-from-subtype="test" data-from-subdir="test"></div>';
    
    if (!empty($_FILES['img']['error']) || empty($_FILES['img']['tmp_name'])) {
        $errCode = isset($_FILES['img']['error']) ? $_FILES['img']['error'] : 'MISSING';
        $errMsg = 'Erreur inconnue';
        switch ($errCode) {
            case UPLOAD_ERR_INI_SIZE:   $errMsg = 'Le fichier dépasse la limite upload_max_filesize de votre serveur (' . ini_get('upload_max_filesize') . ').'; break;
            case UPLOAD_ERR_FORM_SIZE:  $errMsg = 'Le fichier dépasse la contrainte de taille côté client.'; break;
            case UPLOAD_ERR_PARTIAL:    $errMsg = 'Le fichier n\'a été que partiellement téléchargé.'; break;
            case UPLOAD_ERR_NO_FILE:    $errMsg = 'Aucun fichier n\'a été téléchargé.'; break;
            case UPLOAD_ERR_NO_TMP_DIR: $errMsg = 'Dossier temporaire manquant sur votre serveur.'; break;
            case UPLOAD_ERR_CANT_WRITE: $errMsg = 'Échec de l\'écriture du fichier sur le disque du serveur.'; break;
            case UPLOAD_ERR_EXTENSION:  $errMsg = 'Une extension PHP a stoppé l\'upload du fichier.'; break;
            case 'MISSING':             $errMsg = 'Le fichier (tmp_name) est bloqué et absent, potentiellement dû à une limite post_max_size atteinte (' . ini_get('post_max_size') . ') !'; break;
        }
        
        print '  <p style="margin-top: 10px; color: #e74c3c; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Échec de l\'upload PHP. Code: ' . $errCode . ' <br><span style="font-size:12px; font-weight:normal;">' . $errMsg . '</span></p>';
    } else {
        $decodedImage = file_get_contents($_FILES['img']['tmp_name']);
        
        /** @phpstan-ignore-next-line */
    $pathToECMImg = $conf->ecm->dir_output . '/saturne/test_medias';
        if (!dol_is_dir($pathToECMImg)) {
            dol_mkdir($pathToECMImg);
        }
        
        $fileName = dol_print_date(dol_now(), 'dayhourlog') . '_' . rand(1, 9999) . '_test.jpeg';
        file_put_contents($pathToECMImg . '/' . $fileName, $decodedImage);
        
        // Generate thumbnail for the gallery
        require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
        if (!dol_is_dir($pathToECMImg . '/thumbs')) {
            dol_mkdir($pathToECMImg . '/thumbs');
        }
        // Assuming global conf sizes or standard ones
        $maxWidth = !empty($conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL : 120;
        $maxHeight = !empty($conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL : 120;
        vignette($pathToECMImg . '/' . $fileName, $maxWidth, $maxHeight, '_small', 50, 'thumbs');
        
        // Call Saturne's native library handler to render the standard linked-medias HTML!
        // Includes the new thumbnail.
        $dummyObj = new stdClass();
        $dummyObj->id = 999;
        $dummyObj->element = 'test';
        $dummyObj->photo = ''; // no specific favorite
        print '<div class="linked-medias test">';
        print '<div style="display: flex; align-items: center; gap: 12px; margin-top: 10px; background: transparent; padding: 0;">';
        
        // The camera button (Orange #f39c12)
        print '  <label for="upload-media" id="label-upload-media" style="cursor:pointer; display:flex; flex-shrink: 0; justify-content:center; align-items:center; width: 50px; min-width: 50px; height: 50px; min-height: 50px; background-color: #f39c12; color: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 0; padding: 0; box-sizing: border-box;">';
        print '    <i class="fas fa-camera" style="font-size: 20px;"></i>';
        print '    <input type="file" id="upload-media" class="file-upload-input fast-upload-improvement" accept="image/*" style="display: none;">';
        print '  </label>';

        // The single badged photo
        if (dol_is_dir($pathToECMImg)) {
            $filearray = dol_dir_list($pathToECMImg, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
            $totalPhotos = count($filearray);
            
            if ($totalPhotos > 0) {
                $urls = [];
                foreach ($filearray as $file) {
                    $urls[] = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=1&file=' . urlencode('saturne/test_medias/' . $file['name']);
                }
                $urlsJson = htmlspecialchars(json_encode($urls), ENT_QUOTES, 'UTF-8');
                
                // Generate raw html thumb, stripping buttons and links
                $thumbHtml = saturne_show_medias_linked('ecm', $pathToECMImg, 'small', 1, -1, 0, 0, 50, 50, 1, 1, 0, 'saturne/test_medias', $dummyObj, 'photo', 0, 0, 1, 0, '', 0);
                if (!empty($thumbHtml)) {
                    // Extract just the img tag so we completely avoid table wrappers and bad classes
                    preg_match('/<img[^>]+>/i', $thumbHtml, $matches);
                    if (!empty($matches[0])) {
                        $imgTag = $matches[0];
                        $imgTag = str_replace('photowithmargin', '', $imgTag);
                        $imgTag = preg_replace('/style="([^"]*)"/', 'style="$1 width:100% !important; height:100% !important; object-fit:cover !important; display:block !important; border-radius:12px !important; margin:0 !important; padding:0 !important; box-sizing:border-box !important;"', $imgTag);
                        if (strpos($imgTag, 'style=') === false) {
                            $imgTag = str_replace('<img ', '<img style="width:100% !important; height:100% !important; object-fit:cover !important; display:block !important; border-radius:12px !important; margin:0 !important; padding:0 !important; box-sizing:border-box !important;" ', $imgTag);
                        }
                        
                        print '<div class="open-media-editor-as-gallery" data-json="' . $urlsJson . '" style="position: relative; flex-shrink: 0; width: 50px; min-width: 50px; height: 50px; min-height: 50px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); cursor: pointer; display: block; padding: 0; margin: 0; background: #fff; box-sizing: border-box;">';
                        print $imgTag;
                        print '<span style="position:absolute; top:-6px; right:-6px; background:#8f9ba8; color:white; border-radius:12px; height:18px; min-width:18px; padding: 0 4px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); box-sizing:border-box;">' . $totalPhotos . '</span>';
                        print '</div>';
                    }
                }
            }
        }
        print '</div>';
        print '</div>';
        exit;
    }
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url  = 'FR:Module_Saturne#Configuration';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'mediastest', $title, -1, 'saturne_color@saturne');

$dolibarrLimit = !empty($conf->global->MAIN_UPLOAD_DOC) ? ($conf->global->MAIN_UPLOAD_DOC / 1024) . ' Mo' : 'Non défini';
$serverLimit = ini_get('upload_max_filesize');
$serverLimit = str_ireplace('m', ' Mo', $serverLimit);
$serverLimit = str_ireplace('g', ' Go', $serverLimit);
$maxFiles = ini_get('max_file_uploads') . ' fichiers';

// Wrapper container with dynamic border color matching the theme
print '<div id="saturne-config-wrapper" style="border-width: 1px; border-style: solid; border-color: transparent; padding: 20px; border-radius: 8px; margin-bottom: 30px;">';

print load_fiche_titre('<span class="saturne-dynamic-title"><i class="fas fa-camera-retro paddingright"></i> Test Upload Média Canvas</span>', '', '');

print '<div class="linked-medias medias test" id="master-media-row-container" style="padding: 10px 0;">';
print '  <div class="fast-upload-options" data-from-type="saturne" data-from-subtype="test" data-from-subdir="test_medias"></div>';

print '<div style="display: flex; align-items: center; gap: 12px; margin-top: 10px; background: transparent; padding: 0;">';
        
// The camera button
print '  <label for="upload-media" id="label-upload-media" style="cursor:pointer; display:flex; flex-shrink: 0; justify-content:center; align-items:center; width: 50px; min-width: 50px; height: 50px; min-height: 50px; background-color: #f39c12; color: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 0; padding: 0; box-sizing: border-box;">';
print '    <i class="fas fa-camera" style="font-size: 20px;"></i>';
print '    <input type="file" id="upload-media" class="file-upload-input fast-upload-improvement" accept="image/*" style="display: none;">';
print '  </label>';

// Render the existing gallery if items exist
/** @phpstan-ignore-next-line */
    $pathToECMImg = $conf->ecm->dir_output . '/saturne/test_medias';
if (dol_is_dir($pathToECMImg)) {
    $maxWidth = !empty($conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL : 120;
    $maxHeight = !empty($conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL : 120;

    $dummyObj = new stdClass();
    $dummyObj->id = 999;
    $dummyObj->element = 'test';
    
    $filearray = dol_dir_list($pathToECMImg, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
    $totalPhotos = count($filearray);
    
    if ($totalPhotos > 0) {
        $urls = [];
        foreach ($filearray as $file) {
            $urls[] = DOL_URL_ROOT . '/document.php?modulepart=ecm&entity=1&file=' . urlencode('saturne/test_medias/' . $file['name']);
        }
        $urlsJson = htmlspecialchars(json_encode($urls), ENT_QUOTES, 'UTF-8');
        
        $thumbHtml = saturne_show_medias_linked('ecm', $pathToECMImg, 'small', 1, -1, 0, 0, 50, 50, 1, 1, 0, 'saturne/test_medias', $dummyObj, 'photo', 0, 0, 1, 0, '', 0);
        if (!empty($thumbHtml)) {
            preg_match('/<img[^>]+>/i', $thumbHtml, $matches);
            if (!empty($matches[0])) {
                $imgTag = $matches[0];
                $imgTag = str_replace('photowithmargin', '', $imgTag);
                $imgTag = preg_replace('/style="([^"]*)"/', 'style="$1 width:100% !important; height:100% !important; object-fit:cover !important; display:block !important; border-radius:12px !important; margin:0 !important; padding:0 !important; box-sizing:border-box !important;"', $imgTag);
                if (strpos($imgTag, 'style=') === false) {
                    $imgTag = str_replace('<img ', '<img style="width:100% !important; height:100% !important; object-fit:cover !important; display:block !important; border-radius:12px !important; margin:0 !important; padding:0 !important; box-sizing:border-box !important;" ', $imgTag);
                }
                
                print '<div class="open-media-editor-as-gallery" data-json="' . $urlsJson . '" style="position: relative; flex-shrink: 0; width: 50px; min-width: 50px; height: 50px; min-height: 50px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); cursor: pointer; display: block; padding: 0; margin: 0; background: #fff; box-sizing: border-box;">';
                print $imgTag;
                print '<span style="position:absolute; top:-6px; right:-6px; background:#8f9ba8; color:white; border-radius:12px; height:18px; min-width:18px; padding: 0 4px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:600; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); box-sizing:border-box;">' . $totalPhotos . '</span>';
                print '</div>';
            }
        }
    }
}
print '</div>';
print '</div>';

print '<br>';
print load_fiche_titre('<i class="fas fa-tools paddingright"></i> Configuration', '', '');

print '<table class="noborder centpercent" style="margin-bottom: 0;">';
print '<tbody>';
print '<tr class="liste_titre">';
print '  <td width="35%">Paramètres</td>';
print '  <td>Valeurs</td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>Fichiers (Envoyer fichier | Téléchargement) <a href="'.DOL_URL_ROOT.'/admin/security_other.php" target="_blank" title="Aller aux réglages natifs Dolibarr"><i class="fas fa-external-link-square-alt" style="color: #b0b0b0; font-size: 14px; margin-left: 6px;"></i></a></td>';
print '  <td>';
print '    Taille maximum des fichiers envoyés (0 pour interdire l\'envoi). <span style="color: #e74c3c; font-weight: 600; margin-left: 5px;">' . $dolibarrLimit . '</span><br>';
print '    <span class="opacitymedium" style="font-size: 0.95em;">Remarque: La configuration de votre PHP limite la taille des envois à : <span style="color: #e74c3c; font-weight: 600;">' . $serverLimit . '</span>, quelle que soit la valeur de ce paramètre.</span>';
print '  </td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>Divers <a href="'.DOL_URL_ROOT.'/admin/limits.php" target="_blank" title="Aller aux réglages natifs Dolibarr"><i class="fas fa-external-link-square-alt" style="color: #b0b0b0; font-size: 14px; margin-left: 6px;"></i></a></td>';
print '  <td>';
print '    Nombre maximum de fichiers joints dans un formulaire <span style="color: #e74c3c; font-weight: 600; margin-left: 5px;">' . $maxFiles . '</span>';
print '  </td>';
print '</tr>';

print '</tbody>';
print '</table>';

// End Wrapper Container
print '</div>';

// Match the border color to the native title color dynamically
print '<script>
document.addEventListener("DOMContentLoaded", function() {
    var titleElem = document.querySelector(".saturne-dynamic-title");
    if(titleElem) {
        var themeColor = window.getComputedStyle(titleElem).color;
        document.getElementById("saturne-config-wrapper").style.borderColor = themeColor;
    }
});
</script>';

include dol_buildpath('/saturne/core/tpl/medias/media_editor_modal.tpl.php');

print '<script src="'.dol_buildpath('/saturne/js/modules/errors.js', 1).'?v='.time().'"></script>';
print '<script src="'.dol_buildpath('/saturne/js/modules/media.js', 1).'?v='.time().'"></script>';
print '<script>console.log("Saturne MEDIA module cache busted and forced to reload"); </script>';

print dol_get_fiche_end();

llxFooter();
$db->close();
