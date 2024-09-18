<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    admin/redirection.php
 * \ingroup saturne
 * \brief   Saturne redirection page
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

// Load Dolibarr libraries
require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';

// Load Module libraries
require_once __DIR__ . '/../lib/saturne.lib.php';
require_once __DIR__ . '/../class/saturneredirection.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action  = GETPOST('action', 'alpha');
$fromUrl = GETPOST('from_url', 'alpha');
$toUrl   = GETPOST('to_url', 'alpha');

// Initialize technical objects
$saturneRedirection = new SaturneRedirection($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$moduleNameLowerCase . 'redirectionadmin', 'saturneadmin']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissionToRead = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'add') {
        $saturneRedirection->from_url = $fromUrl;
        $saturneRedirection->to_url   = $toUrl;

        $result = $saturneRedirection->create($user, true);
        if ($result > 0) {
            setEventMessage($langs->trans('ObjectCreated', 'redirection'));
            header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
            exit;
        } elseif (!empty($saturneRedirection->errors)) {
            setEventMessages($langs->trans('ErrorCreateObject', 'redirection'), $saturneRedirection->errors, 'errors');
        } else {
            setEventMessages($saturneRedirection->error, [], 'errors');
        }
    }

    if ($action == 'remove') {
        $saturneRedirection->fetch(GETPOST('id'));

        $result = $saturneRedirection->delete($user, true, false);
        if ($result > 0) {
            setEventMessage($langs->trans('ObjectDeleted', 'redirection'));
            header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
            exit;
        } elseif (!empty($saturneRedirection->errors)) {
            setEventMessages($langs->trans('ErrorDeleteObject', 'redirection'), $saturneRedirection->errors, 'errors');
        } else {
            setEventMessages($saturneRedirection->error, [], 'errors');
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', 'Saturne');
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, '', 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head    = $preHead();
print dol_get_fiche_head($head, 'redirection', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

print load_fiche_titre($langs->trans('Configs', dol_strtolower($langs->trans('Redirections'))), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('FromURL') . '</td>';
print '<td>' . $langs->trans('ToURL') . '</td>';
print '<td class="center">' . $langs->trans('QRCode') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

$redirections = $saturneRedirection->fetchAll();
if (is_array($redirections) && !empty($redirections)) {
    foreach ($redirections as $redirection) {
        $barcodeObject = new TCPDF2DBarcode($redirection->from_url, 'QRCODE,H');
        $qrCodePng     = $barcodeObject->getBarcodePngData(6, 6);
        $qrCodeBase64  = 'data:image/png;base64,' . base64_encode($qrCodePng);

        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="remove">';
        print '<tr class="oddeven"><td>';
        print $redirection->from_url;
        print '</td><td>';
        print $redirection->to_url;
        print '</td><td class="center">';
        print '<img src="' . $qrCodeBase64 . '" alt="'. $langs->trans('QRCode') .'" width="100" height="100">';
        print '</td><td class="center">';
        print '<input type="hidden" name="id" value="' . $redirection->id . '">';
        print '<button class="butAction">'. $langs->trans('Remove') . '</button>';
        print '</td></tr>';
        print '</form>';
    }
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add">';

print '<tr class="oddeven"><td>';
print '<input type="text" class="marginrightonly minwidth300 maxwidthonsmartphone" name="from_url" placeholder="' . $langs->trans('FromURL') . '" value="' . $fromUrl . '" required>';
print $form->textwithpicto('', $langs->trans('FromUrlHelp'));
print '</td><td>';
print '<input type="text" name="to_url" class="marginrightonly minwidth300 maxwidthonsmartphone" placeholder="' . $langs->trans('ToURL') . '"  value="' . $toUrl . '" required>';
print $form->textwithpicto('', $langs->trans('ToUrlHelp'));
print '</td><td class="center" colspan="2">';
print '<button class="butAction">'. $langs->trans('Add') . '</button>';
print '</td></tr>';

print '</form>';
print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
