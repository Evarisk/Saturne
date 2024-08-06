<?php
/* Copyright (C) 2021-2023 EVARISK
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
 * \file    admin/redirections.php
 * \ingroup saturne
 * \brief   Saturne redirections page
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
$moduleNameLowerCase = strtolower($moduleName);

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

// Load Module libraries
require_once __DIR__ . '/../lib/saturne.lib.php';
require_once __DIR__ . '/../class/saturneqrcode.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Initialize view objects
$form = new Form($db);

// Get parameters
$action = GETPOST('action', 'alpha');
$url = GETPOST('url', 'alpha');

// Initialize Redirection Manager
$saturneQRCode = new SaturneQRCode($db);

// Security check - Protection if external user
$permissiontoread = $user->rights->saturne->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Add a redirection
if ($action == 'add') {
    $saturneQRCode->url = $url;
    $saturneQRCode->encoded_qr_code = $saturneQRCode->getQRCodeBase64($url);
    $saturneQRCode->module_name = 'saturne';
    $saturneQRCode->status = 1;
    $saturneQRCode->create($user);
}

// Remove a redirection
if ($action == 'remove') {
    $saturneQRCode->fetch(GETPOST('id'));
    $saturneQRCode->delete($user, false, false);
}

/*
 * View
 */

$title    = $langs->trans('RedirectionsSetup', $moduleName);
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $help_url);

print load_fiche_titre($title, '', 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head    = $preHead();
print dol_get_fiche_head($head, 'qrcode', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);
$QRCodes = $saturneQRCode->fetchAll();

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('URL') . '</td>';
print '<td class="text-blank
">' . $langs->trans('QR Code') . '</td>';
print '<td class="center">' . $langs->trans('ModuleName') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

if (is_array($QRCodes) && !empty($QRCodes)) {
    foreach ($QRCodes as $QRCode) {
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="remove">';
        print '<tr class="oddeven"><td>';
        print $QRCode->url;
        print '</td><td class="center">';
        print '<img src="' . $QRCode->encoded_qr_code . '" alt="QR Code" width="100" height="100">';
        print '</td><td class="center">';
        print ucfirst($QRCode->module_name);
        print '</td><td class="center">';
        print '<input type="hidden" name="id" value="' . $QRCode->id . '">';
        print '<input type="submit" class="button" value="' . $langs->trans('Remove') . '">';
        print '</td></tr>';
        print '</form>';
    }
}


print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add">';

print '<tr class="oddeven"><td>';
print '<input placeholder="'. $langs->trans('URLToEncode') .'" type="text" name="url" value="' . $url . '">';
print "&nbsp;" . $form->textwithpicto($langs->trans('Help'), $langs->trans('HowToUseURLToEncode'));
print '</td><td class="center">';
print '</td><td class="center">';
print '</td><td class="center">';
print '<input type="submit" class="button" value="' . $langs->trans('Add') . '">';
print '</td></tr>';

print '</table>';
print '</form>';

print dol_get_fiche_end();
llxFooter();
$db->close();
?>
