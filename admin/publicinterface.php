<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    admin/publicinterface.php
 * \ingroup saturne
 * \brief   Saturne public interface config page
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
$moduleName          = GETPOST('module_name', 'aZ') ?: 'Saturne';
$moduleNameLowerCase = dol_strtolower($moduleName);

// Build the paths for the required files
$moduleLibPath   = dol_buildpath($moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '.lib.php');

// Check if files exist before including them
if (!(is_file($moduleLibPath) && is_readable($moduleLibPath))) {
    die('Failed to require ' . $moduleNameLowerCase . ' libraries: Files not found. Paths: ' . $moduleLibPath);
}
// Load Module Libraries
require_once $moduleLibPath;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$moduleNameLowerCase . 'publicinterfaceadmin']); // Note that conf->hooks_modules contains array

// Permissions
$permissionToRead = $user->hasRight($moduleNameLowerCase, 'adminpage', 'read');

// Security check
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$object     = null;
$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'set_default_public_interface_user') {
        $userId   = GETPOSTINT('default_public_interface_user_id');
        $constKey = dol_strtoupper($moduleNameLowerCase) . '_DEFAULT_PUBLIC_INTERFACE_USER';
        if ($userId != getDolGlobalInt($constKey)) {
            dolibarr_set_const($db, $constKey, $userId, 'integer', 0, '', $conf->entity);

            setEventMessage('SavedConfig');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
            exit;
        }
    }

    if ($action == 'create_default_public_interface_user') {
        $userTmp = new User($db);

        $userTmp->lastname = $langs->transnoentities('DefaultPublicInterfaceUserLastName');
        $userTmp->login    = 'default_public_interface_user';
        $userTmp->entity   = 0;
        $userTmp->employee = 0;
        $userTmp->setPassword($user);

        $userId = $userTmp->create($user);

        $constKey = dol_strtoupper($moduleNameLowerCase) . '_DEFAULT_PUBLIC_INTERFACE_USER';
        dolibarr_set_const($db, $constKey, $userId, 'integer', 0, '', $conf->entity);

        $constKey = dol_strtoupper($moduleNameLowerCase) . '_DEFAULT_PUBLIC_INTERFACE_USER_CREATED';
        dolibarr_set_const($db, $constKey, $userId, 'integer', 0, '', $conf->entity);

        setEventMessage('SavedConfig');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
        exit;
    }
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1' . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head    = $preHead();
print dol_get_fiche_head($head, 'publicinterface', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_default_public_interface_user">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('PublicInterfaceUser');
print '</td><td>';
print $langs->transnoentities('PublicInterfaceUserDescription');
print '</td>';

print '<td>';
$constKey = dol_strtoupper($moduleNameLowerCase) . '_DEFAULT_PUBLIC_INTERFACE_USER';
print img_picto($langs->trans('User'), 'user', 'class="pictofixedwidth"') . $form->select_dolusers(getDolGlobalInt($constKey) > 0 ? getDolGlobalInt($constKey) : '', 'default_public_interface_user_id', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'minwidth400 maxwidth500');
print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?module_name=' . $moduleName) . '" target="_blank"><span class="fa fa-user-plus valignmiddle paddingleft" title="' . $langs->trans('AddUser') . '"></span></a>';
$constKey = dol_strtoupper($moduleNameLowerCase) . '_DEFAULT_PUBLIC_INTERFACE_USER_CREATED';
if (getDolGlobalInt($constKey) == 0) {
    print ' <a href="' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . '&action=create_default_public_interface_user"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddUser') . '"></span></a>';
}
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
