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
$moduleClassPath = dol_buildpath($moduleNameLowerCase . '/core/modules/mod' . $moduleName . '.class.php');

// Check if files exist before including them
if (!(is_file($moduleLibPath) && is_readable($moduleLibPath)) || !(is_file($moduleClassPath) && is_readable($moduleClassPath))) {
    die('Failed to require ' . $moduleNameLowerCase . ' libraries: Files not found. Paths: ' . $moduleLibPath . ' , ' . $moduleClassPath);
}
// Load Module Libraries
require_once $moduleLibPath;
require_once $moduleClassPath;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action     = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize objects

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$moduleNameLowerCase . 'publicinterfaceadmin', 'globalcard']); // Note that conf->hooks_modules contains array

// Permissions
$permissiontoread = $user->hasRight($moduleNameLowerCase, 'adminpage', 'read');

// Security check
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'set_session_trainer_responsible') {
    $responsibleId = GETPOST('session_trainer_responsible_id');
    if ($responsibleId != getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE')) {
        dolibarr_set_const($db, 'DOLIMEET_SESSION_TRAINER_RESPONSIBLE', $responsibleId, 'integer', 0, '', $conf->entity);
    }

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head    = $preHead();
print dol_get_fiche_head($head, 'publicinterface', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

print load_fiche_titre($langs->trans('TrainingSessions'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_session_trainer_responsible">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('SessionTrainerResponsible');
print '</td><td>';
print $langs->transnoentities('SessionTrainerResponsibleDesc');
print '</td>';

print '<td class="minwidth400 maxwidth500">';
print img_picto($langs->trans('User'), 'user', 'class="pictofixedwidth"') . $form->select_dolusers(getDolGlobalInt('DOLIMEET_SESSION_TRAINER_RESPONSIBLE'), 'session_trainer_responsible_id', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'minwidth400 maxwidth500');
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

$constArray['dolimeet'] = [
    'AnswerPublicInterfaceUseSignatory' => [
        'name'        => 'AnswerPublicInterfaceUseSignatory',
        'description' => 'AnswerPublicInterfaceUseSignatoryDescription',
        'code'        => 'DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY',
    ]
];
require __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
