<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/admin/object/object._extrafields_view.php
 * \ingroup saturne
 * \brief   Saturne object config page.
 */

/**
 * The following vars must be defined:
 * global     : $langs
 * parameters : $action, $moduleNameLowerCase, $objectType
 * variable   : $title
 */

// Get parameters.
$value    = GETPOST('value', 'alpha');
$attrname = GETPOST('attrname', 'alpha');

// List of supported format type extrafield label.
$tmptype2label = ExtraFields::$type2label;
$type2label    = [''];
foreach ($tmptype2label as $key => $val) {
    $type2label[$key] = $langs->transnoentitiesnoconv($val);
}
$elementtype = $moduleNameLowerCase . '_' . $objectType; // Must be the $table_element of the class that manage extrafield.

/*
 * Actions
 */

// Extrafields actions.
require_once DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

/*
 * View
 */

$textobject = $title;

// Extrafields management.
print load_fiche_titre($langs->trans('Extrafields'), '', '');

require_once DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

// Buttons.
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create&module_name=' . $moduleName . '&object_type=' . $objectType . '">' . $langs->trans('NewAttribute') . '</a></div>';
    print '</div>';
}

// Creation of an optional field.
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewAttribute'));
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field.
if ($action == 'edit' && !empty($attrname)) {
    print load_fiche_titre($langs->trans('FieldEdition', $attrname));
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}