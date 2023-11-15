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
 *  \file       view/saturne_agenda.php
 *  \ingroup    saturne
 *  \brief      Tab of events on generic element
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');
$showNav     = GETPOST('show_nav', 'int');
$handlePhoto = GETPOST('handle_photo', 'alpha');
$subaction   = GETPOST('subaction', 'alpha');

if (GETPOST('actioncode', 'array')) {
    $actioncode = GETPOST('actioncode', 'array', 3);
    if (!count($actioncode)) {
        $actioncode = '0';
    }
} else {
    $actioncode = GETPOST('actioncode', 'alpha', 3) ? GETPOST('actioncode', 'alpha', 3) : (GETPOST('actioncode') == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}

$searchAgendaLabel = GETPOST('search_agenda_label');

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
$className   = ucfirst($objectType);
$object      = new $className($db);
$extrafields = new ExtraFields($db);

$hookmanager->initHooks([$objectType . 'agenda', $object->element . 'agenda', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
    $upload_dir = $conf->$moduleNameLowerCase->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity] . '/' . $object->id;
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->$objectType->read;
$permissiontoadd  = $user->rights->$moduleNameLowerCase->$objectType->write;
saturne_check_access($permissiontoread, $object);

/*
*  Actions
*/

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Cancel
    if ($cancel && !empty($backtopage)) {
        header('Location: ' . $backtopage);
        exit;
    }

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        $actioncode          = '';
        $searchAgendaLabel = '';
    }

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../core/tpl/actions/banner_actions.tpl.php';
}

/*
*	View
*/

$title    = $langs->trans('Agenda') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_' . $moduleName;

$reshook  = $hookmanager->executeHooks('saturneCustomHeaderFunction', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
    $customHeaderFunction = $hookmanager->resPrint;
    $customHeaderFunction($title, $helpUrl);
} else {
    saturne_header(0, '', $title, $helpUrl);
}

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'agenda', $title);
    saturne_banner_tab($object, 'ref', '', dol_strlen($showNav) > 0 ? $showNav : 1, 'ref', 'ref', method_exists($object, 'getMoreHtmlRef') ? $object->getMoreHtmlRef($object->id) : '', ((!empty($object->photo) || dol_strlen($handlePhoto) > 0) ? $handlePhoto : false));

    print '<div class="fichecenter">';

    $object->info($object->id);
    dol_print_object_info($object, 1);

    print '</div>';

    print dol_get_fiche_end();

    // Actions buttons
    $out = '&origin=' . urlencode($object->element . '@' . $object->module) . '&originid=' . $object->id;
    $urlbacktopage = $_SERVER['REQUEST_URI'];
    $out .= '&backtopage=' . urlencode($urlbacktopage);

    $newCardButton = '';
    if (isModEnabled('agenda')) {
        if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
            $newCardButton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
        }

        if (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read)) {
            print '<br>';
            $param = '&id=' . $object->id;
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . urlencode($limit);
            }

            print load_fiche_titre($langs->trans('ActionsOnObject', $langs->transnoentities('The' . ucfirst($object->element))), $newCardButton, '');

            // List of all actions
            $filters = [];
            $filters['search_agenda_label'] = $searchAgendaLabel;

            show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
        }
    }

}

// End of page
llxFooter();
$db->close();
