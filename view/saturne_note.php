<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       view/saturne_note.php
 *  \ingroup    saturne
 *  \brief      Tab of notes on generic element
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
$moduleName = GETPOST('module_name', 'alpha');
$objectType = GETPOST('object_type', 'alpha');

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' .  $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$subaction  = GETPOST('subaction', 'alpha');

// Initialize technical objects
$className   = ucfirst($objectType);
$object      = new $className($db);
$extrafields = new ExtraFields($db);

$hookmanager->initHooks([$objectType . 'note', $object->element . 'note', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->$objectType->read;
$permissiontoadd  = (($object->status >= $object::STATUS_LOCKED) ? 0 : $user->rights->$moduleNameLowerCase->$objectType->write);
$permissionnote   = $permissiontoadd; // Used by include of actions_setnotes.inc.php
saturne_check_access($permissiontoread);

/*
*  Actions
*/

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be included, not include_once

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../core/tpl/actions/banner_actions.tpl.php';
}

/*
*	View
*/

$title    = $langs->trans('Note') . ' - ' . $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $help_url);

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'note', $title);
    saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

    print '<div class="fichecenter">';

    $cssclass = 'titlefield';
    $moreparam = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element);
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';

    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
