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
global $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Parameters
$action     = GETPOST('action', 'alpha');
$value      = GETPOST('value', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->saturne->adminpage->read;
saturne_check_access($permissiontoread);

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

// Use fast upload improvement
print '<tr class="oddeven"><td>';
print  $langs->trans('UseFastUploadImprovement');
print '</td><td>';
print $langs->transnoentities('UseFastUploadImprovementDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT');
print '</td></tr>';
print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
