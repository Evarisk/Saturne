<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/admin/maintenance_view.tpl.php
 * \ingroup saturne
 * \brief   View of the Saturne maintenance page
 *
 * Expects $permissiontoclean, $scope and $orphanDocuments prepared by admin/maintenance.php
 */

global $langs;

print load_fiche_titre($langs->trans('Maintenance'), '', '');

if (empty($permissiontoclean)) {
    print '<div class="wpeo-notice notice-info"><div class="notice-content"><div class="notice-subtitle"><strong>'
        . $langs->trans('MaintenanceAdminOnly') . '</strong></div></div></div>';
    return;
}

$orphanTotal = 0;
foreach ($orphanDocuments as $orphanDocument) {
    $orphanTotal += $orphanDocument['nb'];
}

print '<form name="cleanOrphanDocuments" id="cleanOrphanDocuments" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="cleanOrphanDocuments">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="tdtop">' . $langs->trans('OrphanDocuments') . '</td>';
print '<td>';
print $langs->trans('OrphanDocumentsDescription') . '<br>';

// The two screens look alike when a single entity comes out: without this line nothing tells
// whether the rest of the installation is clean or simply out of reach
if ($scope['all']) {
    print '<strong>' . $langs->trans('OrphanDocumentsScopeAllEntities') . '</strong><br><br>';
} else {
    print '<strong>' . $langs->trans('OrphanDocumentsScopeCurrentEntity', $scope['entity'] . ' - ' . getDolGlobalString('MAIN_INFO_SOCIETE_NOM')) . '</strong><br>';
    print '<span class="opacitymedium">' . $langs->trans('OrphanDocumentsScopeCurrentEntityHelp') . '</span><br><br>';
}

if (empty($orphanDocuments)) {
    print '<strong>' . $langs->trans('OrphanDocumentsNone') . '</strong>';
} else {
    print '<strong>' . $langs->trans('OrphanDocumentsFound', $orphanTotal) . '</strong><br><br>';

    print '<table class="noborder">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Entity') . '</td>';
    print '<td>' . $langs->trans('Type') . '</td>';
    print '<td class="right">' . $langs->trans('NbOfLines') . '</td>';
    print '</tr>';
    foreach ($orphanDocuments as $orphanDocument) {
        print '<tr class="oddeven">';
        print '<td>' . $orphanDocument['entity'] . '</td>';
        print '<td>' . dol_escape_htmltag($orphanDocument['type']) . '</td>';
        print '<td class="right">' . $orphanDocument['nb'] . '</td>';
        print '</tr>';
    }
    print '</table>';
}

print '</td>';

print '<td class="center tdtop">';
if (empty($orphanDocuments)) {
    print '<input type="submit" class="button" value="' . $langs->trans('Delete') . '" disabled>';
} else {
    // The deletion cannot be undone: the confirmation repeats the count and the scope, the two
    // things the administrator has just read, rather than asking a bare "are you sure"
    $confirmMessage = $scope['all']
        ? $langs->transnoentities('OrphanDocumentsConfirmDeleteAllEntities', $orphanTotal)
        : $langs->transnoentities('OrphanDocumentsConfirmDeleteEntity', $orphanTotal, $scope['entity']);

    print '<input type="submit" class="button reposition" name="cleanOrphanDocumentsSubmit" value="' . $langs->trans('Delete') . '"';
    print ' onclick="return confirm(\'' . dol_escape_js($confirmMessage) . '\');">';
}
print '</td>';
print '</tr>';

print '</table>';
print '</form>';
