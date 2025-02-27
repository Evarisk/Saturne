<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/list/objectfields_build_sql_select.tpl.php
 * \ingroup saturne
 * \brief   Template page for object fields list build sql select
 */

/**
 * The following vars must be defined :
 * Global   : $db, $hookmanager, $langs
 * Objects  : $object, $extrafields
 * Variable : $arrayfields, $num, $resql, $sql
 */

// Build and execute select
// --------------------------------------------------------------------
$sql  = 'SELECT';
$sql .= ' ' . $object->getFieldList('t', $excludefields);
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ', ef.' . $key . ' as options_' . $key : '');
    }
}
// Add fields from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql  = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM ' . $db->prefix() . $object->table_element . ' as t';
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
    $sql .= ' LEFT JOIN ' . $db->prefix() . $object->table_element . '_extrafields as ef on (t.rowid = ef.fk_object)';
}

// Add table from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
    $sql .= ' WHERE t.entity IN (' . getEntity($object->element, (GETPOSTINT('search_current_entity') ? 0 : 1)) . ')';
} else {
    $sql .= ' WHERE 1 = 1';
}

foreach ($search as $key => $val) {
    if (array_key_exists($key, $object->fields)) {
        if ($key == 'status' && $val == -1) {
            continue;
        }
        $mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
        if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
            if ($val == '-1' || ($val === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
                $val = '';
            }
            $mode_search = 2;
        }
        if (empty($object->fields[$key]['searchmulti'])) {
            if (!is_array($val) && $val != '') {
                $sql .= natural_search('t.' . $db->escape($key), $val, (($key == 'status') ? 2 : $mode_search));
            }
        } elseif (is_array($val) && !empty($val)) {
            $sql .= natural_search('t.' . $db->escape($key), implode(',', $val), (($key == 'status') ? 2 : $mode_search));
        }
    } elseif (preg_match('/(_dtstart|_dtend)$/', $key) && $val != '') {
        $columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
        if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
            if (preg_match('/_dtstart$/', $key)) {
                $sql .= ' AND t.' . $db->sanitize($columnName) . " >= '" . $db->idate($val) . "'";
            }
            if (preg_match('/_dtend$/', $key)) {
                $sql .= ' AND t.' . $db->sanitize($columnName) . " <= '" . $db->idate($val) . "'";
            }
        }
    }
}
if ($search_all) {
    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

// Add where from extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $objectdocument may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    /* The fast and low memory method to get and count full list converts the sql into a sql count */
    $sqlforcount = preg_replace('/^' . preg_quote($sqlfields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
    $sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);

    $resql = $db->query($sqlforcount);
    if ($resql) {
        $objforcount      = $db->fetch_object($resql);
        $nbtotalofrecords = $objforcount->nbtotalofrecords;
    } else {
        dol_print_error($db);
    }

    if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
        $page   = 0;
        $offset = 0;
    }
    $db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
    $sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}
