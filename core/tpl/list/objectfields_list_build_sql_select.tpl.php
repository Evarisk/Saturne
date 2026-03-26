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
 * Variables  : $arrayfields, $excludeFields (optional), $fieldsToSearchAll, $offset, $search, $search_array_options (extrafields_list_search_sql.tpl), $searchCategories
 */

/** @var DoliDB $db */
/** @var CommonObject $object */
// Pre-compute LEFT JOINs + searchable columns for integer:/sellist: FK fields used in search_all
// Using JOINs avoids correlated subquery issues in all MySQL/MariaDB versions
// --------------------------------------------------------------------
$searchAllFkJoins   = '';
$searchAllFkAliases = []; // [fieldKey => ['alias' => string, 'cols' => [alias.col, ...]]]

if (!empty($searchAll)) {
    // Extract real column names of table t — avoids JOINing on virtual/missing columns
    $saMainCols = [];
    preg_match_all('/\bt\.(\w+)\b/', $object->getFieldList('t', $excludeFields ?? []), $saColMatches);
    if (!empty($saColMatches[1])) {
        $saMainCols = $saColMatches[1];
    }

    foreach ($object->fields as $saKey => $saDef) {
        // Include FK field if it has searchall=>1 (in $fieldsToSearchAll) OR is visible in the list
        $saInSearchAll = isset($fieldsToSearchAll['t.' . $saKey]);
        $saIsChecked   = !empty($arrayfields['t.' . $saKey]['checked']);
        if (!$saInSearchAll && !$saIsChecked) {
            continue;
        }
        // Verify the column actually exists in table t
        if (!empty($saMainCols) && !in_array($saKey, $saMainCols)) {
            continue;
        }
        $saType = isset($saDef['type']) ? $saDef['type'] : '';
        if (strpos($saType, 'integer:') !== 0 && strpos($saType, 'sellist:') !== 0) {
            continue;
        }
        $saAlias = 'srch_' . $db->sanitize($saKey);

        if (strpos($saType, 'sellist:') === 0) {
            $saParts = explode(':', $saType);
            if (count($saParts) < 3) {
                continue;
            }
            $saRefTable = $db->prefix() . $db->sanitize($saParts[1]);
            $saIdCol    = !empty($saParts[3]) ? $db->sanitize($saParts[3]) : 'rowid';
            $saLabelCol = $db->sanitize($saParts[2]);
            $searchAllFkJoins .= ' LEFT JOIN ' . $saRefTable . ' AS ' . $saAlias . ' ON ' . $saAlias . '.' . $saIdCol . ' = t.' . $db->sanitize($saKey);
            $searchAllFkAliases[$saKey] = ['alias' => $saAlias, 'cols' => [$saAlias . '.' . $saLabelCol]];
        } elseif (strpos($saType, 'integer:') === 0) {
            $saParts     = explode(':', $saType);
            $saClassName = !empty($saParts[1]) ? $saParts[1] : '';
            $saClassFile = '';
            if (!empty($saParts[2])) {
                if (file_exists(DOL_DOCUMENT_ROOT . '/' . $saParts[2])) {
                    $saClassFile = DOL_DOCUMENT_ROOT . '/' . $saParts[2];
                } elseif (file_exists(DOL_DOCUMENT_ROOT . '/custom/' . $saParts[2])) {
                    $saClassFile = DOL_DOCUMENT_ROOT . '/custom/' . $saParts[2];
                }
            }
            if (!$saClassName || !$saClassFile) {
                continue;
            }
            if (!class_exists($saClassName)) {
                require_once $saClassFile;
            }
            if (!class_exists($saClassName)) {
                continue;
            }
            $saTmpObj   = new $saClassName($db);
            $saRefTable = $db->prefix() . $saTmpObj->table_element;
            // integer: format is ClassName:file[:filterstate[:morewhere]] — parts[3]/[4] are NOT column names
            $saIdCol    = 'rowid';
            $saFields   = !empty($saTmpObj->fields) ? $saTmpObj->fields : [];
            $saCols     = [];

            // Fields with searchall=>1 on the referenced object (text cols only)
            foreach ($saFields as $srKey => $srDef) {
                if (empty($srDef['searchall'])) {
                    continue;
                }
                $srType = isset($srDef['type']) ? $srDef['type'] : '';
                if (strpos($srType, 'integer:') === 0 || strpos($srType, 'sellist:') === 0) {
                    continue;
                }
                $c = $saAlias . '.' . $db->sanitize($srKey);
                if (!in_array($c, $saCols)) {
                    $saCols[] = $c;
                }
            }

            // Fallback: common label candidates validated against $saFields
            if (empty($saCols)) {
                foreach (['ref', 'label', 'nom', 'name', 'title', 'libelle', 'code', 'login', 'batch', 'lastname', 'firstname'] as $candidate) {
                    if (isset($saFields[$candidate])) {
                        $cType = isset($saFields[$candidate]['type']) ? $saFields[$candidate]['type'] : '';
                        if (strpos($cType, 'integer:') !== 0 && strpos($cType, 'sellist:') !== 0) {
                            $saCols[] = $saAlias . '.' . $candidate;
                        }
                    }
                }
            }

            // Last resort: first varchar/text field in the referenced object's fields
            if (empty($saCols) && !empty($saFields)) {
                foreach ($saFields as $frKey => $frDef) {
                    $frType = isset($frDef['type']) ? $frDef['type'] : '';
                    if (strpos($frType, 'integer:') === 0 || strpos($frType, 'sellist:') === 0) {
                        continue;
                    }
                    if (strpos($frType, 'varchar') !== false || in_array($frType, ['text', 'html', 'string', 'phone', 'mail', 'url'])) {
                        $saCols[] = $saAlias . '.' . $db->sanitize($frKey);
                        break;
                    }
                }
            }

            if (empty($saCols)) {
                continue; // No usable text column found — skip this FK field
            }
            $searchAllFkJoins .= ' LEFT JOIN ' . $saRefTable . ' AS ' . $saAlias . ' ON ' . $saAlias . '.' . $saIdCol . ' = t.' . $db->sanitize($saKey);
            $searchAllFkAliases[$saKey] = ['alias' => $saAlias, 'cols' => $saCols];
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

// Add table from hooks
$parameters = ['search' => $search];
$hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;
// Add FK JOINs for search_all label resolution
$sql .= $searchAllFkJoins;
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
        $fieldMode    = GETPOST('search_' . $key . '_mode', 'alpha');
        $isExcludeMode = ($fieldMode === 'exc' && $key !== 'status');

        if ($isExcludeMode) {
            // Build NOT IN / != manually for exclude mode
            $col = 't.' . $db->sanitize($key);
            $ids = is_array($val) ? array_filter(array_map('intval', $val)) : array_filter(array_map('intval', explode(',', (string) $val)));
            if (!empty($ids)) {
                $sql .= count($ids) === 1
                    ? ' AND ' . $col . ' != ' . reset($ids)
                    : ' AND ' . $col . ' NOT IN (' . implode(',', $ids) . ')';
            }
        } elseif (empty($object->fields[$key]['searchmulti'])) {
            if (!is_array($val) && $val != '') {
                $sql .= natural_search('t.' . $db->escape($key), $val, (($key == 'status') ? 2 : $mode_search));
            }
        } elseif (is_array($val) && !empty($val)) {
            $sql .= natural_search('t.' . $db->escape($key), implode(',', $val), (($key == 'status') ? 2 : $mode_search));
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
    $orClauses = [];
    $escaped   = $db->escape($searchAll);

    // Text fields from $fieldsToSearchAll — plain LIKE
    foreach (array_keys($fieldsToSearchAll) as $sqlField) {
        $saKey  = preg_replace('/^[a-zA-Z]+\./', '', $sqlField);
        $saType = isset($object->fields[$saKey]['type']) ? $object->fields[$saKey]['type'] : '';
        if (strpos($saType, 'integer:') === 0 || strpos($saType, 'sellist:') === 0) {
            continue; // handled by JOIN aliases below
        }
        $orClauses[] = $sqlField . " LIKE '%" . $escaped . "%'";
    }

    // FK fields — search on pre-JOINed alias columns, no subqueries
    foreach ($searchAllFkAliases as $fkInfo) {
        foreach ($fkInfo['cols'] as $col) {
            $orClauses[] = $col . " LIKE '%" . $escaped . "%'";
        }
    }

    if (!empty($orClauses)) {
        $sql .= ' AND (' . implode(' OR ', $orClauses) . ')';
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
    // Strip LEFT JOINs (extrafields etc.) but re-inject FK search JOINs needed by the WHERE clause
    $sqlForCount = preg_replace('/\s+LEFT\s+JOIN\s+.*?\s+WHERE\s+/is', $searchAllFkJoins . ' WHERE ', $sqlForCount);
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
