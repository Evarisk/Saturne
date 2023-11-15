<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 *  \file       view/saturne_document.php
 *  \ingroup    saturne
 *  \brief      Tab of documents linked to generic element
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

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$confirm     = GETPOST('confirm', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');
$showNav     = GETPOST('show_nav', 'int');
$handlePhoto = GETPOST('handle_photo', 'alpha');
$subaction   = GETPOST('subaction', 'alpha');

// Get pagination parameters
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');

if (empty($page) || $page == -1) { // If $page is not defined, or '' or -1
    $page = 0;
}

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
    $sortfield = 'name';
}
if (!$sortorder) {
    $sortorder = 'ASC';
}

// Initialize technical objects
$className   = ucfirst($objectType);
$object      = new $className($db);
$extrafields = new ExtraFields($db);

$hookmanager->initHooks([$objectType . 'document', $object->element . 'document', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
    $upload_dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?: $conf->entity] . '/' . $object->element . '/' . get_exdir(0, 0, 0, 1, $object);
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->$objectType->read;
$permissiontoadd  = $user->rights->$moduleNameLowerCase->$objectType->write;

saturne_check_access($permissiontoread, $object);

/*
*  Actions
*/

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php'; // Must be included, not include_once

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../core/tpl/actions/banner_actions.tpl.php';
}

/*
*	View
*/

$title   = $langs->trans('Files') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_' . $moduleName;

$reshook  = $hookmanager->executeHooks('saturneCustomHeaderFunction', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
    $customHeaderFunction = $hookmanager->resPrint;
    $customHeaderFunction($title, $helpUrl);
} else {
    saturne_header(0, '', $title, $helpUrl);
}

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'document', $title);
    saturne_banner_tab($object, 'ref', '', dol_strlen($showNav) > 0 ? $showNav : 1, 'ref', 'ref', method_exists($object, 'getMoreHtmlRef') ? $object->getMoreHtmlRef($object->id) : '', ((!empty($object->photo) || dol_strlen($handlePhoto) > 0) ? $handlePhoto : false));

    // Build file list
    $filearray = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
    $totalsize = 0;
    foreach ($filearray as $file) {
        $totalsize += $file['size'];
    }

    print '<div class="fichecenter">';
    print '<table class="border centpercent tableforfield">';
    // Number of files
    print '<tr><td class="titlefield">' . $langs->trans('NbOfAttachedFiles') . '</td><td colspan="3">' . count($filearray) . '</td></tr>';
    // Total size
    print '<tr><td>' . $langs->trans('TotalSizeOfAttachedFiles') . '</td><td colspan="3">' . $totalsize . ' ' . $langs->trans('bytes') . '</td></tr>';
    print '</table>';
    print '</div>';

    print dol_get_fiche_end();

    $modulepart    = $moduleNameLowerCase;
    $param         = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element);
    $urlbacktopage = $_SERVER['PHP_SELF'] . '?id=' . $object->id . $param;
    $param        .= '&backtopage=' . urlencode($urlbacktopage);
    $moreparam     = $param;

    $relativepathwithnofile = $object->element . '/' . dol_sanitizeFileName($object->ref) . '/';

    require_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
}

// End of page
llxFooter();
$db->close();
