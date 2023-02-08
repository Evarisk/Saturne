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
 *  \file       view/saturne_agenda.php
 *  \ingroup    saturne
 *  \brief      Tab of events on generic element
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

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

if (GETPOST('actioncode', 'array')) {
    $actioncode = GETPOST('actioncode', 'array', 3);
    if (!count($actioncode)) {
        $actioncode = '0';
    }
} else {
    $actioncode = GETPOST('actioncode', 'alpha', 3) ? GETPOST('actioncode', 'alpha', 3) : (GETPOST('actioncode') == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}

$search_agenda_label = GETPOST('search_agenda_label');

// Get pagination parameters
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');

if (empty($page) || $page == -1) { // If $page is not defined, or '' or -1
    $page = 0;
}

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
    $sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
    $sortorder = 'DESC,DESC';
}

// Initialize technical objects
$classname   = ucfirst($objectType);
$object      = new $classname($db);
$extrafields = new ExtraFields($db);

$hookmanager->initHooks([$objectType . 'agenda', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
    $upload_dir = $conf->$moduleNameLowerCase->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity] . '/' . $object->id;
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->$objectType->read;
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
    // Cancel
    if ($cancel && !empty($backtopage)) {
        header('Location: ' . $backtopage);
        exit;
    }

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        $actioncode          = '';
        $search_agenda_label = '';
    }
}

/*
*	View
*/

$title    = $langs->trans('Agenda') . ' - ' . $langs->trans(ucfirst($objectType));
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0,'', $title, $help_url);

if ($id > 0 || !empty($ref)) {
    saturne_banner_tab($object, 'agenda', $title);

    print '<div class="fichecenter">';

    $object->info($object->id);
    dol_print_object_info($object, 1);

    print '</div>';

    print dol_get_fiche_end();

    // Actions buttons
    $out = '&origin=' . urlencode($objectType . '@' . $object->module) . '&originid=' . $object->id;
    $urlbacktopage = $_SERVER['REQUEST_URI'];
    $out .= '&backtopage=' . urlencode($urlbacktopage);

    $newcardbutton = '';
    if (isModEnabled('agenda')) {
        if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
            $newcardbutton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
        }
    }

    if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
        print '<br>';
        $param = '&id=' . $object->id;
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . urlencode($limit);
        }

        print load_fiche_titre($langs->trans('ActionsOn' . ucfirst($objectType)), $newcardbutton, '');

        // List of all actions
        $filters = [];
        $filters['search_agenda_label'] = $search_agenda_label;

        show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
    }
}

// End of page
llxFooter();
$db->close();