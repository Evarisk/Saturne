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
 * Globals    : $conf (extrafields_list_search_sql.tpl), $db, $hookmanager
 * Parameters : $action, $limit, $searchAll, $sortfield, $sortorder, $page
 * Objects    : $extrafields, $object
 * Variables  : $arrayfields, $excludeFields (optional), $offset, $search, $search_array_options (extrafields_list_search_sql.tpl), $searchCategories
 */

// Pre-calculate searchAll fields and LEFT JOINs for integer: type fields
// --------------------------------------------------------------------
$searchAllSimpleFields = [];
$searchAllJoins        = [];
$searchAllLinkedFields = [];

if ($searchAll) {
    foreach ($object->fields as $key => $field) {
        if (empty($field['searchall'])) {
            continue;
        }

        if (isset($field['type']) && strpos($field['type'], 'integer:') === 0) {
            $typeParts       = explode(':', $field['type']);
            $linkedClassName = $typeParts[1];
            $linkedClassPath = $typeParts[2];
            $fullClassPath = DOL_DOCUMENT_ROOT . '/' . $linkedClassPath;
            if (!file_exists($fullClassPath)) {
                $fullClassPath = DOL_DOCUMENT_ROOT . '/custom/' . $linkedClassPath;
            }

            if (file_exists($fullClassPath)) {
                require_once $fullClassPath;

                if (class_exists($linkedClassName)) {
                    $linkedObject = new $linkedClassName($db);

                    if (!($linkedObject instanceof CommonObject)) {
                        continue;
                    }

                    $linkedTableAlias = 'lnk_' . $key;

                    $searchAllJoins[$key] = ' LEFT JOIN ' . $db->prefix() . $linkedObject->table_element
                        . ' AS ' . $linkedTableAlias . ' ON (t.' . $key . ' = ' . $linkedTableAlias . '.rowid)';

                    if (isset($linkedObject->fields) && is_array($linkedObject->fields)) {
                        foreach ($linkedObject->fields as $linkedKey => $linkedField) {
                            if (!empty($linkedField['searchall'])) {
                                $searchAllLinkedFields[] = $linkedTableAlias . '.' . $linkedKey;
                            }
                        }
                    }
                }
            }
        } else {
            $searchAllSimpleFields[] = 't.' . $key;
        }
    }
}

// Build and execute select
// --------------------------------------------------------------------
$sql  = 'SELECT';
$sql .= ' ' . $object->getFieldList('t', $excludeFields ?? []);

// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ', ef.' . $key . ' as options_' . $key : '');
    }
}

// Add fields from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;
$sql  = preg_replace('/,\s*$/', '', $sql);
// $sql fields to remove for count total
$sqlFields = $sql;

$sql .= ' FROM ' . $db->prefix() . $object->table_element . ' as t';
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
    $sql .= ' LEFT JOIN ' . $db->prefix() . $object->table_element . '_extrafields as ef ON (t.rowid = ef.fk_object)';
}

// Add LEFT JOINs for searchAll on integer: type fields
foreach ($searchAllJoins as $join) {
    $sql .= $join;
}

// Add table from hooks
$parameters = ['search' => $search];
$hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
    $sql .= ' WHERE t.entity IN (' . getEntity($object->element, (GETPOSTINT('search_current_entity') ? 0 : 1)) . ')';
} else {
    $sql .= ' WHERE 1 = 1';
}

if (isModEnabled('categorie') && isset($categorie->MAP_OBJ_CLASS[$object->element]) && !empty($searchCategories)) {
    $objectElement = $object->element;
    if (!empty($object->parent_element)) {
        $objectElement = $object->parent_element;
    }
    $sql .= ' AND EXISTS ( SELECT 1 FROM ' . $db->prefix() . 'categorie_' . $objectElement . ' AS cp WHERE t.rowid = cp.fk_' . $objectElement . ' AND cp.fk_categorie IN (' . implode(',', $searchCategories) . '))';
}

// Add default status filter only if the object has a 'status' field in its table
if (array_key_exists('status', $object->fields)) {
    $sql .= ' AND t.status >= 0';
}

