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
 * Variable   : $enableMassValidate (optional), $objectclass, $permissiontoadd
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
