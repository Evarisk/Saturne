<?php

/* Copyright (C) 2024-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/actions/list_massactions.tpl.php
 * \ingroup saturne
 * \brief   Template page for list mass actions.
 */

/**
 * The following vars must be defined:
 * Global     : $db, $langs, $user,
 * Parameters : $action, $confirm, $massaction, $toselect
 * Objects    : $object
 * Variable   : $enableMassSignature (optional), $enableMassValidate (optional), $objectclass, $permissiontoadd
 */

// Validate mass action - only offered on lists that opted in with $enableMassValidate
$validateAsked = ($massaction == 'validate' || ($action == 'validate' && $confirm == 'yes'));
if ($validateAsked && !empty($enableMassValidate) && $permissiontoadd) {
    if (!empty($toselect)) {
        $nbOk      = 0;
        $nbSkipped = 0;
        $error     = 0;
        $objectTmp = new $objectclass($db);
        foreach ($toselect as $toSelectedID) {
            $result = $objectTmp->fetch($toSelectedID);
            if ($result > 0) {
                // Only a draft object can be validated, else a locked or archived one would go back to the validated status
                if ($objectTmp->status != $objectTmp::STATUS_DRAFT) {
                    $nbSkipped++;
                    continue;
                }

                $result = $objectTmp->validate($user);
                if ($result > 0) {
                    $nbOk++;
                } else {
                    setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                    $error++;
                    break;
                }
            } else {
                setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                $error++;
                break;
            }
        }

        if ($error == 0) {
            setEventMessages($langs->trans('RecordsValidated', $nbOk), []);
            if ($nbSkipped > 0) {
                setEventMessages($langs->trans('RecordsNotDraftSkipped', $nbSkipped), [], 'warnings');
            }
        }
    }
}

// Sign mass action - only offered on lists that opted in with $enableMassSignature
$signAsked = ($massaction == 'sign' || ($action == 'sign' && $confirm == 'yes'));
if ($signAsked && !empty($enableMassSignature) && $permissiontoadd) {
    if (!empty($toselect)) {
        require_once __DIR__ . '/../../../class/saturnesignature.class.php';

        $signatoryTmp  = new SaturneSignature($db);
        $userSignature = $signatoryTmp->fetchUserSignature($user->id);

        // Nothing can be signed in bulk until the user drew their signature once on their user card
        if (dol_strlen($userSignature) == 0) {
            setEventMessages($langs->trans('NoUserElectronicSignature'), [], 'errors');
        } else {
            $nbOk      = 0;
            $nbSkipped = 0;
            $error     = 0;
            $objectTmp = new $objectclass($db);
            foreach ($toselect as $toSelectedID) {
                $result = $objectTmp->fetch($toSelectedID);
                if ($result > 0) {
                    // Only a validated object is open to signature, a template is never signed
                    if ($objectTmp->status != $objectTmp::STATUS_VALIDATED || !empty($objectTmp->model)) {
                        $nbSkipped++;
                        continue;
                    }

                    // The list is homogeneous, $object carries the type the signatories were registered with
                    $result = $signatoryTmp->signAsUser($user, $objectTmp->id, $object->element, $userSignature);
                    if ($result > 0) {
                        $nbOk++;
                    } elseif ($result == 0) {
                        // The user is not a signatory of this object, or already signed it
                        $nbSkipped++;
                    } else {
                        setEventMessages($signatoryTmp->error, $signatoryTmp->errors, 'errors');
                        $error++;
                        break;
                    }
                } else {
                    setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                    $error++;
                    break;
                }
            }

            if ($error == 0) {
                setEventMessages($langs->trans('RecordsSigned', $nbOk), []);
                if ($nbSkipped > 0) {
                    setEventMessages($langs->trans('RecordsNotSignableSkipped', $nbSkipped), [], 'warnings');
                }
            }
        }
    }
}

// Sign for an attendant mass action - hands the selection over to the mass signature page
if ($massaction == 'signattendant' && !empty($enableMassSignature) && $permissiontoadd && !empty($toselect)) {
    // The filters are held in session, the list restores them on its own when coming back
    $backToList        = $_SERVER['PHP_SELF'] . '?restore_lastsearch_values=1' . (GETPOSTISSET('object_type') ? '&object_type=' . urlencode(GETPOST('object_type', 'aZ09')) : '');
    $massSignatureUrl  = dol_buildpath('/saturne/view/saturne_mass_signature.php', 1);
    $massSignatureUrl .= '?module_name=' . urlencode($object->module) . '&object_type=' . urlencode($object->element);
    $massSignatureUrl .= '&ids=' . implode(',', array_map('intval', $toselect));
    $massSignatureUrl .= '&backtopage=' . urlencode($backToList);

    header('Location: ' . $massSignatureUrl);
    exit;
}

// Archive mass action
if (($massaction == 'archive' || ($action == 'archive' && $confirm == 'yes')) && $permissiontoadd) {
    if (!empty($toselect)) {
        $nbOk      = 0;
        $error     = 0;
        $objectTmp = new $objectclass($db);
        foreach ($toselect as $toSelectedID) {
            $result = $objectTmp->fetch($toSelectedID);
            if ($result > 0) {
                $result = $objectTmp->setArchived($user, false);
                if ($result > 0) {
                    $nbOk++;
                } else {
                    setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                    $error++;
                    break;
                }
            } else {
                setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                $error++;
                break;
            }
        }

        if ($error == 0) {
            setEventMessages($langs->trans('RecordsArchived', $nbOk), []);
        }
    }
}

// Unarchive mass action
if (($massaction == 'unarchive' || ($action == 'unarchive' && $confirm == 'yes')) && $permissiontoadd) {
    if (!empty($toselect)) {
        $nbOk      = 0;
        $error     = 0;
        $objectTmp = new $objectclass($db);
        foreach ($toselect as $toSelectedID) {
            $result = $objectTmp->fetch($toSelectedID);
            if ($result > 0) {
                $result = $objectTmp->setUnarchived($user, false);
                if ($result > 0) {
                    $nbOk++;
                } elseif ($result < 0) {
                    // 0 = élément non archivé (garde-fou) : ignoré silencieusement, pas bloquant
                    setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                    $error++;
                    break;
                }
            } else {
                setEventMessages($objectTmp->error, $objectTmp->errors, 'errors');
                $error++;
                break;
            }
        }

        if ($error == 0) {
            setEventMessages($langs->trans('RecordsUnarchived', $nbOk), []);
        }
    }
}
