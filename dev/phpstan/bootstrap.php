<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * PHPStan bootstrap for the Saturne module.
 *
 * Defines Dolibarr constants and suppresses session/login/redirect side-effects
 * so that main.inc.php can be loaded without a running web server or database.
 * Falls back to stubs when Dolibarr core is not found (e.g. isolated CI).
 */

// Suppress Dolibarr bootstrap side-effects.
if (!defined('NOLOGIN')) {
    define('NOLOGIN', '1');
}
if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}
if (!defined('NOHTTPSREDIRECT')) {
    define('NOHTTPSREDIRECT', '1');
}
if (!defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', '1');
}

// Resolve Dolibarr root: two directories above this module (htdocs/).
$dolibarrRoot = realpath(__DIR__ . '/../../../../');
$mainIncPath  = $dolibarrRoot . '/main.inc.php';

// Always use stub mode: loading main.inc.php triggers DB connections and
// session handling which are incompatible with static analysis.
// PHPStan resolves Dolibarr class definitions via scanDirectories instead.
define('DOL_DOCUMENT_ROOT', $dolibarrRoot);
define('DOL_DATA_ROOT', dirname($dolibarrRoot) . '/documents');
define('DOL_URL_ROOT', '/');
define('DOL_VERSION', '0.0.0');
define('GETPOST_ALLOWHTML', 1);

include_once __DIR__ . '/stubs.php';

// tcpdf is delivered via composer — load the barcodes file so PHPStan can
// resolve TCPDF_BARCODES_2D and related classes (vendor/ is excluded from
// scanDirectories by excludePaths.analyseAndScan, so bootstrap is the only path).
$tcpdfBarcodesFile = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
if (file_exists($tcpdfBarcodesFile)) {
    require_once $tcpdfBarcodesFile;
}
