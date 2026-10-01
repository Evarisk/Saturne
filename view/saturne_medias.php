<?php

/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       view/saturne_medias.php
 *  \ingroup    saturne
 *  \brief      Page listing the medias of a module library
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Get module parameters
$moduleName          = GETPOST('module_name', 'alpha');
$moduleNameLowerCase = dol_strtolower($moduleName);

// Load Saturne libraries
require_once __DIR__ . '/../lib/medias.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['medias@saturne']);

// Get parameters
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');

// The list lives in the page here, so the gallery modal must not render a second one: the caller
// picking the list out of a response would otherwise land on the wrong one
$mediaListInPage = true;
$pendingOnly     = GETPOSTISSET('pending') ? GETPOSTINT('pending') : 1;

// Security
if (!isModEnabled($moduleNameLowerCase)) {
    accessforbidden($langs->trans('ErrorModuleNotEnabled', $moduleName));
}

// The library is shared by the whole module: reading it asks for a right on that module, the same
// way its files are already served to anyone allowed to read it
$permissiontoread = !empty($user->rights->$moduleNameLowerCase);
saturne_check_access($permissiontoread);

/*
 * View
 */

$title    = $langs->trans('Medias');
$help_url = '';

// The gallery modal carries the actions the list sends its writes to
saturne_header(1, '', $title, $help_url);

print load_fiche_titre($title . ' - ' . $moduleName, '', 'fontawesome_fa-images_fas_#63acdc');

print '<div class="saturne-media-tabs">';
print '<a class="saturne-media-tab' . ($pendingOnly ? ' active' : '') . '" href="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?module_name=' . urlencode($moduleName) . '&pending=1"><i class="fas fa-hourglass-half"></i> ' . $langs->trans('PendingMedias') . '</a>';
print '<a class="saturne-media-tab' . ($pendingOnly ? '' : ' active') . '" href="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?module_name=' . urlencode($moduleName) . '&pending=0"><i class="fas fa-images"></i> ' . $langs->trans('AllMedias') . '</a>';
print '</div>';

// The pane keeps the markup the list view reloads itself into after an assignment
print '<div class="saturne-media-tab-pane saturne-media-page-pane" data-tab="pending" data-loaded="1">';
require __DIR__ . '/../core/tpl/medias/medias_pending_list.tpl.php';
print '</div>';

// End of page
llxFooter();
$db->close();
