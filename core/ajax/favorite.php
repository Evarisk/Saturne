<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/ajax/favorite.php
 * \ingroup saturne
 * \brief   Saturne ajax action favorite
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

global $db, $user, $conf, $langs;

require_once __DIR__ . '../../../lib/object.lib.php';

$action = GETPOST('action', 'aZ09');

if ($action == 'toggle_favorite') {
    $isFile = GETPOST('isFile', 'int');
    $fileId = GETPOST('fileId', 'int');
    $isFavorite = GETPOST('isFavorite', 'int');

    if ($isFile) {
        require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

        $file = new EcmFiles($db);
        $file->fetch($fileId);
    } else {
        require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';

        $file = new Link($db);
        $file->fetch($fileId);
    }
    $file->array_options['favorite'] = $isFavorite;
    $result = $file->insertExtraFields();
    if ($result < 0) {
        echo json_encode(['error' => 1, 'message' => $langs->trans('ErrorSavingFavorite')]);
    } else {
        echo json_encode(['error' => 0, 'message' => $langs->trans('FavoriteSaved')]);
    }
}