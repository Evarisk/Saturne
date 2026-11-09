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
 * \file    s/index.php
 * \ingroup saturne
 * \brief   Public short URL entry point : /custom/saturne/s/?code redirects to the target of the code
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
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Load Saturne libraries
require_once __DIR__ . '/../class/saturneredirection.class.php';

// Global variables definitions
global $db, $langs;

// The code is the whole query string (?code), parameters added by a messaging app (&fbclid=...) are ignored
$queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
$code        = (string) strtok($queryString, '&');

$saturneRedirection = new SaturneRedirection($db);

$toUrl = $saturneRedirection->fetchTargetUrl($code);
if ($toUrl === '') {
    // Seen on a phone after a scan: a standalone page, readable without the CSS of Dolibarr, in the language of the browser
    $langs->load('saturne@saturne');

    $message = dol_escape_htmltag($langs->transnoentities('ShortUrlNotFound'));

    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    print '<!DOCTYPE html><html lang="' . dol_escape_htmltag(substr($langs->defaultlang, 0, 2)) . '"><head><meta charset="utf-8">';
    print '<meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">';
    print '<title>' . $message . '</title>';
    print '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;';
    print 'background:#f4f5f7;color:#333}p{max-width:20em;margin:1em;padding:1.5em;border-radius:8px;background:#fff;';
    print 'text-align:center;font-size:1.2em;box-shadow:0 1px 4px rgba(0,0,0,.1)}</style></head>';
    print '<body><p>' . $message . '</p></body></html>';
    exit;
}

// 302 and not 301 : the target of a printed QR code must stay editable, browsers keep a 301 forever
header('Location: ' . $toUrl, true, 302);
exit;
