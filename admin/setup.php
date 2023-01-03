<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * \file    admin/setup.php
 * \ingroup saturne
 * \brief   Saturne setup page.
 */

// Load Dolibarr environment
include_once __DIR__ . '/../saturne.main.inc.php';

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";

require_once '../lib/saturne.lib.php';

// Translations
$langs->loadLangs(array("admin", "saturne@saturne"));

// Initialize technical objects

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

if ($action == 'setMediaDimension') {
	$MediaMaxWidthMedium = GETPOST('MediaMaxWidthMedium', 'alpha');
	$MediaMaxHeightMedium = GETPOST('MediaMaxHeightMedium', 'alpha');
	$MediaMaxWidthLarge = GETPOST('MediaMaxWidthLarge', 'alpha');
	$MediaMaxHeightLarge = GETPOST('MediaMaxHeightLarge', 'alpha');

	if (!empty($MediaMaxWidthMedium) || $MediaMaxWidthMedium === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM", $MediaMaxWidthMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightMedium) || $MediaMaxHeightMedium === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM", $MediaMaxHeightMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxWidthLarge) || $MediaMaxWidthLarge === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE", $MediaMaxWidthLarge, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightLarge) || $MediaMaxHeightLarge === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE", $MediaMaxHeightLarge, 'integer', 0, '', $conf->entity);
	}
}

/*
 * View
 */

$page_name = "SaturneSetup";
$help_url  = 'FR:Module_Saturne#Configuration';

$morejs  = array("/saturne/js/saturne.js");
$morecss = array("/saturne/css/saturne.css");

saturneHeader('saturne', $action,'',0, '', $help_url, '', '', '', 0, $morejs, $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'saturne32px@saturne');

// Configuration header
$head = saturneAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, "saturne@saturne");
print load_fiche_titre($langs->trans("SaturneData"), '', '');

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
