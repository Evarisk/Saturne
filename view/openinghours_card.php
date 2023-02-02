<?php
/* Copyright (C) 2022-2023 EVARISK <dev@evarisk.com>
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
 *   	\file       view/openinghours_card.php
 *		\ingroup    saturne
 *		\brief      Page to view Opening Hours
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
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
if (isModEnabled('contrat')) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}

require_once __DIR__ . '/../class/openinghours.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$socid       = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int');
$elementType = GETPOST('element_type', 'alpha');
$backtopage  = GETPOST('backtopage', 'alpha');

if ($user->socid) {
    $socid = $user->socid;
}

// Initialize technical objects
$object = new OpeningHours($db);

if (isModEnabled($elementType)) {
    $classname = ucfirst($elementType);
    $objectLinked = new $classname($db);
}

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$elementType . 'openinghours', 'globalcard']); // Note that conf->hooks_modules contains array of hook context

// Fetch current OpeningHours object
if ($id > 0 && !empty($elementType)) {
    $morewhere = ' AND element_id = ' . $id;
    $morewhere .= ' AND element_type = ' . "'" . $elementType . "'";
    $object->fetch(0, '', $morewhere);
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->read;
$permissiontoadd  = $user->rights->$moduleNameLowerCase->read;
if (empty($conf->$moduleNameLowerCase->enabled) || !$permissiontoread) {
    accessforbidden();
}

/*
 * Actions
 */

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    if ($action == 'update' && $permissiontoadd) {
        $object->monday       = GETPOST('monday', 'alpha');
        $object->tuesday      = GETPOST('tuesday', 'alpha');
        $object->wednesday    = GETPOST('wednesday', 'alpha');
        $object->thursday     = GETPOST('thursday', 'alpha');
        $object->friday       = GETPOST('friday', 'alpha');
        $object->saturday     = GETPOST('saturday', 'alpha');
        $object->sunday       = GETPOST('sunday', 'alpha');

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
            setEventMessages($langs->trans('OpeningHoursSaved'), []);
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

$title   =  $langs->trans('OpeningHours') . ' - ' . $langs->trans(ucfirst($elementType));
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
            $prepareHead = '';
            break;
    }

    $objectLinked->fetch($id);
    if (!empty($prepareHead)) {
        $head = $prepareHead($objectLinked);
        print dol_get_fiche_head($head, 'openinghours', $title, -1, $objectLinked->picto);
    }

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/' . $elementType . '/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid='.$socid : '') . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = $objectLinked->ref;

    $morehtmlref .= '<div class="refidno">';

    // Ref customer
    $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $objectLinked->ref_customer, $objectLinked, 0, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $objectLinked->ref_customer, $objectLinked, 0, 'string', '', null, null, '', 1, 'getFormatedCustomerRef');

    // Ref supplier
    $morehtmlref .= '<br>';
    $morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $objectLinked->ref_supplier, $objectLinked, 0, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $objectLinked->ref_supplier, $objectLinked, 0, 'string', '', null, null, '', 1, 'getFormatedSupplierRef');

    // Thirdparty
    if (isModEnabled('societe')) {
        $objectLinked->fetch_thirdparty();
        $morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . (is_object($objectLinked->thirdparty) ? $objectLinked->thirdparty->getNomUrl(1) : '') . '<br>';
    }

    // Project
    if (isModEnabled('project')) {
        if (!empty($objectLinked->fk_project)) {
            $project = new Project($db);
            $project->fetch($object->fk_project);
            $morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1) . '<br>';
        } else {
            $morehtmlref .= $langs->trans('Project') . ' : ';
        }
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($objectLinked, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print dol_get_fiche_end();

    print load_fiche_titre($langs->trans(ucfirst($elementType) . 'OpeningHours'), '', '');

    print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    if (!empty($backtopage)) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }

    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans('Day') . '</th><th>' . $langs->trans('Value') . '</th></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Monday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="monday" id="monday" class="minwidth100" value="' . ($object->monday ?: GETPOST('monday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Tuesday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="tuesday" id="tuesday" class="minwidth100" value="' . ($object->tuesday ?: GETPOST('tuesday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Wednesday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="wednesday" id="wednesday" class="minwidth100" value="' . ($object->wednesday ?: GETPOST('wednesday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Thursday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="thursday" id="thursday" class="minwidth100" value="' . ($object->thursday ?: GETPOST('thursday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Friday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="friday" id="friday" class="minwidth100" value="' . ($object->friday ?: GETPOST('friday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Saturday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="saturday" id="saturday" class="minwidth100" value="' . ($object->saturday ?: GETPOST('saturday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('Sunday'), $langs->trans('OpeningHoursFormatDesc'));
    print '</td><td>';
    print '<input name="sunday" id="sunday" class="minwidth100" value="' . ($object->sunday ?: GETPOST('sunday', 'alpha')) . '"' . (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"') . '></td></tr>';

    print '</table>';

    if ($objectLinked->status < 2) {
        print '<div class="center">';
        print '<input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '">';
        print '</div>';
    }
    print '</form>';
}

// End of page
llxFooter();
$db->close();