<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/attendants/attendants_table_view.tpl.php
 * \ingroup saturne
 * \brief   Template page for attendants table
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user,
 * Parameters : $attendantTableMode, $objectType, $documentType, $id, $backtopage,
 * Objects    : $thirdparty, $object
 * Variable   : $signatoryRole, $signatories, $moduleNameLowerCase, $permissiontoadd
 */

print load_fiche_titre($langs->trans($attendantTableMode == 'advanced' ? $signatoryRole : 'Attendants'), '', '');

if (!empty($signatories) || (empty($signatories) && $object->status == $object::STATUS_DRAFT) || ($object->status == $object::STATUS_VALIDATED && getDolGlobalInt('SATURNE_ATTENDANTS_ADD_STATUS_MANAGEMENT'))) {
    print '<table class="border centpercent tableforfield">';

    print '<tr class="liste_titre">';
    print '<td>' . img_picto('', 'company') . ' ' . $langs->trans('ThirdParty') . '</td>';
    print '<td class="minwidth300 widthcentpercentminusx">' . img_picto('', 'user') . ' ' . $langs->trans('User') . ' | ' . img_picto('', 'contact') . ' ' . $langs->trans('Contacts') . '</td>';
    if ($attendantTableMode == 'simple') {
        print '<td class="center ' . ($conf->browser->layout != 'classic' && $object->status > $object::STATUS_DRAFT ? 'hidden': '') . '">' . $langs->trans('Role') . '</td>';
    }
    print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
    print '<td class="center">' . $langs->trans('SendMailDate') . '</td>';
    print '<td class="' . ($conf->browser->layout != 'classic' ? 'hidden': '') . '">' . $langs->trans('SignatureDate') . '</td>';
    print '<td class="center">' . $langs->trans('Status') . '</td>';
    print '<td class="center">' . $langs->trans('Attendance') . '</td>';
    print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
    print '</tr>';
}

