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
    $module  = GETPOST('module', 'aZ09', 2);
    $element = GETPOST('element', 'aZ09', 2);
    $class   = GETPOST('objclass', 'aZ09', 2);
    $ids     = GETPOST('ids', 'array', 2);

    if (empty($module) || empty($element) || empty($class) || !is_array($ids) || empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'InvalidParameters']);
        $db->close();
        exit;
    }

    // Permission guard: the user must be allowed to write this element type
    if (!$user->hasRight($module, $element, 'write')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'NotEnoughPermissions']);
        $db->close();
        exit;
    }

    // Resolve the element class from the (sanitized, alphanumeric) module/element pair.
    // fetchObjectByElement() can't be used here: SaturneElement-based elements are not
    // registered in getElementProperties, so it would fail to resolve the class.
    $classFile = DOL_DOCUMENT_ROOT . '/custom/' . $module . '/class/' . $element . '.class.php';
    if (!is_file($classFile)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'InvalidElement']);
        $db->close();
        exit;
    }
    require_once $classFile;
    if (!class_exists($class) || !is_subclass_of($class, 'SaturneElement')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'InvalidClass']);
        $db->close();
        exit;
    }

    $error    = 0;
    $position = 1;
    foreach ($ids as $id) {
        $object = new $class($db);
        if ($object->fetch((int) $id) <= 0) {
            continue;
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
