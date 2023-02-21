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
 *	\file       saturneindex.php
 *	\ingroup    saturne
 *	\brief      Home page of saturne top menu
 */

// Load DoliMeet environment
if (file_exists('saturne.main.inc.php')) {
    require_once __DIR__ . '/saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Libraries
require_once __DIR__ . '/core/modules/modSaturne.class.php';

// Global variables definitions
global $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Initialize technical objects
$modSaturne = new modSaturne($db);

// Security check
$permissiontoread = $user->rights->saturne->lire;
saturne_check_access($permissiontoread);

/*
 * View
 */

$title   = $langs->trans('ModuleArea', 'Saturne');
$helpUrl = 'FR:Module_Saturne';

saturne_header(0, '', $title . ' ' . $modSaturne->version, $helpUrl);

print load_fiche_titre($title . ' ' . $modSaturne->version, '', 'saturne_color.png@saturne');

// End of page
llxFooter();
$db->close();
