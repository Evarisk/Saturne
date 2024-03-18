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
 * \file    core/tpl/actions/admin_conf_actions.tpl.php
 * \ingroup saturne
 * \brief   Template page for admin conf actions
 */

/**
 * The following vars must be defined :
 * Global     : $conf, $db, $langs
 * Parameters : $action
 * Variable   : $moduleName, $permissiontoread
 */

if ($action == 'set_mod' && $permissiontoread) {
    $value      = GETPOST('value');
    $objectType = GETPOST('object_type');

    $confName = dol_strtoupper($moduleName . '_' . $objectType)  . '_ADDON';
    dolibarr_set_const($db, $confName, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'update_mask' && $permissiontoread) {
    $documentMaskConst = GETPOST('mask', 'alpha');
    $documentMask      = GETPOST('addon_value', 'alpha');

    if (dol_strlen($documentMask) < 1) {
        setEventMessages($langs->trans('ErrorSavedConfig'), [], 'errors');
    } else {
        dolibarr_set_const($db, $documentMaskConst, $documentMask, 'chaine', 0, '', $conf->entity);
        setEventMessage('SavedConfig');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName);
        exit;
    }
}
