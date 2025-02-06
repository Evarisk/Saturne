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
 * \file    admin/setup.php
 * \ingroup saturne
 * \brief   Saturne setup page.
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

// Initialize view objects
$form = new Form($db);

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

if ($action == 'set_media_infos') {
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
        dolibarr_set_const($db, $key, $valueMax, 'integer', 0, '', $conf->entity);
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'Saturne');
$help_url  = 'FR:Module_Saturne#Configuration';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'saturne_color@saturne');

print load_fiche_titre($langs->trans('GeneralConfig'), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

// Enable public interface
print '<tr class="oddeven"><td>';
print  $langs->trans('EnablePublicInterface');
print '</td><td>';
print $langs->trans('EnablePublicInterfaceDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_ENABLE_PUBLIC_INTERFACE');
print '</td></tr>';

// Show logo for company
print '<tr class="oddeven"><td>';
print  $langs->trans('ShowCompanyLogo');
print '</td><td>';
print $langs->trans('ShowCompanyLogoDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_SHOW_COMPANY_LOGO');
print '</td></tr>';

// Use captcha
print '<tr class="oddeven"><td>';
print  $langs->trans('UseCaptcha');
print '</td><td>';
print $langs->trans('UseCaptchaDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_USE_CAPTCHA');
print '</td></tr>';

// Use all email mode
print '<tr class="oddeven"><td>';
print  $langs->trans('UseAllEmailMode');
print '</td><td>';
print $langs->trans('UseAllEmailModeDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_USE_ALL_EMAIL_MODE');
print '</td></tr>';

// Use create document on archive
print '<tr class="oddeven"><td>';
print  $langs->trans('UseCreateDocumentOnArchive');
print '</td><td>';
print $langs->trans('UseCreateDocumentOnArchiveDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_USE_CREATE_DOCUMENT_ON_ARCHIVE');
print '</td></tr>';

// Manage saturne attendants add status
print '<tr class="oddeven"><td>';
print  $langs->trans('AttendantsAddStatusManagement');
print '</td><td>';
print $langs->trans('AttendantsAddStatusManagementDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_ATTENDANTS_ADD_STATUS_MANAGEMENT');
print '</td></tr>';


// Use fast upload improvement
print '<tr class="oddeven"><td>';
print  $langs->trans('UseFastUploadImprovement');
print '</td><td>';
print $langs->transnoentities('UseFastUploadImprovementDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT');
print '</td></tr>';

// End of the table
print '</table>';

print load_fiche_titre($langs->trans('Configs', $langs->transnoentities('MediasMin')), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="media_data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_media_infos">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMini">' . $langs->trans('MediaMaxWidthMini') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthMiniDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthMini" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_MINI') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMini">' . $langs->trans('MediaMaxHeightMini') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightMiniDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightMini" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_MINI') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthSmall">' . $langs->trans('MediaMaxWidthSmall') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthSmallDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthSmall" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_SMALL') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightSmall">' . $langs->trans('MediaMaxHeightSmall') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightSmallDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightSmall" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_SMALL') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthMedium">' . $langs->trans('MediaMaxWidthMedium') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthMediumDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthMedium" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_MEDIUM') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightMedium">' . $langs->trans('MediaMaxHeightMedium') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightMediumDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightMedium" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_MEDIUM') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxWidthLarge">' . $langs->trans('MediaMaxWidthLarge') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxWidthLargeDescription') . '</td>';
print '<td><input type="number" name="MediaMaxWidthLarge" min="0" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_LARGE') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="MediaMaxHeightLarge">' . $langs->trans('MediaMaxHeightLarge') . '</label></td>';
print '<td>' . $langs->trans('MediaMaxHeightLargeDescription') . '</td>';
print '<td><input type="number" name="MediaMaxHeightLarge" value="' . getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_LARGE') . '" required></td>';
print '</td></tr>';

print '<tr class="oddeven"><td><label for="DisplayNumberMediaGallery">' . $langs->trans('DisplayNumberMediaGallery') . '</label></td>';
print '<td>' . $langs->trans('DisplayNumberMediaGalleryDescription') . '</td>';
print '<td><input type="number" name="DisplayNumberMediaGallery" value="' . getDolGlobalInt('SATURNE_DISPLAY_NUMBER_MEDIA_GALLERY') . '" required></td>';
print '</td></tr>';

// End of the table
print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
