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
 * \file    admin/media.php
 * \ingroup saturne
 * \brief   Saturne media library admin page
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
require_once __DIR__ . '/../lib/medias.lib.php';

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

$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');
$moduleName = GETPOST('module_name', 'alpha');
$subDir     = GETPOST('sub_dir', 'alpha');

if ($action == 'uploadPhoto' && !empty($moduleName) && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    $moduleNameLowerCase = dol_strtolower($moduleName);
    $uploadDir           = !empty($conf->$moduleNameLowerCase->dir_output)
        ? $conf->$moduleNameLowerCase->dir_output
        : $conf->ecm->dir_output . '/' . $moduleNameLowerCase;
    if (!empty($subDir)) {
        $uploadDir .= '/' . $subDir;
    }

    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    $res = dol_add_file_process($uploadDir, 0, 1, 'userfile', '', null, '', 1);
    if ($res > 0) {
        setEventMessages($langs->trans('PhotoWellSent'), null, 'mesgs');
    } else {
        setEventMessages($langs->trans('PhotoNotSent'), null, 'errors');
    }
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url = 'FR:Module_Saturne#Configuration';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'media', $title, -1, 'saturne_color@saturne');

// Build configuration values
$dolibarrLimit = !empty($conf->global->MAIN_UPLOAD_DOC) ? ($conf->global->MAIN_UPLOAD_DOC / 1024) . ' Mo' : $langs->trans('NotDefined');
$serverLimit   = ini_get('upload_max_filesize');
$serverLimit   = str_ireplace('m', ' Mo', $serverLimit);
$serverLimit   = str_ireplace('g', ' Go', $serverLimit);
$maxFiles      = ini_get('max_file_uploads');

$targetModule    = 'saturne';
$targetSubDir    = 'test_medias';
$uploadDirRaw    = !empty($conf->$targetModule->dir_output)
    ? $conf->$targetModule->dir_output
    : $conf->ecm->dir_output . '/' . $targetModule;
if (!empty($targetSubDir)) {
    $uploadDirRaw .= '/' . $targetSubDir;
}
$uploadDirDisplay = str_replace('/', DIRECTORY_SEPARATOR, $uploadDirRaw);

$dirExists    = dol_is_dir($uploadDirRaw);
$dirWritable  = $dirExists && is_writable(dol_osencode($uploadDirRaw));
$nbFilesPhoto = 0;
$nbFilesAudio = 0;

