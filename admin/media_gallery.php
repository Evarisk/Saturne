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
 * \file    admin/media_gallery.php
 * \ingroup saturne
 * \brief   Saturne media gallery page.
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
	require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
	require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once __DIR__ . '/../lib/saturne.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

$form       = new Form($db);

// Parameters
$action     = GETPOST('action', 'alpha');
$value      = GETPOST('value', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->saturne->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'setMediaDimension') {
	$error = 0;
	$mediasMax['SATURNE_MEDIA_MAX_WIDTH_MINI']         = GETPOST('MediaMaxWidthMini', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_HEIGHT_MINI']        = GETPOST('MediaMaxHeightMini', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_WIDTH_SMALL']        = GETPOST('MediaMaxWidthSmall', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_HEIGHT_SMALL']       = GETPOST('MediaMaxHeightSmall', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_WIDTH_MEDIUM']       = GETPOST('MediaMaxWidthMedium', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_HEIGHT_MEDIUM']      = GETPOST('MediaMaxHeightMedium', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_WIDTH_LARGE']        = GETPOST('MediaMaxWidthLarge', 'alpha');
	$mediasMax['SATURNE_MEDIA_MAX_HEIGHT_LARGE']       = GETPOST('MediaMaxHeightLarge', 'alpha');
	$mediasMax['SATURNE_DISPLAY_NUMBER_MEDIA_GALLERY'] = GETPOST('DisplayNumberMediaGallery', 'alpha');

	foreach($mediasMax as $key => $valueMax) {
		if (empty($valueMax)) {
			setEventMessages('MediaDimensionEmptyError', [], 'errors');
			$error++;
			break;
		} else if ($valueMax < 0) {
			setEventMessages('MediaDimensionNegativeError', [], 'errors');
			$error++;
			break;
		} else {
			dolibarr_set_const($db, $key, $valueMax, 'integer', 0, '', $conf->entity);
		}
	}

	if (empty($error)) {
		setEventMessages('MediaDimensionSetWithSuccess', []);
	}
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url = 'FR:Module_Saturne#Configuration';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'media_gallery', $title, -1, 'saturne_color@saturne');

print load_fiche_titre($langs->trans('Configs', $langs->transnoentities('MediasMin')), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="media_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setMediaDimension">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMini">' . $langs->trans('MediaMaxWidthMini') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthMiniDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthMini" value="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_MINI . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMini">' . $langs->trans('MediaMaxHeightMini') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightMiniDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightMini" value="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_MINI . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthSmall">' . $langs->trans('MediaMaxWidthSmall') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthSmallDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthSmall" value="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightSmall">' . $langs->trans('MediaMaxHeightSmall') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightSmallDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightSmall" value="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMedium">' . $langs->trans('MediaMaxWidthMedium') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthMediumDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthMedium" value="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_MEDIUM . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMedium">' . $langs->trans('MediaMaxHeightMedium') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightMediumDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightMedium" value="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_MEDIUM . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthLarge">' . $langs->trans('MediaMaxWidthLarge') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthLargeDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthLarge" value="' . $conf->global->SATURNE_MEDIA_MAX_WIDTH_LARGE . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightLarge">' . $langs->trans('MediaMaxHeightLarge') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightLargeDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightLarge" value="' . $conf->global->SATURNE_MEDIA_MAX_HEIGHT_LARGE . '"></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="DisplayNumberMediaGallery">' . $langs->trans('DisplayNumberMediaGallery') . '</label></td>';
print '<td>' . $langs->trans('DisplayNumberMediaGalleryDescription') . '</td>';
print '<td><input type="number" name="DisplayNumberMediaGallery" value="' . $conf->global->SATURNE_DISPLAY_NUMBER_MEDIA_GALLERY . '"></td>';
print '</td></tr>';

print '</table>';

print $form->buttonsSaveCancel('Save', '');
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
