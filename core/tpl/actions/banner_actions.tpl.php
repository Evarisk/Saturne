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
 * \file    core/tpl/actions/banner_actions.tpl.php
 * \ingroup saturne
 * \brief   Template page for banner actions
 */

/**
 * The following vars must be defined :
 * Global     : $user
 * Parameters : $action
 * Objects    : $object
 * Variable   : $permissiontoadd
 */

if (($action == 'set_societe' || $action == 'set_project') && $permissiontoadd) {
    $objectKey = explode('_', $action);
    $objectKey = $objectKey[1] . '_key';
    $object->setValueFrom(GETPOST($objectKey), GETPOST(GETPOST($objectKey), 'int'), '', '', 'int', '', $user, strtoupper($object->element) . '_MODIFY');
}