if ($dirExists) {
    $photoFiles   = dol_dir_list($uploadDirRaw, 'files', 0, '\.(png|jpg|jpeg|gif|webp|PNG|JPG|JPEG|GIF|WEBP)$', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
    $nbFilesPhoto = is_array($photoFiles) ? count($photoFiles) : 0;

    $audioFiles   = dol_dir_list($uploadDirRaw, 'files', 0, '\.(wav|mp3|ogg|m4a|WAV|MP3|OGG|M4A)$', '(\.meta)$', 'date', SORT_DESC);
    $nbFilesAudio = is_array($audioFiles) ? count($audioFiles) : 0;
}

/*
 * Photo block
 */

print load_fiche_titre('<i class="fas fa-camera-retro paddingright"></i>' . $langs->trans('MediaTestUploadCanvas'), '', '');

print '<div class="info" style="margin-bottom: 20px;">';
print '  <strong><i class="fas fa-lightbulb"></i> ' . $langs->trans('MediaPhotoBrick') . '</strong><br>';
print '  <code>print saturne_render_media_block(\'saturne\', \'test_medias\', \'\', \'\', [\'show_photo\' => true, \'show_audio\' => false]);</code>';
print '</div>';

print saturne_render_media_block('saturne', 'test_medias', '', '', ['show_photo' => true, 'show_audio' => false]);

print '<br>';
print load_fiche_titre('<i class="fas fa-tools paddingright"></i>' . $langs->trans('Configuration'), '', '');

print '<table class="noborder centpercent">';
print '<tbody>';
print '<tr class="liste_titre">';
print '  <td width="35%">' . $langs->trans('Parameters') . '</td>';
print '  <td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('MediaFilesUploadDownload') . ' <a href="' . DOL_URL_ROOT . '/admin/security_other.php" target="_blank"><i class="fas fa-external-link-square-alt opacitymedium"></i></a></td>';
print '  <td>';
print '    ' . $langs->trans('MaxUploadFileSize') . ' : <strong>' . $dolibarrLimit . '</strong><br>';
print '    <span class="opacitymedium">' . $langs->trans('MediaMaxUploadSizeNote', $serverLimit) . '</span>';
print '  </td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('Various') . ' <a href="' . DOL_URL_ROOT . '/admin/limits.php" target="_blank"><i class="fas fa-external-link-square-alt opacitymedium"></i></a></td>';
print '  <td>' . $langs->trans('MediaMaxFilesInForm') . ' : <strong>' . $maxFiles . '</strong></td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('MediaStoragePath') . '</td>';
print '  <td>';
print '    <strong>' . dol_escape_htmltag($uploadDirDisplay) . '</strong>';
if (!$dirExists) {
    print ' <span class="error"><i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('DirectoryNotFound') . '</span>';
} elseif (!$dirWritable) {
    print ' <span class="error"><i class="fas fa-lock"></i> ' . $langs->trans('ErrorDirNotWritable') . '</span>';
} else {
    print ' <span class="ok"><i class="fas fa-check-circle"></i> ' . $nbFilesPhoto . ' ' . $langs->trans('MediasMin') . '</span>';
}
print '  </td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('MediaAllowedTypesPhoto') . '</td>';
print '  <td><strong>.jpg, .jpeg, .png, .gif, .webp</strong></td>';
print '</tr>';

print '</tbody>';
print '</table>';

/*
 * Audio block
 */

print '<br>';
print load_fiche_titre('<i class="fas fa-microphone-alt paddingright"></i>' . $langs->trans('MediaTestAudio'), '', '');

print '<div class="info" style="margin-bottom: 20px;">';
print '  <strong><i class="fas fa-lightbulb"></i> ' . $langs->trans('MediaAudioBrick') . '</strong><br>';
print '  <code>print saturne_render_media_block(\'saturne\', \'test_medias\', \'\', \'\', [\'show_photo\' => false, \'show_audio\' => true]);</code>';
print '</div>';

print saturne_render_media_block('saturne', 'test_medias', '', '', ['show_photo' => false, 'show_audio' => true, 'show_gallery' => false]);

print '<br>';
print load_fiche_titre('<i class="fas fa-tools paddingright"></i>' . $langs->trans('Configuration'), '', '');

print '<table class="noborder centpercent">';
print '<tbody>';
print '<tr class="liste_titre">';
print '  <td width="35%">' . $langs->trans('Parameters') . '</td>';
print '  <td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('MediaFilesUploadDownload') . ' <a href="' . DOL_URL_ROOT . '/admin/security_other.php" target="_blank"><i class="fas fa-external-link-square-alt opacitymedium"></i></a></td>';
print '  <td>';
print '    ' . $langs->trans('MaxUploadFileSize') . ' : <strong>' . $dolibarrLimit . '</strong><br>';
print '    <span class="opacitymedium">' . $langs->trans('MediaMaxUploadSizeNote', $serverLimit) . '</span>';
print '  </td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('Various') . ' <a href="' . DOL_URL_ROOT . '/admin/limits.php" target="_blank"><i class="fas fa-external-link-square-alt opacitymedium"></i></a></td>';
print '  <td>' . $langs->trans('MediaMaxFilesInForm') . ' : <strong>' . $maxFiles . '</strong></td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('MediaStoragePath') . '</td>';
print '  <td>';
print '    <strong>' . dol_escape_htmltag($uploadDirDisplay) . '</strong>';
if (!$dirExists) {
    print ' <span class="error"><i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('DirectoryNotFound') . '</span>';
} elseif (!$dirWritable) {
    print ' <span class="error"><i class="fas fa-lock"></i> ' . $langs->trans('ErrorDirNotWritable') . '</span>';
} else {
    print ' <span class="ok"><i class="fas fa-check-circle"></i> ' . $nbFilesAudio . ' ' . $langs->trans('MediasMin') . '</span>';
}
print '  </td>';
print '</tr>';

print '<tr class="oddeven">';
print '  <td>' . $langs->trans('MediaAllowedTypesAudio') . '</td>';
print '  <td><strong>.wav, .mp3, .ogg, .m4a</strong></td>';
print '</tr>';

print '</tbody>';
print '</table>';

include dol_buildpath('/saturne/core/tpl/medias/media_editor_modal.tpl.php');

print dol_get_fiche_end();

llxFooter();
$db->close();
