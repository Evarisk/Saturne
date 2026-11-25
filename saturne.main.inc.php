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
 *  \file       saturne.main.inc.php
 *  \ingroup    saturne
 *  \brief      Saturne main file
 */

// Entity of a public page: no session carries it, so with MultiCompany master.inc.php keeps entity 1 unless DOLENTITY is
// defined before main.inc.php, and the module check below would then redirect when the module is off in entity 1. Same
// choice as the public pages of Dolibarr (public/onlinesign/newonlinesign.php); GETPOST is not loaded yet. A page without
// entity in its URL keeps the default
if (defined('NOLOGIN') && !defined('DOLENTITY')) {
    $publicPageEntity = !empty($_GET['entity']) ? $_GET['entity'] : ($_POST['entity'] ?? '');
    if (is_numeric($publicPageEntity) && (int) $publicPageEntity > 0) {
        define('DOLENTITY', (int) $publicPageEntity);
    }
    unset($publicPageEntity);
}

// Load Dolibarr environment
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
$res = 0;
if (!empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
    $res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'] . '/main.inc.php';
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp  = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i    = strlen($tmp) - 1;
$j    = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . '/main.inc.php')) {
    $res = @include substr($tmp, 0, ($i + 1)) . '/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . '/main.inc.php')) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . '/main.inc.php';
}
// Try main.inc.php using relative path
if (!$res && file_exists('../main.inc.php')) {
    $res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
    $res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
    $res = @include '../../../main.inc.php';
}
if (!$res && file_exists('../../../../main.inc.php')) {
    $res = @include '../../../../main.inc.php';
}
if (!$res) {
    die('Include of main fails');
}

require_once __DIR__ . '/lib/saturne_functions.lib.php';

saturne_check_modules_enabled();
