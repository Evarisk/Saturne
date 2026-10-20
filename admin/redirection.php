<?php

/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
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
$urlRoot = GETPOST('short_url_root', 'alpha');
$search  = trim(GETPOST('search_redirection', 'alpha'));
$page    = max(0, GETPOSTINT('page'));
$limit   = 25;

// Initialize technical objects
$saturneRedirection = new SaturneRedirection($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$moduleNameLowerCase . 'redirectionadmin', 'saturneadmin']);

// Security check - Protection if external user
$permissionToRead = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Same root for every entity : codes are resolved without one
    if ($action == 'set_short_url_root') {
        if ($urlRoot !== '' && !SaturneRedirection::isValidShortUrlRoot($urlRoot)) {
            setEventMessages($langs->trans('ErrorBadShortUrlRoot'), [], 'errors');
        } else {
            dolibarr_set_const($db, 'SATURNE_SHORT_URL_ROOT', $urlRoot, 'chaine', 0, '', 0);
            setEventMessages($langs->trans('SetupSaved'), []);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
            exit;
        }
    }

    if ($action == 'add') {
        // Empty code : a random one is generated, the case of QR codes
        $result = $saturneRedirection->createShortUrl($toUrl, $user, $fromUrl) !== '' ? 1 : -1;
        if ($result < 0 && empty($saturneRedirection->errors)) {
            $saturneRedirection->error = $langs->trans(SaturneRedirection::isValidTargetUrl($toUrl) ? 'FromUrlHelp' : 'ToUrlHelp');
        }
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

    // Removal asks for a confirmation first (action remove): a printed QR code leading to this short URL stops working
    if ($action == 'confirm_remove' && GETPOST('confirm', 'alpha') == 'yes') {
        $result = $saturneRedirection->fetch(GETPOSTINT('id'));
        if ($result <= 0) {
            setEventMessages($saturneRedirection->error ?: $langs->trans('ErrorObjectNotFound'), [], 'errors');
        } else {
            $result = $saturneRedirection->delete($user, 1, false);
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

print load_fiche_titre($langs->trans('ShortUrlRoot'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_short_url_root">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>' . $langs->trans('Parameters') . '</td><td>' . $langs->trans('Description') . '</td><td>' . $langs->trans('Value') . '</td></tr>';
print '<tr class="oddeven"><td>' . $langs->trans('ShortUrlRoot') . '</td>';
print '<td>' . $langs->trans('ShortUrlRootDescription', dol_escape_htmltag(SaturneRedirection::getDefaultShortUrlRoot())) . '</td>';
print '<td><input type="text" class="minwidth300" name="short_url_root" placeholder="' . dol_escape_htmltag(SaturneRedirection::getDefaultShortUrlRoot()) . '" value="' . dol_escape_htmltag(getDolGlobalString('SATURNE_SHORT_URL_ROOT')) . '"></td></tr>';
print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';

print load_fiche_titre($langs->trans('Configs', dol_strtolower($langs->trans('Redirections'))), '', '');

if ($action == 'remove') {
    print $form->formconfirm($_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '&id=' . GETPOSTINT('id'), $langs->trans('Remove'), $langs->trans('ConfirmRemoveRedirection'), 'confirm_remove', '', 'yes', 1);
}

// Paginated list : every controlled object gets its own short URL, there may be thousands
$param   = '&module_name=' . urlencode($moduleName) . ($search !== '' ? '&search_redirection=' . urlencode($search) : '');
$nbTotal = $saturneRedirection->countRedirections($search);
$offset  = $limit * $page;
if ($nbTotal >= 0 && $offset >= $nbTotal) {
    $page   = 0;
    $offset = 0;
}

$redirections = $saturneRedirection->fetchAll('DESC', 't.rowid', $limit + 1, $offset, $saturneRedirection->getSearchFilter($search));
$num          = is_array($redirections) ? count($redirections) : 0;

print '<form method="GET" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="module_name" value="' . dol_escape_htmltag($moduleName) . '">';
print_barre_liste($langs->trans('Redirections'), $page, $_SERVER['PHP_SELF'], $param, '', '', '', $num, $nbTotal, '', 0, '', '', $limit);
print '<div class="marginbottomonly">';
print '<input type="text" class="minwidth300" name="search_redirection" placeholder="' . dol_escape_htmltag($langs->trans('SearchRedirection')) . '" value="' . dol_escape_htmltag($search) . '">';
print ' <button type="submit" class="button small">' . $langs->trans('Search') . '</button>';
print '</div>';
print '</form>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('FromURL') . '</td>';
print '<td>' . $langs->trans('ToURL') . '</td>';
print '<td class="center">' . $langs->trans('QRCode') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

if ($num > 0) {
    foreach (array_slice($redirections, 0, $limit) as $redirection) {
        // Redirections created before the public entry point stored a path instead of a code : no short URL for them
        $shortUrl = SaturneRedirection::isValidCode($redirection->from_url) ? SaturneRedirection::getShortUrlFromCode($redirection->from_url) : '';

        print '<tr class="oddeven"><td>';
        if ($shortUrl !== '') {
            print '<a href="' . dol_escape_htmltag($shortUrl) . '" target="_blank" rel="noopener">' . dol_escape_htmltag($shortUrl) . '</a>';
            print showValueWithClipboardCPButton($shortUrl, 0, 'none');
        } else {
            print dol_escape_htmltag($redirection->from_url);
        }
        print '</td><td class="tdoverflowmax500">';
        print dol_escape_htmltag($redirection->to_url);
        print '</td><td class="center">';
        if ($shortUrl !== '') {
            $barcodeObject = new TCPDF2DBarcode($shortUrl, 'QRCODE,M');
            $qrCodePng     = $barcodeObject->getBarcodePngData(6, 6);
            $qrCodeBase64  = 'data:image/png;base64,' . base64_encode($qrCodePng);

            print '<img src="' . $qrCodeBase64 . '" alt="' . $langs->trans('QRCode') . '" width="100" height="100">';
        }
        print '</td><td class="center">';
        print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?module_name=' . urlencode($moduleName) . '&action=remove&id=' . ((int) $redirection->id) . '&token=' . newToken() . ($search !== '' ? '&search_redirection=' . urlencode($search) : '') . '&page=' . $page . '">' . $langs->trans('Remove') . '</a>';
        print '</td></tr>';
    }
} else {
    print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">' . $langs->trans('NoRecordFound') . '</span></td></tr>';
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add">';

print '<tr class="oddeven"><td>';
print '<input type="text" class="marginrightonly minwidth300 maxwidthonsmartphone" name="from_url" placeholder="' . $langs->trans('FromURL') . '" value="' . dol_escape_htmltag($fromUrl) . '">';
print $form->textwithpicto('', $langs->trans('FromUrlHelp'));
print '</td><td>';
print '<input type="text" name="to_url" class="marginrightonly minwidth300 maxwidthonsmartphone" placeholder="' . $langs->trans('ToURL') . '"  value="' . dol_escape_htmltag($toUrl) . '" required>';
print $form->textwithpicto('', $langs->trans('ToUrlHelp'));
print '</td><td class="center" colspan="2">';
print '<button class="butAction">' . $langs->trans('Add') . '</button>';
print '</td></tr>';

print '</form>';
print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
