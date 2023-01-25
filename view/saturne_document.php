<?php
/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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

// Load Dolibarr environment
if (file_exists('../../../main.inc.php')) {
    require_once __DIR__ . '/../../../main.inc.php';
} elseif (file_exists('../../../../../main.inc.php')) {
    require_once '../../../../main.inc.php';
} else {
    die('Include of main fails');
}

// Get module parameters
$moduleName        = GETPOST('module_name', 'alpha');
$objectType        = GETPOST('object_type', 'alpha');
$bojectParentType  = GETPOSTISSET('object_parent_type') ? GETPOST('object_parent_type', 'alpha') : $objectType;

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
//@todo test les requires
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}
if (isModEnabled('contrat')) {
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}

require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $bojectParentType . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
$langs->loadLangs([$moduleNameLowerCase . '@' . $moduleNameLowerCase, 'companies', 'other', 'mails']);

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$confirm     = GETPOST('confirm', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

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
$classname   = ucfirst($objectType);
$object      = new $classname($db);
$extrafields = new ExtraFields($db);
if (isModEnabled('project')) {
    $project = new Project($db);
}
if (isModEnabled('contrat')) {
    $contract = new Contrat($db);
}

$hookmanager->initHooks([$object->element . 'note', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
    $uploadDir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?: $conf->entity] . '/' . $objectType . '/' . get_exdir(0, 0, 0, 1, $object);
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->$objectType->read;
$permissiontoadd  = $user->rights->$moduleNameLowerCase->$objectType->write;
if (empty($conf->$moduleNameLowerCase->enabled) || !$permissiontoread) {
    accessforbidden();
}

/*
*  Actions
*/

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php'; // Must be included, not include_once
}

/*
*	View
*/

$title    = $langs->trans('Files') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_' . $moduleName;
//@todo changement avec saturne
$morejs   = ['/dolimeet/js/dolimeet.js'];
$morecss  = ['/dolimeet/css/dolimeet.css'];

llxHeader('', $title, $helpUrl, '', 0, 0, $morejs, $morecss);

if ($id > 0 || !empty($ref)) {
    // Configuration header
    //@todo changer le mot session
    $head = sessionPrepareHead($object);
    print dol_get_fiche_head($head, 'document', $title, -1, $object->picto);

    // Build file list
    $filearray = dol_dir_list($uploadDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
    $totalsize = 0;
    foreach ($filearray as $key => $file) {
        $totalsize += $file['size'];
    }

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $object->element . '/' . $object->element . '_list.php', 1) . '?restore_lastsearch_values=1' . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = '<div class="refidno">';
    // Project
    if (!empty($conf->projet->enabled)) {
        if (!empty($object->fk_project)) {
            $project->fetch($object->fk_project);
            $morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1);
        } else {
            $morehtmlref .= '';
        }
    }

    // Contract @todo hook car spécifique a dolimeet
    if ($object->element == 'trainingsession') {
        if (!empty($object->fk_contrat)) {
            $contract->fetch($object->fk_contrat);
            $morehtmlref .= $langs->trans('Contract') . ' : ' . $contract->getNomUrl(1, '', 1);
        } else {
            $morehtmlref .= '';
        }
    }
    $morehtmlref .= '</div>';

    //@todo problème avec dolimeet
    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';
    // Number of files
    print '<tr><td class="titlefield">' . $langs->trans('NbOfAttachedFiles') . '</td><td colspan="3">' .count($filearray) . '</td></tr>';
    // Total size
    print '<tr><td>' . $langs->trans('TotalSizeOfAttachedFiles') . '</td><td colspan="3">' . $totalsize . ' ' . $langs->trans('bytes') . '</td></tr>';
    print '</table>';
    print '</div>';

    print dol_get_fiche_end();

    $modulepart    = $moduleNameLowerCase;
    $param         = '&module_name=' . urlencode($moduleName) . '&object_parent_type=' . urlencode($bojectParentType) . '&object_type=' . urlencode($objectType);
    $urlbacktopage = $_SERVER['PHP_SELF'] . '?id=' . $object->id . $param;
    $param        .= '&backtopage=' . urlencode($urlbacktopage);
    $moreparam     = $param;

    $relativepathwithnofile = $objectType . '/' . dol_sanitizeFileName($object->ref) . '/';

    require_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
}

// End of page
llxFooter();
$db->close();