foreach ($search as $key => $val) {
    if (array_key_exists($key, $object->fields)) {

        if ($key == 'status' && $val == -1) {
            continue;
        }

        // Add search from hooks
        $parameters = ['search' => $search, 'key' => $key, 'val' => $val];
        $resHook    = $hookmanager->executeHooks('printFieldListSearch', $parameters, $object, $action);
        $sql       .= $hookmanager->resPrint;
        if (!empty($resHook)) {
            continue;
        }


        $mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
        $isExclude   = (GETPOST('search_' . $key . '_mode', 'alpha') === 'exc');
        if (isset($object->fields[$key]['type']) && ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval']))) {
            if ($val == '-1' || ($val === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
                $val = '';
            }
            if (!empty($object->fields[$key]['arrayofkeyval'])) {
                $keys = array_keys($object->fields[$key]['arrayofkeyval']);
                $keysAreInt = count(array_filter($keys, 'is_int')) === count($keys);
                if ($keysAreInt) {
                    $mode_search = 2;
                } else {
                    $mode_search = 3;
                }
            } else {
                $mode_search = 2;
            }
        }
        if (empty($object->fields[$key]['searchmulti'])) {
            if (!is_array($val) && $val != '') {
                if ($isExclude) {
                    if ($mode_search === 2) {
                        $sql .= ' AND t.' . $db->escape($key) . ' != ' . (int) $val;
                    } elseif ($mode_search === 3) {
                        $sql .= " AND t." . $db->escape($key) . " != '" . $db->escape($val) . "'";
                    } else {
                        $sql .= ' AND t.' . $db->escape($key) . ' != ' . (int) $val;
                    }
                } else {
                    $sql .= natural_search('t.' . $db->escape($key), $val, (($key == 'status') ? 2 : $mode_search));
                }
            }
        } elseif (is_array($val) && !empty($val)) {
            if ($isExclude && $mode_search === 2) {
                $sql .= ' AND t.' . $db->escape($key) . ' NOT IN (' . implode(',', array_map('intval', $val)) . ')';
            } elseif ($isExclude && $mode_search === 3) {
                $sql .= ' AND t.' . $db->escape($key) . " NOT IN (" . implode(',', array_map(function ($v) use ($db) { return "'" . $db->escape($v) . "'"; }, $val)) . ")";
            } else {
                $sql .= natural_search('t.' . $db->escape($key), implode(',', $val), (($key == 'status') ? 2 : $mode_search));
            }
        }
    } elseif (preg_match('/(_dtstart|_dtend)$/', $key) && $val != '') {
        $columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
        if (in_array($object->fields[$columnName]['type'], ['date', 'datetime', 'timestamp'])) {
            if (preg_match('/_dtstart$/', $key)) {
                $sql .= ' AND t.' . $db->sanitize($columnName) . " >= '" . $db->idate($val) . "'";
            }
            if (preg_match('/_dtend$/', $key)) {
                $sql .= ' AND t.' . $db->sanitize($columnName) . " <= '" . $db->idate($val) . "'";
            }
        }
        if ($object->fields[$columnName]['type'] == 'duration') {
            if (preg_match('/_dtstart$/', $key)) {
                $sql .= ' AND t.' . $db->sanitize($columnName) . " >= " . $val;
            }
            if (preg_match('/_dtend$/', $key)) {
                $sql .= ' AND t.' . $db->sanitize($columnName) . " <= " . $val;
            }
        }
    }
}
if ($searchAll) {
    $allSearchFields = array_merge($searchAllSimpleFields, $searchAllLinkedFields);
    if (!empty($allSearchFields)) {
        $sql .= natural_search($allSearchFields, $searchAll);
    }
}

// Add where from extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = ['search' => $search];
$hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;

// Add groupby from hooks
$parameters = [];
$hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;

// Add having from hooks
$parameters = ['search' => $search];
$hookmanager->executeHooks('printFieldListHaving', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbTotalOfRecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    /* The fast and low memory method to get and count full list converts the sql into a sql count */
    $sqlForCount = preg_replace('/^' . preg_quote($sqlFields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
    // Only strip the extrafields LEFT JOIN (not searchAll joins which are referenced in WHERE)
    $sqlForCount = preg_replace('/ LEFT JOIN \S+_extrafields\s+as\s+ef\s+ON\s+\([^)]+\)/', '', $sqlForCount);
    $sqlForCount = preg_replace('/GROUP BY .*$/', '', $sqlForCount);
    $resql = $db->query($sqlForCount);
    if ($resql) {
        $objForCount      = $db->fetch_object($resql);
        $nbTotalOfRecords = $objForCount->nbtotalofrecords;
    } else {
        dol_print_error($db);
    }

    // if total resultset is smaller than the paging size (filtering), goto and load page 0
    if (($page * $limit) > $nbTotalOfRecords) {
        $page   = 0;
        $offset = 0;
    }
    $db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
//if (array_key_exists($sortfield, $elementElementFields)) {
//$sql .= $db->order($elementElementFields[$sortfield] . '.fk_source ', $sortorder);
//}
//if ($sortfield == 'days_remaining_before_next_control') {
//    $sql .= $db->order('next_control_date', $sortorder);
//}
if ($limit) {
    $sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $searchAll && !$page) {
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    //@todo parameter
    header('Location: ' . dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?id=' . $id);
    exit;
}
