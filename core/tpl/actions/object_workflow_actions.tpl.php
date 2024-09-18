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
 * \file    core/tpl/object/object_action_workflow.tpl.php
 * \ingroup saturne
 * \brief   Template page for object action workflow.
 */

/**
 * The following vars must be defined:
 * Global     : $langs, $user,
 * Parameters : $action, $backtopage, $id,
 * Objects    : $object, $signatory (optional), $document (optional)
 * Variable   : $permissiontoadd
 */

// Action to set status STATUS_LOCKED
if ($action == 'confirm_lock' && $permissiontoadd) {
    $result = $object->setLocked($user, false);
    if ($result > 0) {
        // Set locked OK.
        $urlToGo = str_replace('__ID__', $result, $backtopage);
        $urlToGo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urlToGo); // New method to autoselect project after a New on another form object creation.
        header('Location: ' . $urlToGo);
        exit;
    } elseif (!empty($object->errors)) { // Set locked KO.
        setEventMessages('', $object->errors, 'errors');
    } else {
        setEventMessages($object->error, [], 'errors');
    }
}

// Action to set status STATUS_ARCHIVED and replace signatory by text
if ($action == 'confirm_archive' && $permissiontoadd) {
    $result = $object->setArchived($user);
    if ($result > 0) {
        if (isset($signatory)) {
            $signatories = $signatory->fetchSignatory('', $object->id, $object->element);
            if (!empty($signatories) && $signatories > 0) {
                foreach ($signatories as $arrayRole) {
                    foreach ($arrayRole as $signatory) {
                        $signatory->signature = $langs->transnoentities('FileGenerated');
                        $signatory->update($user, false);
                    }
                }
            }
        }

        if (isset($document) && getDolGlobalInt('SATURNE_USE_CREATE_DOCUMENT_ON_ARCHIVE')) {
            $shouldRedirect = false;
            require_once __DIR__ . '/../documents/documents_action.tpl.php';
        }

        // Set Archived OK
        $urlToGo = str_replace('__ID__', $result, $backtopage);
        $urlToGo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urlToGo); // New method to autoselect project after a New on another form object creation
        header('Location: ' . $urlToGo);
        exit;
    } elseif (!empty($object->errors)) { // Set Archived KO
        setEventMessages('', $object->errors, 'errors');
    } else {
        setEventMessages($object->error, [], 'errors');
    }
}
