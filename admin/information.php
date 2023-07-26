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
 * \file    admin/information.php
 * \ingroup saturne
 * \brief   Information page of module Saturne and other
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
require_once DOL_DOCUMENT_ROOT . '/includes/parsedown/Parsedown.php';

// Load Saturne libraries
require_once __DIR__ . '/../lib/saturne.lib.php';

// Global variables definitions
global $db, $langs;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$filename   = GETPOST('filename', 'alpha');
$tabName    = GETPOST('tab_name', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$parsedown = new Parsedown();

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url = 'FR:Module_Saturne';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, $tabName, $title, -1, 'saturne_color@saturne');

$filePath = dol_buildpath('saturne/' . $filename . '.md');
$content  = file_get_contents($filePath);
print $parsedown->text($content);

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
