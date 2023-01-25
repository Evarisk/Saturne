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

// Load Dolibarr environment
if (file_exists('../../../main.inc.php')) {
    require_once __DIR__ . '/../../../main.inc.php';
} elseif (file_exists('../../../../../main.inc.php')) {
    require_once '../../../../main.inc.php';
} else {
    die('Include of main fails');
}

// Get module parameters
$moduleName        = GETPOST('module_name', 'alpha');
$objectType        = GETPOST('object_type', 'alpha');
$objectParentType  = GETPOSTISSET('object_parent_type') ? GETPOST('object_parent_type', 'alpha') : $objectType;

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}
if (isModEnabled('contrat')) {
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}

require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectParentType . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
$langs->loadLangs([$moduleNameLowerCase . '@' . $moduleNameLowerCase, 'other']);

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

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
if (isModEnabled('project')) {
    $project = new Project($db);
}
if (isModEnabled('contrat')) {
    $contract = new Contrat($db);
}

$hookmanager->initHooks([$object->element . 'agenda', 'globalcard']); // Note that conf->hooks_modules contains array

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

$title    = $langs->trans('Agenda') . ' - ' . $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_' . $moduleName;
//@todo changement avec saturne
$morejs   = ['/dolimeet/js/dolimeet.js'];
$morecss  = ['/dolimeet/css/dolimeet.css'];

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

if ($id > 0 || !empty($ref)) {
    // Configuration header
    //@todo changer le mot session
    $head = sessionPrepareHead($object);
    print dol_get_fiche_head($head, 'agenda', $title, -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $object->element . '/' . $object->element . '_list.php', 1) . '?restore_lastsearch_values=1' . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = '<div class="refidno">';
    // Project
    if (isModEnabled('project')) {
        if (!empty($object->fk_project)) {
            $project->fetch($object->fk_project);
            $morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1);
        } else {
            $morehtmlref .= '';
        }
    }

    // Contract @todo hook car spécifique a dolimeet
    if (isModEnabled('contrat')) {
        if ($object->element == 'trainingsession') {
            if (!empty($object->fk_contrat)) {
                $contract->fetch($object->fk_contrat);
                $morehtmlref .= $langs->trans('Contract') . ' : ' . $contract->getNomUrl(1, '', 1);
            } else {
                $morehtmlref .= '';
            }
        }
    }
    $morehtmlref .= '</div>';

    //@todo problème avec dolimeet
    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    $object->info($object->id);
    dol_print_object_info($object, 1);

    print '</div>';

    print dol_get_fiche_end();

    // Actions buttons
    $out = '&origin=' . urlencode($object->element . '@' . $object->module) . '&originid=' . $object->id;
    $urlbacktopage = $_SERVER['REQUEST_URI'];
    $out .= '&backtopage=' . urlencode($urlbacktopage);

    print '<div class="tabsAction">';
    if (isModEnabled('agenda')) {
        if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
            print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out . '">' . $langs->trans('AddAction') . '</a>';
        } else {
            print '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans('AddAction') . '</a>';
        }
    }
    print '</div>';

    if (isModEnabled('agenda') && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
        $param = '&id=' . $object->id;
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . urlencode($limit);
        }

        print load_fiche_titre($langs->trans('ActionsOn' . ucfirst($object->element)), '', '');

        // List of all actions
        $filters = [];
        $filters['search_agenda_label'] = $search_agenda_label;

        show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
    }
}

// End of page
llxFooter();
$db->close();
