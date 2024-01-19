<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * Variable   : $objectclass, $permissiontoadd
 */

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
