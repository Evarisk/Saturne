<?php
/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/list/objectfields_list_search_title.tpl.php
 * \ingroup saturne
 * \brief   Template page for object fields list title label
 */

/**
 * The following vars must be defined :
 * Global   : $hookmanager, $langs
 * Variable : $arrayfields, $param, $selectedfields, $sortfield, $sortorder
 */

$totalarray            = [];
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch');
    $totalarray['nbfield']++;
}

foreach ($object->fields as $key => $val) {
    $cssForField = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
    if ($key == 'status') {
        $cssForField .= ' center';
    } elseif (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $cssForField .= ' center';
    } elseif (isset($val['type']) && in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && !in_array($key, ['id', 'rowid', 'ref', 'status']) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
        $cssForField .= ' right';
    }

    if (!empty($arrayfields['t.' . $key]['checked'])) {
        print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssForField ? 'class="' . $cssForField . '"' : ''), $sortfield, $sortorder, ($cssForField ? $cssForField .' ' : ''), (empty($val['disablesort']) ? '' : $val['disablesort']), (empty($val['helplist']) ? '' : $val['helplist']));
        $totalarray['nbfield']++;
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = ['arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray];
$hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch');
    $totalarray['nbfield']++;
}

print '</tr>';

// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
    foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
        if (!is_null($val) && preg_match('/\$object/', $val)) {
            $needToFetchEachLine++; // There is at least one compute field that use $object
        }
    }
}
