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

$nbFilesPhotos = 0;
$nbFilesAudio = 0;

$targetModule = 'saturne';
$targetSubDir = 'test_medias';
$pathToECMImgSetupRaw = (empty($conf->$targetModule->dir_output) ? $conf->ecm->dir_output . '/' . $targetModule : $conf->$targetModule->dir_output);
if (!empty($targetSubDir)) $pathToECMImgSetupRaw .= '/' . $targetSubDir;

// Health Checks
$dirExists = dol_is_dir($pathToECMImgSetupRaw);
$dirIsWritable = $dirExists ? is_writable(dol_osencode($pathToECMImgSetupRaw)) : false;

$pathToECMImgSetup = str_replace('/', DIRECTORY_SEPARATOR, $pathToECMImgSetupRaw);

if ($dirExists) {
    $filesArrPhotos = dol_dir_list($pathToECMImgSetupRaw, 'files', 0, '\.(png|jpg|jpeg|gif|webp|PNG|JPG|JPEG|GIF|WEBP)$', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
    $nbFilesPhotos = is_array($filesArrPhotos) ? count($filesArrPhotos) : 0;
    
    $filesArrAudio = dol_dir_list($pathToECMImgSetupRaw, 'files', 0, '\.(wav|mp3|ogg|m4a|WAV|MP3|OGG|M4A)$', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
    $nbFilesAudio = is_array($filesArrAudio) ? count($filesArrAudio) : 0;
}

// Health status HTML generator for the UI tables
function getSaturneFolderHealthStatus($nbFiles, $dirExists, $dirIsWritable) {
    if (!$dirExists) {
        return '<br><span style="color: #e74c3c; font-weight: 600; font-size: 0.95em;"><i class="fas fa-exclamation-triangle"></i> Erreur : Le répertoire est introuvable ou n\'a pas encore été créé.</span>';
    }
    if (!$dirIsWritable) {
        return '<br><span style="color: #e74c3c; font-weight: 600; font-size: 0.95em;"><i class="fas fa-lock"></i> Erreur : Le répertoire existe mais vous n\'avez pas les droits d\'écriture (Vérifiez les permissions OS ' . (empty($_SERVER['WINDIR']) ? '- chmod/chown' : '- Propriétés Windows') . ').</span>';
    }
    return ' <span style="color: #2ecc71; font-weight: 600; font-size: 0.95em; margin-left: 6px;">(' . $nbFiles . ' Fichiers | <i class="fas fa-check-circle"></i> Accès OK)</span>';
}

// Wrapper container with dynamic border color matching the theme
print '<div id="saturne-config-wrapper" style="border-width: 1px; border-style: solid; border-color: transparent; padding: 20px; border-radius: 8px; margin-bottom: 30px;">';

print load_fiche_titre('<span class="saturne-dynamic-title"><i class="fas fa-camera-retro paddingright"></i> Test Upload Média Canvas</span>', '', '');

print '<div class="info" style="margin-bottom: 20px; padding: 15px; background: #e8f4f8; border-left: 4px solid #3498db; border-radius: 4px;">';
print '  <h4 style="margin-top: 0; color: #2980b9;"><i class="fas fa-lightbulb"></i> Brique Photo Dynamique (Pour Développeurs)</h4>';
print '  <p style="margin-bottom:8px;">Pour intégrer cette brique dans votre module (comme ce "Test Médias" du module Saturne) :</p>';
print '  <pre style="background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 4px; overflow-x: auto;">print saturne_render_media_block(\'saturne\', \'test_medias\', \'TEST_\', \'\', [\'show_photo\'=>true, \'show_audio\'=>false]);</pre>';
print '  <p style="margin-bottom: 0; font-size: 0.9em; color: #7f8c8d;"><i>Cette méthode gère le formulaire, la miniature, et limite l\'écriture dynamiquement.</i></p>';
print '</div>';

print saturne_render_media_block('saturne', 'test_medias', '', '', ['show_photo' => true, 'show_audio' => false]);

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

print '<tr class="oddeven">';
print '  <td>Chemins de stockage des données</td>';
print '  <td><span style="font-weight: 600;">' . $pathToECMImgSetup . '</span>' . getSaturneFolderHealthStatus($nbFilesPhotos, $dirExists, $dirIsWritable) . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>Types de fichiers autorisés (Photos)</td>';
print '  <td><span style="color: #2ecc71; font-weight: 600; font-size: 0.95em;">.jpg, .jpeg, .png, .gif, .webp</span></td>';
print '</tr>';

print '</tbody>';
print '</table>';

// End Wrapper Container Photo
print '</div>';

// Wrapper container for Audio
print '<div id="saturne-config-wrapper-audio" style="border-width: 1px; border-style: solid; border-color: transparent; padding: 20px; border-radius: 8px; margin-bottom: 30px;">';

print load_fiche_titre('<span class="saturne-dynamic-title"><i class="fas fa-microphone-alt paddingright"></i> Test ajout médias audio</span>', '', '');

print '<div class="info" style="margin-bottom: 20px; padding: 15px; background: #fdf3e7; border-left: 4px solid #e67e22; border-radius: 4px;">';
print '  <h4 style="margin-top: 0; color: #d35400;"><i class="fas fa-lightbulb"></i> Brique Audio Dynamique (Pour Développeurs)</h4>';
print '  <p style="margin-bottom:8px;">Pour l\'enregistreur vocal (avec animations et pré-écoute) :</p>';
print '  <pre style="background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 4px; overflow-x: auto;">print saturne_render_media_block(\'saturne\', \'test_medias\', \'\', \'\', [\'show_photo\'=>false, \'show_audio\'=>true]);</pre>';
print '</div>';

print saturne_render_media_block('saturne', 'test_medias', '', '', ['show_photo' => false, 'show_audio' => true, 'show_gallery' => false]);

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

print '<tr class="oddeven">';
print '  <td>Chemins de stockage des données</td>';
print '  <td><span style="font-weight: 600;">' . $pathToECMImgSetup . '</span>' . getSaturneFolderHealthStatus($nbFilesAudio, $dirExists, $dirIsWritable) . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>Types de fichiers autorisés (Audio)</td>';
print '  <td><span style="color: #8e44ad; font-weight: 600; font-size: 0.95em;">.wav, .mp3, .ogg, .m4a</span></td>';
print '</tr>';

print '</tbody>';
print '</table>';

// End Wrapper Container Audio
print '</div>';

// Match the border color to the native title color dynamically
print '<script>
document.addEventListener("DOMContentLoaded", function() {
    var titleElem = document.querySelector(".saturne-dynamic-title");
    if(titleElem) {
        var themeColor = window.getComputedStyle(titleElem).color;
        var wrapperPhoto = document.getElementById("saturne-config-wrapper");
        if(wrapperPhoto) wrapperPhoto.style.borderColor = themeColor;
        var wrapperAudio = document.getElementById("saturne-config-wrapper-audio");
        if(wrapperAudio) wrapperAudio.style.borderColor = themeColor;
    }
});
</script>';

print '

<style>
@keyframes recordingPulseAnim {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); background-color: #e74c3c; color: white; }
    50% { transform: scale(1.1); box-shadow: 0 0 0 12px rgba(231, 76, 60, 0); background-color: #c0392b; color: white; }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); background-color: #e74c3c; color: white; }
}
.recording-pulse-active {
    animation: recordingPulseAnim 1.5s infinite !important;
    background-color: #e74c3c !important;
    color: white !important;
}

@keyframes playingPulseAnim {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(123, 104, 238, 0.7); background-color: #7b68ee; color: white; }
    50% { transform: scale(1.1); box-shadow: 0 0 0 12px rgba(123, 104, 238, 0); background-color: #6a5acd; color: white; }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(123, 104, 238, 0); background-color: #7b68ee; color: white; }
}
.playing-pulse-active {
    animation: playingPulseAnim 1.5s infinite !important;
    background-color: #7b68ee !important;
    color: white !important;
}
</style>

';

include dol_buildpath('/saturne/core/tpl/medias/media_editor_modal.tpl.php');

print '<script src="'.dol_buildpath('/saturne/js/modules/errors.js', 1).'?v='.time().'"></script>';
print '<script src="'.dol_buildpath('/saturne/js/modules/toolbox.js', 1).'?v='.time().'"></script>';
print '<script src="'.dol_buildpath('/saturne/js/modules/notice.js', 1).'?v='.time().'"></script>';
print '<script src="'.dol_buildpath('/saturne/js/modules/audio.js', 1).'?v='.time().'"></script>';
print '<script src="'.dol_buildpath('/saturne/js/modules/media.js', 1).'?v='.time().'"></script>';
print '<script>console.log("Saturne MEDIA module cache busted and forced to reload"); </script>';

print dol_get_fiche_end();

llxFooter();
$db->close();

