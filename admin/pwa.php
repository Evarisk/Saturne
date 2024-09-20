<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    admin/pwa.php
 * \ingroup saturne
 * \brief   Progressive web apps page of module Saturne
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
$startUrl            = GETPOST('start_url', 'alpha');
$moduleNameLowerCase = strtolower($moduleName);

// Load Dolibarr libraries
require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';

// Load Module Libraries
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$moduleNameLowerCase . 'pwaadmin', 'saturneadmin']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissionToRead = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'generate_QRCode') {
    $urlToEncode = GETPOST('urlToEncode');

    $barcode = new TCPDF2DBarcode($urlToEncode, 'QRCODE,L');

    dol_mkdir($conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/pwa/qrcode/');
    $file = $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/pwa/qrcode/' . 'barcode_' . dol_print_date(dol_now(), 'dayhourlog') . '.png';

    $imageData = $barcode->getBarcodePngData();
    $imageData = imagecreatefromstring($imageData);
    imagepng($imageData, $file);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '&start_url=' . $urlToEncode);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head    = $preHead();
print dol_get_fiche_head($head, 'pwa', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

print '<a class="marginrightonly" href="' . $startUrl . '" target="_blank">' . img_picto('', 'url', 'class="pictofixedwidth"') . $langs->trans('PWA') . '</a>';
print showValueWithClipboardCPButton($startUrl, 0, 'none');

// PWA QR Code generation
print load_fiche_titre($langs->transnoentities('PWAQRCodeGenerationManagement'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generate_QRCode">';
print '<input hidden name="urlToEncode" value="' . $startUrl . '">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans('GeneratePWAQRCode') . '</td>';
print '<td>' . $langs->trans('GeneratePWAQRCodeDescription') . '</td>';
print '<td class="center">' . saturne_show_medias_linked($moduleNameLowerCase, $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/pwa/qrcode/', 'small', 1, 0, 0, 0, 80, 80, 0, 0, 0, 'pwa/qrcode/', null, '', 0, 0, 0, 0, 'center') . '</td></tr>';

print '</table>';
print $form->buttonsSaveCancel('Generate', '');
print '</form>';

$parameters = [];
$hookmanager->executeHooks('saturneAdminPWAAdditionalConfig', $parameters);
print $hookmanager->resPrint;

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
