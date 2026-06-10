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
 * \file    core/ajax/saturne_reorder_element.php
 * \ingroup saturne
 * \brief   Saturne ajax action to reorder elements (persist the new sibling order in position)
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

global $db, $user;

$action = GETPOST('action', 'aZ09');

if ($action == 'reorder_element') {
    $element = GETPOST('element', 'aZ09', 2);
    $ids     = GETPOST('ids', 'array', 2);

    if (empty($element) || !is_array($ids) || empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'InvalidParameters']);
        $db->close();
        exit;
    }

    $error    = 0;
    $position = 1;
    foreach ($ids as $id) {
        $object = fetchObjectByElement((int) $id, $element);

        if (!is_object($object) || empty($object->id)) {
            continue;
        }

        // Permission guard: the user must be allowed to write this element
        if (!saturne_user_can_write_element($user, $object, $element)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'NotEnoughPermissions']);
            $db->close();
            exit;
        }

        // Single-column update of position only (lighter than a full object update)
        if ($object->setValueFrom('position', $position, '', null, 'int', '', $user) < 0) {
            $error++;
        }
        $position++;
    }

    if ($error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'ReorderFailed']);
    } else {
        echo json_encode(['success' => true]);
    }
    $db->close();
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'UnknownAction']);
$db->close();
