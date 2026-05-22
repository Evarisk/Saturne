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
 * \file    core/ajax/saturne_update_field.php
 * \ingroup saturne
 * \brief   Saturne ajax action update field
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

global $conf, $db, $user, $langs;

$action = GETPOST('action', 'aZ09');

if ($action == 'update_field') {
    $field     = GETPOST('field', 'alpha', 2);
    $element   = GETPOST('element', 'alpha', 2);
    $fkElement = GETPOSTINT('fk_element', 2);
    $type      = GETPOST('type', 'alpha', 2);

    $object = fetchObjectByElement($fkElement, $element);

    // Guard: a real object must be loaded and the field must belong to it (prevents writing arbitrary columns)
    if (!is_object($object) || empty($object->id) || !isset($object->fields[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'InvalidObjectOrField']);
        $db->close();
        exit;
    }

    // Permission guard: the user must be allowed to write this element
    if (!saturne_user_can_write_element($user, $object, $element)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'NotEnoughPermissions']);
        $db->close();
        exit;
    }

    $format = '';
    switch ($type) {
        case 'datepicker':
            $format = 'date';
            $value  = GETPOSTINT('fieldValue', 2) / 1000;
            break;
        case 'number':
            $value = price2num(GETPOST('fieldValue', 'alphanohtml', 2));
            break;
        case 'select':
        case 'text':
        default:
            $value = GETPOST('fieldValue', 'restricthtml', 2);
            break;
    }

    $result = $object->setValueFrom($field, $value, '', null, $format, '', $user);

    if ($result < 0) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $object->error ?: 'UpdateFailed']);
    } else {
        echo json_encode(['success' => true]);
    }
    $db->close();
    exit;
}