if (is_array($signatories) && !empty($signatories) && $signatories > 0) {
    foreach ($signatories as $element) {
        $usertmp = new User($db);
        $contact = new Contact($db);
        print '<tr class="oddeven" data-signatory-id="' . $element->id . '"><td class="minwidth200">';
        if ($element->element_type == 'socpeople') {
            $contact->fetch($element->element_id);
            $thirdparty->fetch($contact->fk_soc);
            if ($conf->browser->layout != 'classic') {
                print img_picto('', 'company') . ' ' . $thirdparty->name;
            } else {
                print $thirdparty->getNomUrl(1);
            }
        } else {
            $usertmp->fetch($element->element_id);
            if ($usertmp->contact_id > 0) {
                $contact->fetch($usertmp->contact_id);
                $thirdparty->fetch($contact->fk_soc);
                if ($conf->browser->layout != 'classic') {
                    print img_picto('', 'company') . ' ' . $thirdparty->name;
                } else {
                    print $thirdparty->getNomUrl(1);
                }
            } else {
                print img_picto('', 'company') . ' ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            }
        }
        print '</td><td>';
        print $element->getNomUrl(-1, 'blank');
        if (!empty($element->job)) {
            print ' - ' . $element->job;
        }
        if ($attendantTableMode == 'simple') {
            print '</td><td class="center ' . ($conf->browser->layout != 'classic' && $object->status > $object::STATUS_DRAFT ? 'hidden': '') . '">';
            print $langs->transnoentities($element->role);
        }
        print '</td><td class="center copy-signatureurl-container">';
        if ($object->status == $object::STATUS_VALIDATED) {
            if ((!$user->rights->$moduleNameLowerCase->$objectType->read && $user->rights->$moduleNameLowerCase->assignedtome->$objectType && ($element->element_id == $user->id || $element->element_id == $user->contact_id)) || $permissiontoadd) {
                $signatureUrl = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $element->signature_url . '&entity=' . $conf->entity . '&module_name=' . $moduleNameLowerCase . '&object_type=' . $object->element . '&document_type=' . $documentType, 3);
                print '<a href=' . $signatureUrl . ' target="_blank"><div class="wpeo-button button-blue" style="' . ($conf->browser->layout != 'classic' ? 'font-size: 25px;': '') . '"><i class="fas' . (($element->status == SaturneSignature::STATUS_SIGNED) ? ' fa-eye' : ' fa-signature') . '"></i></div></a>';
                print ' <i class="fas fa-clipboard copy-signatureurl" data-signature-url="' . $signatureUrl . '" style="color: #666;' .  ($conf->browser->layout != 'classic' ? 'display: none;': '') . '"></i>';
                print '<span class="copied-to-clipboard" style="display: none;">' . '  ' . $langs->trans('CopiedToClipboard') . '</span>';
            }
        }
        print '</td><td class="center">';
        if ($object->status == $object::STATUS_VALIDATED && $element->signature == '') {
            if (dol_strlen($element->email)) {
                print dol_print_date($element->last_email_sent_date, 'dayhour', 'tzuser');
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                $nbEmailSent = 0;
                $actionComm  = new ActionComm($db);
                $actionComms = $actionComm->getActions(0, $object->id, $object->element . '@' . $moduleNameLowerCase, " AND code = 'AC_SATURNE_SIGNATURE_PENDING_SIGNATURE'");
                if (is_array($actionComms) && !empty($actionComms)) {
                    foreach ($actionComms as $actionComm) {
                        if ($actionComm->socpeopleassigned[$element->element_id]['id'] == $element->element_id) {
                            $nbEmailSent++;
                        }
                    }
                }
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="send_email">';
                print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
                print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                print '<button type="submit" class="signature-email wpeo-button button-blue" style="' . ($conf->browser->layout != 'classic' ? 'font-size: 20px;': '') . '" value="' . $element->id . '">';
                print '<i class="fas fa-paper-plane"></i>';
                print '</button>';
                if ($nbEmailSent > 0) {
                    print ' <span class="badge badge-info">' . $nbEmailSent . '</span>';
                }
                print '</form>';
            } else {
                print '<div class="wpeo-button button-grey wpeo-tooltip-event" style="' . ($conf->browser->layout != 'classic' ? 'font-size: 20px;': '') . '" aria-label="' . $langs->trans('NoEmailSet', $langs->trans($element->role) . ' ' . strtoupper($element->lastname) . ' ' . $element->firstname) . '"><i class="fas fa-paper-plane"></i></div>';
            }
        }
        print '</td><td class="' . ($conf->browser->layout != 'classic' ? 'hidden': '') . '">';
        print dol_print_date($element->signature_date, 'dayhour', 'tzuser');
        print '</td><td class="center">';
        print $element->getLibStatut(5);
        print '</td><td class="center">';
        switch ($element->attendance) {
            case 1:
                $cssButton = '';
                $userIcon  = 'fa-user-clock';
                break;
            case 2:
                $cssButton = 'button-red';
                $userIcon  = 'fa-user-slash';
                break;
            default:
                $cssButton = 'button-green';
                $userIcon  = 'fa-user';
                break;
        }

        if ($object->status <= $object::STATUS_VALIDATED && $permissiontoadd) {
            print '<div class="wpeo-dropdown dropdown-right attendance-container">';
            print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
            print '<div class="dropdown-toggle" style="' . ($conf->browser->layout != 'classic' ? 'font-size: 20px;': '') . '"><span class="wpeo-button ' . $cssButton . '"><i class="fas ' . $userIcon . ' button-icon"></i><i class="fas fa-edit button-add"></i></span></div>';
            print '<ul class="saturne-dropdown-content wpeo-gridlayout grid-3">';
            print '<li class="dropdown-item set-attendance" style="padding: 0;" value="0"><div class="wpeo-button button-green"><i class="fas fa-user"></i></div></li>';
            print '<li class="dropdown-item set-attendance" style="padding: 0;" value="1"><div class="wpeo-button"><i class="fas fa-user-clock"></i></div></li>';
            print '<li class="dropdown-item set-attendance" style="padding: 0;" value="2"><div class="wpeo-button button-red"><i class="fas fa-user-slash"></i></div></li>';
            print '</ul>';
            print '</div>';
        } else {
            print '<div class="dropdown-toggle wpeo-button ' . $cssButton . '"><i class="fas ' . $userIcon . '"></i></div>';
        }
        print '</td><td class="center">';
        if (($object->status == $object::STATUS_DRAFT || ($object->status == $object::STATUS_VALIDATED && getDolGlobalInt('SATURNE_ATTENDANTS_ADD_STATUS_MANAGEMENT'))) && $permissiontoadd) {
            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="delete_attendant">';
            print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
            print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
            print '<button type="submit" name="deleteAttendant" id="deleteAttendant" class="attendant-delete wpeo-button button-grey" value="' . $element->id . '">';
            print '<i class="fas fa-trash"></i>';
            print '</button>';
            print '</form>';
        }
        print '</td>';
        print '</tr>';
        $alreadyAddedSignatories[$element->element_type][$element->element_id] = $element->element_id;
    }

    require __DIR__ . '/attendants_table_add_view.tpl.php';
} else {
    print '<div class="opacitymedium">' . $langs->trans('NoAttendants' . ($object->status == $object::STATUS_VALIDATED && getDolGlobalInt('SATURNE_ATTENDANTS_ADD_STATUS_MANAGEMENT') ? 'WithStatusManagement' : '')) . '</div><br>';

    require __DIR__ . '/attendants_table_add_view.tpl.php';
}
