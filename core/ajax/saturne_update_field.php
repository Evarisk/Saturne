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
    $fkElement = GETPOST('fk_element', 'alpha', 2);
    $type      = GETPOST('type', 'alpha', 2);
    $object = fetchObjectByElement($fkElement, $element);
    $format = '';
    $value  =  '';
    if ($type == 'datepicker') {
        $format    = 'date';
        $timestamp = GETPOSTINT('fieldValue', 2);
        $value = ($timestamp / 1000);
    }

    $object->setValueFrom($field, $value, '', null, $format);
}
