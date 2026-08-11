<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * \file    admin/javascripts.php
 * \ingroup saturne
 * \brief   Saturne javascripts setup page
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

// Load translation files required by the page
saturne_load_langs(['admin']);

// Initialize view objects
$form = new Form($db);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->saturne->adminpage->read;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

/*
 * View
 */

$title    = $langs->trans('Javascripts', 'Saturne');
$help_url  = 'FR:Module_Saturne#Configuration';

saturne_header(0, '', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = saturne_admin_prepare_head();
print dol_get_fiche_head($head, 'javascripts', $title, -1, 'saturne_color@saturne');

print load_fiche_titre($langs->trans('JavascriptsConfig'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="settings_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

// Enable maxlength counter
print '<tr class="oddeven"><td>';
print  $langs->trans('EnableMaxlengthCounter');
print '</td><td>';
print $langs->trans('EnableMaxlengthCounterDescription');
print '</td><td class="center">';
print ajax_constantonoff('SATURNE_ENABLE_MAXLENGTH_COUNTER');
print '</td></tr>';

// End of the table
print '</table>';
print '</div>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
