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
 *   	\file       view/saturne_schedules.php
 *		\ingroup    saturne
 *		\brief      Page to view Saturne Schedules
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
$moduleNameLowerCase = strtolower($moduleName);

// Libraries
if (isModEnabled('societe')) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
}
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}
if (isModEnabled('contrat')) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}

require_once __DIR__ . '/../class/saturneschedules.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$socid       = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int');
$elementType = GETPOST('element_type', 'alpha');
$backtopage  = GETPOST('backtopage', 'alpha');
$subaction   = GETPOST('subaction', 'alpha');

if ($user->socid) {
    $socid = $user->socid;
}

// Initialize technical objects
$object = new SaturneSchedules($db);

$customClassPath = __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $elementType . '.class.php';
$customLibPath   = __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $elementType . '.lib.php';
if (isModEnabled($elementType) || is_file($customClassPath)) {
    if (is_file($customClassPath)) {
        require_once $customClassPath;
    }
    if (is_file($customLibPath)) {
        require_once $customLibPath;
    }
    $className    = ucfirst($elementType);
    $objectLinked = new $className($db);
}


// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$elementType . 'schedules',  'saturneschedules', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array of hook context

// Fetch current Schedules object
if ($id > 0 && !empty($elementType)) {
    $morewhere = ' AND element_id = ' . $id;
    $morewhere .= ' AND element_type = ' . "'" . $elementType . "'";
    $object->fetch(0, '', $morewhere);
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->read || $user->rights->$moduleNameLowerCase->lire;
$permissiontoadd  = $permissiontoread;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../core/tpl/actions/banner_actions.tpl.php';

    if ($action == 'update' && $permissiontoadd) {
        $object->monday    = GETPOST('monday', 'alpha');
        $object->tuesday   = GETPOST('tuesday', 'alpha');
        $object->wednesday = GETPOST('wednesday', 'alpha');
        $object->thursday  = GETPOST('thursday', 'alpha');
        $object->friday    = GETPOST('friday', 'alpha');
        $object->saturday  = GETPOST('saturday', 'alpha');
        $object->sunday    = GETPOST('sunday', 'alpha');

        $object->element_type = $elementType;
        $object->element_id   = $id;
        $object->tms          = dol_now('tzuser');
        $object->status       = 1;

        if ($object->id > 0) {
            $result = $object->update($user);
        } else {
            $result = $object->create($user);
        }

        if ($result > 0) {
            setEventMessages($langs->trans('SchedulesSaved'), []);
        } elseif (!empty($object->errors)) {
            setEventMessages('', $object->errors, 'errors');
        } else {
            setEventMessages($object->error, [], 'errors');
        }
        $action = '';
    }
}

/*
 *  View
 */

$title   =  $langs->trans('Schedules') . ' - ' . $langs->trans(ucfirst($elementType));
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $helpUrl);

if (!empty($objectLinked) && empty($action)) {
    switch ($elementType) {
        case 'societe':
            $prepareHead = 'societe_prepare_head';
            break;
        case 'contrat':
            $prepareHead = 'contract_prepare_head';
            break;
        default:
            $prepareHead = $elementType . '_prepare_head';
            break;
    }

    $objectLinked->fetch($id);
    if (!empty($prepareHead)) {
        print saturne_get_fiche_head($objectLinked, 'schedules', $title);
    }

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/' . $elementType . '/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid='.$socid : '') . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = '<div class="refidno">';

    if ($elementType == 'contrat') {
        // Ref customer
        $morehtmlref .= $form->editfieldkey('RefCustomer', 'ref_customer', $objectLinked->ref_customer, $objectLinked, 0, 'string', '', 0, 1);
        $morehtmlref .= $form->editfieldval('RefCustomer', 'ref_customer', $objectLinked->ref_customer, $objectLinked, 0, 'string', '', null, null, '', 1, 'getFormatedCustomerRef');

        // Ref supplier
        $morehtmlref .= '<br>';
        $morehtmlref .= $form->editfieldkey('RefSupplier', 'ref_supplier', $objectLinked->ref_supplier, $objectLinked, 0, 'string', '', 0, 1);
        $morehtmlref .= $form->editfieldval('RefSupplier', 'ref_supplier', $objectLinked->ref_supplier, $objectLinked, 0, 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
        $morehtmlref .= '<br>';

        // Project
        if (isModEnabled('project')) {
            if (!empty($objectLinked->fk_project)) {
                $morehtmlref .= $langs->trans('Project') . ' : ';
                $project = new Project($db);
                $project->fetch($object->fk_project);
                $morehtmlref .= $project->getNomUrl(1, '', 1);
                $morehtmlref .= '<br>';
            }
        }
    }

    $morehtmlref .= '</div>';

    saturne_banner_tab($objectLinked, 'ref', '', 1, 'ref', 'ref', $morehtmlref, !empty($object->photo));

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print dol_get_fiche_end();

    print load_fiche_titre($langs->trans(ucfirst($elementType) . 'Schedules'), '', '');

    print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    if (!empty($backtopage)) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans('Day') . '</th><th>' . $langs->trans('Value') . '</th></tr>';

    $daysArray = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($daysArray as $day) {
        print '<tr class="oddeven"><td>';
        print $form->textwithpicto($langs->trans(ucfirst($day)), $langs->trans('OpeningHoursFormatDesc'));
        print '</td><td>';
        print '<input name="' . $day . '" id=' . $day . '" class="minwidth100" value="' . ($object->$day ?: GETPOST($day, 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';
    }
    print '</table>';

    $parameters = [];
    $reshook = $hookmanager->executeHooks('saturneSchedules', $parameters, $objectLinked); // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    } elseif (empty($reshook)) {
        print '<div class="center">';
        print '<input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '">';
        print '</div>';
    } else {
        print $hookmanager->resPrint;
    }
    print '</form>';
}

// End of page
llxFooter();
$db->close();
