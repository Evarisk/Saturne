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
 * \file    core/ajax/saturne_save_list_layout.php
 * \ingroup saturne
 * \brief   AJAX endpoint to save/reset a list's per-user column layout (order + widths) in llx_user_param
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once __DIR__ . '/../../lib/saturne_functions.lib.php';

header('Content-Type: application/json');

global $conf, $db, $user;

if (empty($user->id)) {
    echo json_encode(['success' => false, 'error' => 'NotLoggedIn']);
    exit;
}

$action = GETPOST('action', 'aZ09');
$listId = GETPOST('list_id', 'alphanohtml');
if (empty($listId)) {
    echo json_encode(['success' => false, 'error' => 'MissingListId']);
    exit;
}

$key = saturne_list_layout_param($listId);

if ($action === 'save') {
    $raw     = htmlspecialchars_decode(GETPOST('layout', 'restricthtml'), ENT_QUOTES);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        echo json_encode(['success' => false, 'error' => 'InvalidLayout']);
        exit;
    }

    $clean = ['order' => [], 'widths' => []];
    if (!empty($decoded['order']) && is_array($decoded['order'])) {
        foreach ($decoded['order'] as $colKey) {
            $colKey = preg_replace('/[^A-Za-z0-9_]/', '', (string) $colKey);
            if ($colKey !== '') {
                $clean['order'][] = $colKey;
            }
        }
    }
    if (!empty($decoded['widths']) && is_array($decoded['widths'])) {
        foreach ($decoded['widths'] as $colKey => $width) {
            $colKey = preg_replace('/[^A-Za-z0-9_]/', '', (string) $colKey);
            $width  = (int) $width;
            if ($colKey !== '' && $width > 0 && $width < 2000) {
                $clean['widths'][$colKey] = $width;
            }
        }
    }

    $res = dol_set_user_param($db, $conf, $user, [$key => json_encode($clean)]);
    echo json_encode($res > 0 ? ['success' => true] : ['success' => false, 'error' => 'SaveFailed']);
    exit;
}

if ($action === 'reset') {
    $res = dol_set_user_param($db, $conf, $user, [$key => '']);
    echo json_encode($res > 0 ? ['success' => true] : ['success' => false, 'error' => 'ResetFailed']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'UnknownAction']);
