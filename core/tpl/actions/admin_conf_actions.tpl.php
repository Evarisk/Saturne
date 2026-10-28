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
 * Global     : $conf, $db, $langs, $user
 * Parameters : $action
 * Variable   : $moduleName, $permissiontoread or $permissiontowrite
 */

// The settings are written with the write permission of the page, the read one is only a fallback
// for the pages that never declared the other
$permissionToWriteConf = $permissiontowrite ?? $permissiontoread ?? 0;

// The actions below receive the name of the constant to write: it must belong to the module
$moduleConstPrefix = dol_strtoupper($moduleName) . '_';

if ($action == 'set_mod' && $permissionToWriteConf) {
    $value      = GETPOST('value');
    $objectType = GETPOST('object_type');

    $confName = dol_strtoupper($moduleName . '_' . $objectType)  . '_ADDON';
    dolibarr_set_const($db, $confName, $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'update_mask' && $permissionToWriteConf) {
    $documentMaskConst = GETPOST('mask', 'alpha');
    $documentMask      = GETPOST('addon_value', 'alpha');

    if (dol_strlen($documentMask) < 1 || strpos($documentMaskConst, $moduleConstPrefix) !== 0) {
        setEventMessages($langs->trans('ErrorSavedConfig'), [], 'errors');
    } else {
        dolibarr_set_const($db, $documentMaskConst, $documentMask, 'chaine', 0, '', $conf->entity);
        setEventMessage('SavedConfig');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?module_name=' . $moduleName . (GETPOST('object_type', 'alpha') !== '' ? '&object_type=' . urlencode(GETPOST('object_type', 'alpha')) : ''));
        exit;
    }
}

// Switch of a module constant without javascript, or for a user who is not an administrator:
// ajax_constantonoff() and saturne_constant_onoff() then fall back on set_<CONST> / del_<CONST> links,
// handled here so the constant is set to zero instead of deleted. Only an administrator may write
// the constant of another entity
if ($permissionToWriteConf && preg_match('/^(set|del)_(' . preg_quote($moduleConstPrefix, '/') . '[A-Z0-9_]+)$/', $action, $constAction)) {
    $constEntity = (!empty($user->admin) && GETPOSTISSET('entity')) ? GETPOSTINT('entity') : $conf->entity;
    dolibarr_set_const($db, $constAction[2], ($constAction[1] == 'set' ? 1 : 0), 'chaine', 0, '', $constEntity);
    setEventMessage('SavedConfig');
}
