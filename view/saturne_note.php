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
 *  \file       view/saturne_note.php
 *  \ingroup    saturne
 *  \brief      Tab of notes on generic element
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
$module_name        = GETPOST('module_name', 'alpha');
$object_type        = GETPOST('object_type', 'alpha');
$object_parent_type = GETPOSTISSET('object_parent_type') ? GETPOST('object_parent_type', 'alpha') : $object_type;

$module_name_lower = strtolower($module_name);

// Libraries
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}
if (isModEnabled('contrat')) {
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}

require_once __DIR__ . '/../../' . $module_name_lower . '/class/' . $object_type . '.class.php';
require_once __DIR__ . '/../../' . $module_name_lower . '/lib/' . $module_name_lower . '_' . $object_parent_type . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
$langs->loadLangs([$module_name_lower . '@' . $module_name_lower, 'companies']);

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname   = ucfirst($object_type);
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
    $upload_dir = $conf->$module_name_lower->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity] . '/' . $object->id;
}

// Security check - Protection if external user
$permissiontoread = $user->rights->$module_name_lower->$object_type->read;
$permissionnote   = $user->rights->$module_name_lower->$object_type->write; // Used by include of actions_setnotes.inc.php
if (empty($conf->$module_name_lower->enabled) || !$permissiontoread) {
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
    include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be included, not include_once
}

/*
*	View
*/

$title    = $langs->trans('Note') . ' - ' . $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_' . $module_name;
//@todo changement avec saturne
$morejs   = ['/dolimeet/js/dolimeet.js'];
$morecss  = ['/dolimeet/css/dolimeet.css'];

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss);

if ($id > 0 || !empty($ref)) {
    // Configuration header
    //@todo changer le mot session
    $head = sessionPrepareHead($object);
    print dol_get_fiche_head($head, 'note', $title, -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/' . $module_name_lower . '/view/' . $object->element . '/' . $object->element . '_list.php', 1) . '?restore_lastsearch_values=1' . '">' . $langs->trans('BackToList') . '</a>';

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

    $cssclass = 'titlefield';
    $moreparam = '&module_name=' . urlencode($module_name) . '&object_parent_type=' . urlencode($object_parent_type) . '&object_type=' . urlencode($object_type);
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';

    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();