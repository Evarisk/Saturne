<?php
/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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
} else {
    die('Include of saturne main fails');
}

// Get module parameters
$moduleName       = GETPOST('module_name', 'alpha');
$objectType       = GETPOST('object_type', 'alpha');
$objectParentType = GETPOSTISSET('object_parent_type') ? GETPOST('object_parent_type', 'alpha') : $objectType;

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectParentType . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname   = ucfirst($objectType);
$object      = new $classname($db);
$extrafields = new ExtraFields($db);

$hookmanager->initHooks([$objectType . 'note', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->$objectType->read;
$permissionnote   = $user->rights->$moduleNameLowerCase->$objectType->write; // Used by include of actions_setnotes.inc.php
if (empty($conf->$moduleNameLowerCase->enabled) || !$permissiontoread) {
    accessforbidden();
}

/*
*  Actions
*/

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be included, not include_once
}

/*
*	View
*/

$title    = $langs->trans('Note') . ' - ' . $langs->trans(ucfirst($objectType));
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $help_url);

if ($id > 0 || !empty($ref)) {
    saturne_banner_tab($object, 'note', $title);

    print '<div class="fichecenter">';

    $cssclass = 'titlefield';
    $moreparam = '&module_name=' . urlencode($moduleName) . '&object_parent_type=' . urlencode($objectParentType) . '&object_type=' . urlencode($objectType);
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';

    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();