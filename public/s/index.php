<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    public/s/index.php
 * \ingroup saturne
 * \brief   Public short URL entry point : /custom/saturne/public/s/?code redirects to the target of the code
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', 1);
}
if (!defined('NOLOGIN')) { // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) { // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) { // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Load Saturne environment
if (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} elseif (file_exists('../../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Load Saturne libraries
require_once __DIR__ . '/../../class/saturneredirection.class.php';

// Global variables definitions
global $db, $langs;

// The code is the whole query string (?code), parameters added by a messaging app (&fbclid=...) are ignored
$queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
$code        = (string) strtok($queryString, '&');

$saturneRedirection = new SaturneRedirection($db);

$toUrl = $saturneRedirection->fetchTargetUrl($code);
if ($toUrl === '') {
    $langs->load('errors');

    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    print $langs->transnoentities('ErrorRecordNotFound');
    exit;
}

// 302 and not 301 : the target of a printed QR code must stay editable, browsers keep a 301 forever
header('Location: ' . $toUrl, true, 302);
exit;
