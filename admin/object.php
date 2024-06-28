<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/object/object.php
 * \ingroup saturne
 * \brief   Saturne object config page.
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Get module parameters
$moduleName = GETPOST('module_name', 'alpha');
$objectType = GETPOST('object_type', 'alpha');

// If the previous action is from extrafields, the return value is PHP_SELF without querys
// So we need to fill moduleName and objectType using the previous url (HTTP_REFERER)
if (empty($moduleName) && empty($objectType)) {
    $lastUrl      = $_SERVER['HTTP_REFERER'];
    $lastUrlArray = parse_url($lastUrl);

    if (is_array($lastUrlArray) && isset($lastUrlArray['query'])) {
        parse_str($lastUrlArray['query'], $lastUrlQuerys);

        if (isset($lastUrlQuerys['module_name']) && isset($lastUrlQuerys['object_type'])) {
            $moduleName = $lastUrlQuerys['module_name'];
            $objectType = $lastUrlQuerys['object_type'];
        }
    }
}

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '.lib.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$className = ucfirst($objectType);
$object    = new $className($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$objectType . 'admin']); // Note that conf->hooks_modules contains array.

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', $moduleName);
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head = $preHead();
print dol_get_fiche_head($head, $object->element, $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

require_once __DIR__ . '/../core/tpl/admin/object/object_numbering_module_view.tpl.php';

require_once __DIR__ . '/../core/tpl/admin/object/object_const_view.tpl.php';

if ($object->isextrafieldmanaged > 0) {
    require_once __DIR__ . '/../core/tpl/admin/object/object_extrafields_view.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
