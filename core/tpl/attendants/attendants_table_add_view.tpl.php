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
 * \file    core/tpl/attendants/attendants_table_add_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for add attendants.
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $langs, $user,
 * Parameters : $moduleNane, $documentType, $attendantTableMode, $id,
 * Objects    : $object, $formcompany, $form,
 * Variable   : $signatoryRole, $alreadyAddedSignatories, $permissiontoadd
 */

if (($object->status == $object::STATUS_DRAFT || ($object->status == $object::STATUS_VALIDATED && getDolGlobalInt('SATURNE_ATTENDANTS_ADD_STATUS_MANAGEMENT'))) && $permissiontoadd) {
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add_attendant">';
    if ($attendantTableMode == 'advanced') {
        print '<input type="hidden" name="attendant_role" value="' . $signatoryRole . '">';
    }
    if (!empty($backtopage)) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }

    print '<tr class="oddeven"><td>';
    $selectedCompany = GETPOSTISSET('newcompany' . (($attendantTableMode == 'advanced') ? $signatoryRole : '')) ? GETPOST('newcompany' . (($attendantTableMode == 'advanced') ? $signatoryRole : ''), 'int') : (empty($object->socid) ? 0 : $object->socid);
    $moreparam       = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element) . '&document_type=' . $documentType . '&attendant_table_mode=' . urlencode($attendantTableMode);
    $moreparam       .= '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreparam);
    $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany' . (($attendantTableMode == 'advanced') ? $signatoryRole : ''), '', 0, $moreparam, 'minwidth300imp');
    print '</td>';
    print '<td class="minwidth300 widthcentpercentminusx">';
    if ($selectedCompany <= 0) {
        print img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers('', 'attendant' . (($attendantTableMode == 'advanced') ? $signatoryRole : '_') . 'user', 1, $alreadyAddedSignatories['user'], 0, '', '', $conf->entity, 0, 0, '', 0, '', 'minwidth200 widthcentpercentminusx maxwidth300') . '<br>';
    }
    print img_object('', 'contact', 'class="pictofixedwidth"') . $form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), GETPOST('contactID'), 'attendant' . (($attendantTableMode == 'advanced') ? $signatoryRole : '_') . 'contact', 1, $alreadyAddedSignatories['socpeople'], '', 1, 'minwidth200 widthcentpercentminusx maxwidth300');
    if (!empty($selectedCompany) && $selectedCompany > 0 && $user->rights->societe->creer) {
        $newcardbutton = '<a href="' . DOL_URL_ROOT . '/contact/card.php?socid=' . $selectedCompany . '&action=create' . $moreparam . urlencode('&newcompany' . (($attendantTableMode == 'advanced') ? $signatoryRole : '') . '=' . GETPOST('newcompany' . (($attendantTableMode == 'advanced') ? $signatoryRole : '')) . '&contactID=&#95;&#95;ID&#95;&#95;') . '" title="' . $langs->trans('NewContact') . '"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
        print $newcardbutton;
    }
    print '</td>';
    if ($attendantTableMode == 'simple') {
        print '<td class="center">';
        print saturne_select_dictionary('attendant_role','c_' . $object->element . '_attendants_role', 'ref');
        print '</td>';
    }
    print '<td colspan="' . ($conf->browser->layout != 'classic' ? 4 : 5) . '"></td>';
    print '<td class="center">';
    print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i></button>';
    print '</td></tr>';
    print '</table>';
    print '</form>';
}
