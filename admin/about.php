<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/about.php
 * \ingroup saturne
 * \brief   About page of module Saturne.
 */

// Load Saturne environment.
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Get module parameters.
$moduleName          = GETPOST('module_name', 'alpha');
$moduleNameLowerCase = strtolower($moduleName);

// Load Module Libraries.
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '.lib.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/core/modules/mod' . $moduleName . '.class.php';

// Global variables definitions.
global $db, $langs, $user;

// Load translation files required by the page.
saturne_load_langs(['admin']);

// Initialize technical objects.
$className = 'mod' . $moduleName;
$modModule = new $className($db);

// Get parameters.
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user.
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * View
 */

$title    = $langs->trans('ModuleAbout', $moduleName);
$help_url = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title, $help_url);

// Subheader.
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

// Configuration header.
$preHead = $moduleNameLowerCase . '_admin_prepare_head';
$head = $preHead();
print dol_get_fiche_head($head, 'about', $title, -1, $moduleNameLowerCase . '_color@' . $moduleNameLowerCase);

print $modModule->getDescLong();

// Page end.
print dol_get_fiche_end();
llxFooter();
$db->close();
