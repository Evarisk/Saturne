<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 *  \file       view/saturne_mass_signature.php
 *  \ingroup    saturne
 *  \brief      Page to sign several objects at once for a single attendant
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
$objectType = GETPOST('object_type', 'aZ09');

$moduleNameLowerCase = strtolower($moduleName);

// Load Saturne libraries
require_once __DIR__ . '/../class/saturnesignature.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action      = GETPOST('action', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');
$elementType = GETPOST('element_type', 'aZ09');
$elementID   = GETPOSTINT('element_id');
$objectIDs   = array_filter(array_map('intval', explode(',', GETPOST('ids', 'intcomma'))));

// Initialize technical objects
$className = ucfirst($objectType);
$object    = new $className($db);
$signatory = new SaturneSignature($db, $moduleNameLowerCase, $object->element);

$hookmanager->initHooks([$objectType . 'masssignature', 'saturnemasssignature', 'saturneglobal']); // Note that conf->hooks_modules contains array

// Security check
$permissiontoadd = $user->hasRight($moduleNameLowerCase, $objectType, 'write');
saturne_check_access($permissiontoadd, null, true);

// Load the selection: only a validated object is open to signature, a template is never signed
$objects = [];
foreach ($objectIDs as $objectID) {
    $objectTmp = new $className($db);
    if ($objectTmp->fetch($objectID) > 0 && $objectTmp->status == $objectTmp::STATUS_VALIDATED && empty($objectTmp->model)) {
        $objects[$objectTmp->id] = $objectTmp;
    }
}

// Group the signatures still waiting on the selection by attendant, each of them signs once for all their objects
$attendants = [];
if (!empty($objects)) {
    $pendingFilter = 't.status <> ' . SaturneSignature::STATUS_SIGNED . ' AND t.attendance <> ' . SaturneSignature::ATTENDANCE_ABSENT;
    $pendingLines  = $signatory->fetchSignatoriesOfObjects(array_keys($objects), $object->element, $pendingFilter);
    if (is_array($pendingLines)) {
        foreach ($pendingLines as $pendingLine) {
            $lineKey = $pendingLine->element_type . '_' . $pendingLine->element_id;
            if (!isset($attendants[$lineKey])) {
                $attendants[$lineKey] = [
                    'element_type' => $pendingLine->element_type,
                    'element_id'   => $pendingLine->element_id,
                    'name'         => dolGetFirstLastname($pendingLine->firstname, $pendingLine->lastname),
                    'society'      => $pendingLine->society_name,
                    'roles'        => [],
                    'objects'      => []
                ];
            }
            $attendants[$lineKey]['roles'][$pendingLine->role]        = $pendingLine->role;
            $attendants[$lineKey]['objects'][$pendingLine->fk_object] = $pendingLine->fk_object;
        }
    }
}

$selectedAttendant = $attendants[$elementType . '_' . $elementID] ?? [];

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Action to add the signature drawn by the selected attendant on every object they still have to sign
    if ($action == 'add_signature' && $permissiontoadd) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($selectedAttendant) || empty($data['signature'])) {
            setEventMessages($langs->trans('NoAttendantToSign'), [], 'errors');
        } else {
            $nbOk  = 0;
            $error = 0;
            foreach ($selectedAttendant['objects'] as $objectID) {
                $result = $signatory->signAsElement($user, $objectID, $object->element, $selectedAttendant['element_type'], $selectedAttendant['element_id'], $data['signature']);
                if ($result > 0) {
                    $nbOk++;
                } elseif ($result < 0) {
                    setEventMessages($signatory->error, $signatory->errors, 'errors');
                    $error++;
                    break;
                }
            }

            if ($error == 0) {
                setEventMessages($langs->trans('RecordsSignedForAttendant', $nbOk, $selectedAttendant['name']), []);
            }
        }

        // The signature is posted by ajax and the page is reloaded right after: rendering here would
        // consume the messages in a response nobody reads, they must survive until that reload
        exit;
    }
}

