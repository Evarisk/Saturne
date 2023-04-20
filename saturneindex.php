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

// Load Saturne environment
if (file_exists('saturne.main.inc.php')) {
	require_once __DIR__ . '/saturne.main.inc.php';
} elseif (file_exists('../saturne.main.inc.php')) {
	require_once __DIR__ . '/../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

$moduleName = 'Saturne';
$moduleNameLowerCase = strtolower($moduleName);

require_once __DIR__ . '/core/tpl/index/index_view.tpl.php';
