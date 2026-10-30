<?php

/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    utils/viewimage.php
 * \ingroup saturne
 * \brief   Wrapper to show images
 */

if (!defined('NOREQUIREUSER')) {
    define('NOREQUIREUSER', '1');
}
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
// This means this output page does not require to be logged
if (!defined('NOLOGIN')) {
    define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', '1');
}
// Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOIPCHECK')) {
    define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Global variables definitions
global $conf, $db;

// Get parameters
$file       = GETPOST('file');
// Do not use urldecode here ($_GET are already decoded by PHP)
$modulepart = GETPOST('modulepart', 'aZ09');
$entity     = GETPOSTINT('entity');

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Security check
if (empty($modulepart) || empty($file)) {
    accessforbidden('Bad link. Bad value for parameter modulepart or file', 0, 0, 1);
}

// Security: this wrapper is served without login, only for the directories whose images the public pages
// of the Saturne modules show. Anything else of the data directory (invoices, logs, other modules) is refused
$publicModuleParts = ['digiquali', 'digiriskdolibarr', 'easyurl', 'category', 'produit', 'product_batch', 'mycompany'];
if (!in_array($modulepart, $publicModuleParts, true)) {
    accessforbidden('Error: This modulepart is not served by the public image wrapper.', 0, 0, 1);
}
// The company directory also holds its documents: only its logos are public, as in the viewimage.php of the core
if ($modulepart == 'mycompany' && !preg_match('/^\/?logos\//', $file)) {
    accessforbidden('Error: Only the logos of the company are served by the public image wrapper.', 0, 0, 1);
}

/*
 * View
 */

// Define mime type
$type = dol_mimetype($file);

// Security: Delete string ../ or ..\ into $file
// Replace '... or more' with '..'
$file = preg_replace('/\.\.+/', '..', $file);
$file = str_replace('../', '/', $file);
$file = str_replace('..\\', '/', $file);

// Security: images only. dolIsAllowedForPreview() also lets through PDF and plain text (logs, configuration,
// sources), and SVG may carry scripts
if (!in_array(dol_mimetype($file, '', 1), ['avif', 'bmp', 'gif', 'jpeg', 'png', 'tiff', 'webp'], true)) {
    accessforbidden('This file is not qualified for preview', 0, 0, 1);
}

$fileName = basename($file);
$fullName = DOL_DATA_ROOT . '/' . ($entity > 1 ? $entity . '/' : '') . $modulepart . '/' . $file;

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers
if (preg_match('/\.\./', $fullName) || preg_match('/[<>|]/', $fullName)) {
    dol_syslog("Refused to deliver file " . $fullName);
    print "ErrorFileNameInvalid: " . dol_escape_htmltag($file);
    exit;
}

// Open and return file
clearstatcache();

// Output files on browser
dol_syslog("viewimage.php return file $fullName filename = $fileName content-type = $type");

// This test is to avoid error images when image is not available (for example thumbs)
if (!dol_is_file($fullName)) {
    $fullName = DOL_DOCUMENT_ROOT . '/public/theme/common/nophoto.png';
}

// Permissions are ok and file found, so we return it
if ($type) {
    top_httphead($type);
} else {
    top_httphead('image/png');
}
header('Content-Disposition: inline; filename="' . $fileName . '"');

$fullPathFileOsencoded = dol_osencode($fullName);

readfile($fullPathFileOsencoded);
