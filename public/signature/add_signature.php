<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    public/signature/add_signature.php
 * \ingroup saturne
 * \brief   Public page to add signature
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

// Get module parameters
$moduleName   = GETPOST('module_name', 'alpha');
$objectType   = GETPOST('object_type', 'alpha');
$documentType = GETPOST('document_type', 'alpha');

$moduleNameLowerCase = strtolower($moduleName);

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../class/saturnesignature.class.php';

// Load Module libraries
require_once __DIR__ . '/../../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
$fileExists = file_exists('../../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . strtolower($documentType) . '.class.php');
if ($fileExists && GETPOSTISSET('document_type')) {
    require_once __DIR__ . '/../../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . strtolower($documentType) . '.class.php';
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$trackID = GETPOST('track_id', 'alpha');
$entity  = GETPOST('entity');
$action  = GETPOST('action', 'aZ09');
$source  = GETPOST('source', 'aZ09');

// Initialize technical objects
$classname = ucfirst($objectType);
$object    = new $classname($db);
if (GETPOSTISSET('document_type') && $fileExists) {
    $document = new $documentType($db);
}
$signatory = new SaturneSignature($db, $moduleNameLowerCase, $objectType);
$user      = new User($db);

$hookmanager->initHooks([$objectType . 'publicsignature', 'saturnepublicsignature', 'saturnepublicinterface', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

$signatory->fetch(0, '', ' AND signature_url =' . "'" . $trackID . "'");
$object->fetch($signatory->fk_object);

$upload_dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1];

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Actions add_signature, builddoc, remove_file
    require_once __DIR__ . '/../../core/tpl/actions/signature_actions.tpl.php';
}

/*
 * View
 */

$title  = $langs->trans('Signature');
$moreJS = ['/saturne/js/includes/signature-pad.min.js'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, $moreJS, [], '', 'page-public-card page-signature');

$moreParams['useConfirmation'] = 1;
require_once __DIR__ . '/../../core/tpl/signature/public_signature_view.tpl.php';

llxFooter('', 'public');
$db->close();