/*
 * View
 */

$title   = $langs->trans('MassSignature');
$pageUrl = $_SERVER['PHP_SELF'] . '?module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($objectType) . '&ids=' . implode(',', array_keys($objects)) . '&backtopage=' . urlencode($backtopage);

// The signature pad is loaded the same way the public signature page loads it
saturne_header(1, '', $title);

print load_fiche_titre($title, '', '');
print '<input type="hidden" name="token" value="' . newToken() . '">';

if (empty($objects)) {
    print '<div class="opacitymedium">' . $langs->trans('NoSignableObjectSelected') . '</div>';
} elseif (empty($attendants)) {
    print '<div class="opacitymedium">' . $langs->trans('NoPendingSignatureOnSelection') . '</div>';
} elseif (empty($selectedAttendant)) {
    // First step: the attendant that is going to sign has to be picked
    print '<div class="opacitymedium">' . $langs->trans('SelectTheAttendantToSign', count($objects)) . '</div><br>';

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Attendant') . '</td>';
    print '<td>' . $langs->trans('Role') . '</td>';
    print '<td class="center">' . $langs->trans('NbOfObjectsToSign') . '</td>';
    print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
    print '</tr>';

    foreach ($attendants as $attendant) {
        $roles = [];
        foreach ($attendant['roles'] as $role) {
            $roles[] = $langs->transnoentities($role);
        }

        print '<tr class="oddeven">';
        print '<td>' . dol_escape_htmltag($attendant['name']);
        if (dol_strlen($attendant['society']) > 0) {
            print ' <span class="opacitymedium">(' . dol_escape_htmltag($attendant['society']) . ')</span>';
        }
        print '</td>';
        print '<td>' . implode(', ', $roles) . '</td>';
        print '<td class="center">' . count($attendant['objects']) . '</td>';
        print '<td class="center"><a class="butAction" href="' . $pageUrl . '&element_type=' . urlencode($attendant['element_type']) . '&element_id=' . $attendant['element_id'] . '"><i class="fas fa-signature"></i> ' . $langs->trans('Sign') . '</a></td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
} else {
    // Second step: the picked attendant signs once, the signature lands on every object of the selection
    print '<div class="opacitymedium">' . $langs->trans('SignOnceForTheseObjects', dol_escape_htmltag($selectedAttendant['name']), count($selectedAttendant['objects'])) . '</div><br>';

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>' . $langs->trans('Ref') . '</td><td>' . $langs->trans('Label') . '</td></tr>';
    foreach ($selectedAttendant['objects'] as $objectID) {
        print '<tr class="oddeven">';
        print '<td>' . $objects[$objectID]->getNomUrl(1) . '</td>';
        print '<td>' . dol_escape_htmltag($objects[$objectID]->label ?? '') . '</td>';
        print '</tr>';
    }
    print '</table>';
    print '</div><br>';

    print '<div class="signature-container" data-public-interface="false">';
    print '<canvas class="canvas-container canvas-signature" style="height: 250px; width: 100%; border: #0b419b solid 2px"></canvas>';
    print '<div class="signature-erase wpeo-button button-square-50 button-grey"><span><i class="fas fa-eraser"></i></span></div>';
    print '<div class="signature-validate wpeo-button button-grey button-disable"><i class="fas fa-save"></i> ' . $langs->trans('SignatureSaveButton') . '</div>';
    print '</div>';
}

print '<div class="tabsAction">';
if (!empty($selectedAttendant)) {
    print '<a class="butAction" href="' . $pageUrl . '"><i class="fas fa-users"></i> ' . $langs->trans('BackToAttendantSelection') . '</a>';
}
if (dol_strlen($backtopage) > 0) {
    print '<a class="butAction" href="' . dol_escape_htmltag($backtopage) . '">' . $langs->trans('BackToList') . '</a>';
}
print '</div>';

// End of page
llxFooter();
$db->close();
