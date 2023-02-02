<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
		dolibarr_set_const($db, "SATURNE_MEDIA_MAX_WIDTH_MEDIUM", $MediaMaxWidthMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightMedium) || $MediaMaxHeightMedium === '0') {
		dolibarr_set_const($db, "SATURNE_MEDIA_MAX_HEIGHT_MEDIUM", $MediaMaxHeightMedium, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxWidthLarge) || $MediaMaxWidthLarge === '0') {
		dolibarr_set_const($db, "SATURNE_MEDIA_MAX_WIDTH_LARGE", $MediaMaxWidthLarge, 'integer', 0, '', $conf->entity);
	}
	if (!empty($MediaMaxHeightLarge) || $MediaMaxHeightLarge === '0') {
		dolibarr_set_const($db, "SATURNE_MEDIA_MAX_HEIGHT_LARGE", $MediaMaxHeightLarge, 'integer', 0, '', $conf->entity);
	}
}

/*
 * View
 */

$page_name = "SaturneSetup";
$help_url  = 'FR:Module_Saturne#Configuration';

$morejs  = array("/saturne/js/saturne.js");
$morecss = array("/saturne/css/saturne.css");

saturne_header(1,'',0, '', $help_url, '', '', '', 0, $morejs, $morecss);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'saturne32px@saturne');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'media_gallery', '', -1, "saturne@saturne");

print load_fiche_titre($langs->trans("MediaData"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="media_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMediaDimension">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMedium">' . $langs->trans("MediaMaxWidthMedium") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxWidthMediumDescription") . '</td>';
print '<td><input type="number" name="MediaMaxWidthMedium" value="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_MEDIUM . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMedium">' . $langs->trans("MediaMaxHeightMedium") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxHeightMediumDescription") . '</td>';
print '<td><input type="number" name="MediaMaxHeightMedium" value="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_MEDIUM . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthLarge">' . $langs->trans("MediaMaxWidthLarge") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxWidthLargeDescription") . '</td>';
print '<td><input type="number" name="MediaMaxWidthLarge" value="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_LARGE . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightLarge">' . $langs->trans("MediaMaxHeightLarge") . '</label></td>';
print '<td>' . $langs->trans("MediaMaxHeightLargeDescription") . '</td>';
print '<td><input type="number" name="MediaMaxHeightLarge" value="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_LARGE . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
