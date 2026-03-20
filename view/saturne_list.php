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
 * \file    view/saturne_list.php
 * \ingroup saturne
 * \brief   Generic list page for saturne objects
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
$objectType = GETPOST('object_type', 'aZ09');

// Validate object_type
if (empty($objectType)) {
    accessforbidden('BadObjectType');
}

// Load object metadata — handles class require_once + instantiation internally
$objectMetadata = saturne_get_objects_metadata($objectType);
if (empty($objectMetadata)) {
    accessforbidden('ObjectTypeNotSupported');
}

// Load Dolibarr libraries
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action     = GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');                                // The bulk action (combo box choice into lists)

// Get list parameters
$toselect                                   = [];
[$confirm, $contextpage, $optioncss, $mode] = ['', '', '', ''];
$listParameters                             = saturne_load_list_parameters(basename(dirname(__FILE__)));
$listParameters['contextpage']              = GETPOSTISSET('contextpage') ? GETPOST('contextpage', 'aZ') : $objectMetadata['hook_name_list'];
foreach ($listParameters as $listParameterKey => $listParameter) {
    $$listParameterKey = $listParameter;
}

// Get pagination parameters
[$limit, $page, $offset] = [0, 0, 0];
[$sortfield, $sortorder] = ['', ''];
$paginationParameters    = saturne_load_pagination_parameters();
foreach ($paginationParameters as $paginationParameterKey => $paginationParameter) {
    $$paginationParameterKey = $paginationParameter;
}

// Initialize technical objects
$object      = $objectMetadata['object'];
$extrafields = new ExtraFields($db);
if (isModEnabled('categorie')) {
    $categorie = new Categorie($db);
}

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$contextpage]); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (isModEnabled('categorie')) {
    $searchCategories = GETPOST('search_category_' . $object->element . '_list', 'array');
}

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
    $sortfield = $objectMetadata['defaultsort'];
}
if (!$sortorder) {
    $sortorder = $objectMetadata['defaultorder'];
}

$excludeFields = [];

// Hook to add/override fields per object type
$parameters = ['objectType' => $objectType, 'excludeFields' => $excludeFields];
$hookmanager->executeHooks('saturneListAddCustomFields', $parameters, $object);
if (!empty($hookmanager->resArray['excludeFields'])) {
    $excludeFields = $hookmanager->resArray['excludeFields'];
}

// Initialize array of search criterias
$searchAll = trim(GETPOST('search_all'));
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
    if (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
        $search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
    }
}

// List of fields to search into when doing a "search in all"
$fieldsToSearchAll = [];
foreach ($object->fields as $key => $val) {
    if (!empty($val['searchall'])) {
        $fieldsToSearchAll['t.' . $key] = $val['label'];
    }
}

// Definition of array of fields for columns
foreach ($object->fields as $key => $val) {
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible']);
        $arrayfields['t.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'])),
            'position' => $val['position'],
            'help'     => $val['help'] ?? '',
        ];
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields    = dol_sort_array($arrayfields, 'position');

// Permissions
if (!empty($object->module)) {
    $permissiontoread   = $user->hasRight($object->module, $object->element, 'read');
    $permissiontoadd    = $user->hasRight($object->module, $object->element, 'write');
    $permissiontodelete = $user->hasRight($object->module, $object->element, 'delete');
} else {
    $permissiontoread   = $user->hasRight($object->element, 'read');
    $permissiontoadd    = $user->hasRight($object->element, 'write');
    $permissiontodelete = $user->hasRight($object->element, 'delete');
}

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = ['arrayfields' => &$arrayfields];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Selection of new fields
    require_once DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($object->fields as $key => $val) {
            $search[$key] = '';
            if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
                $search[$key . '_dtstart'] = '';
                $search[$key . '_dtend']   = '';
            }
        }
        $searchAll            = '';
        $toselect             = [];
        $search_array_options = [];
        $searchCategories     = [];
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
        $massaction = '';
    }

    // Mass actions
    $objectclass  = $objectMetadata['class_name'];
    $objectlabel  = $objectMetadata['class_name'];
    $objectModule = !empty($object->module) ? $object->module : $object->element;
    $uploaddir    = getMultidirOutput($object, $objectModule);

    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    // Mass actions archive
    require_once __DIR__ . '/../core/tpl/actions/list_massactions.tpl.php';
}

/*
 * View
 */

$title = $langs->trans(ucfirst($object->element) . 'List');
saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'mod-' . $object->element . ' page-list bodyforlist');

?>
    <script nonce="<?php echo getNonce(); ?>">
        Dolibarr.setContextVars(<?php print json_encode([
            'DOL_VERSION'            => DOL_VERSION,
            'MAIN_LANG_DEFAULT'      => 'fr_FR',
            'DOL_LANG_INTERFACE_URL' => dol_buildpath('admin/tools/ui/experimental/experiments/dolibarr-context/langs-tool-interface.php', 1),
        ]) ?>);
    </script>
<?php

require_once __DIR__ . '/../core/tpl/list/objectfields_list_build_sql_select.tpl.php';
require_once __DIR__ . '/../core/tpl/list/objectfields_list_header.tpl.php';
require_once __DIR__ . '/../core/tpl/list/objectfields_list_search_input.tpl.php';
require_once __DIR__ . '/../core/tpl/list/objectfields_list_search_title.tpl.php';
require_once __DIR__ . '/../core/tpl/list/objectfields_list_loop_object.tpl.php';
require_once __DIR__ . '/../core/tpl/list/objectfields_list_footer.tpl.php';

// End of page
llxFooter();
$db->close();
