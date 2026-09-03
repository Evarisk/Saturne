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
 *
 * Exposes    : $sqlForList — full filtered query before sort/pagination, for aggregate/KPI hooks
 */

// Pre-calculate searchAll fields and LEFT JOINs for integer: type fields
// --------------------------------------------------------------------
$searchAllSimpleFields = [];
$searchAllJoins        = [];
$searchAllLinkedFields = [];

if ($searchAll) {
    foreach ($object->fields as $key => $field) {
        // Skip fields explicitly excluded (virtual/computed fields not in the DB table)
        if (!empty($excludeFields) && in_array($key, $excludeFields)) {
            continue;
        }

        $isCheckedInArrayFields = !empty($arrayfields['t.' . $key]['checked']);

        // Include field if it has searchall OR if it is checked in the visible fields list
        if (empty($field['searchall']) || !$isCheckedInArrayFields) {
            continue;
        }

        if (isset($field['type']) && strpos($field['type'], 'integer:') === 0) {
            $typeParts       = explode(':', $field['type']);
            $linkedClassName = $typeParts[1];
            $linkedClassPath = $typeParts[2];
            $res = dol_include_once($linkedClassPath);

            if ($res) {
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

// Category criteria is a list of signed tokens : "12" includes category 12, "-12" excludes it, and
// NOTCATEGORIZED / -NOTCATEGORIZED match objects carrying no category at all. Shared with the list header TPL,
// which renders one tag per token and lets the user flip its sign
$categoryNotCategorizedToken = 'NOTCATEGORIZED';
$categoryFallbackColor       = '#95a5a6';

// Not every list controller instantiates the category object nor reads the criteria : do it here so any list built on
// this TPL gets the category filter, and so the tags printed by the list header TPL are always backed by a WHERE
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    if (!isset($categorie) || !$categorie instanceof Categorie) {
        $categorie = new Categorie($db);
    }

    // $searchCategories holds the plain category ids read by the list controllers, the tag filter adds the signed
    // tokens : merge both so old links (and the catid parameter) keep working alongside the tag UI
    $searchCategories = (isset($searchCategories) && is_array($searchCategories)) ? $searchCategories : [];
    $searchCategories = array_merge($searchCategories, GETPOST('search_categories_filter', 'array'));
    if (GETPOSTINT('catid') > 0) {
        $searchCategories[] = GETPOSTINT('catid');
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
        $searchCategories = [];
    }

    // Keep only well-formed tokens, this is what protects the IN () clauses below from raw input
    $searchCategories = array_values(array_filter($searchCategories, function ($searchCategory) use ($categoryNotCategorizedToken) {
        return preg_match('/^[+-]?(\d+|' . $categoryNotCategorizedToken . ')$/', (string) $searchCategory) && (string) $searchCategory !== '0';
    }));
}

if (isModEnabled('categorie') && isset($categorie->MAP_OBJ_CLASS[$object->element]) && !empty($searchCategories)) {
    $objectElement = $object->element;
    if (!empty($object->parent_element)) {
        $objectElement = $object->parent_element;
    }

    $categoryExistsSql = 'SELECT 1 FROM ' . $db->prefix() . 'categorie_' . $objectElement . ' AS cp WHERE t.rowid = cp.fk_' . $objectElement;

    $includedCategoryIds = [];
    $excludedCategoryIds = [];
    $includeNotCategorized = false;
    $excludeNotCategorized = false;
    foreach ($searchCategories as $searchCategory) {
        $isExcluded    = (strpos((string) $searchCategory, '-') === 0);
        $categoryToken = ltrim((string) $searchCategory, '+-');

        if ($categoryToken === $categoryNotCategorizedToken) {
            if ($isExcluded) {
                $excludeNotCategorized = true;
            } else {
                $includeNotCategorized = true;
            }
        } elseif ($isExcluded) {
            $excludedCategoryIds[] = (int) $categoryToken;
        } else {
            $includedCategoryIds[] = (int) $categoryToken;
        }
    }

    // Included categories widen the result set, so they are ORed together
    $includeConditions = [];
    if (!empty($includedCategoryIds)) {
        $includeConditions[] = 'EXISTS (' . $categoryExistsSql . ' AND cp.fk_categorie IN (' . implode(',', $includedCategoryIds) . '))';
    }
    if ($includeNotCategorized) {
        $includeConditions[] = 'NOT EXISTS (' . $categoryExistsSql . ')';
    }
    if (!empty($includeConditions)) {
        $sql .= ' AND (' . implode(' OR ', $includeConditions) . ')';
    }

    // Excluded ones always narrow it, whatever was included
    if (!empty($excludedCategoryIds)) {
        $sql .= ' AND NOT EXISTS (' . $categoryExistsSql . ' AND cp.fk_categorie IN (' . implode(',', $excludedCategoryIds) . '))';
    }
    if ($excludeNotCategorized) {
        $sql .= ' AND EXISTS (' . $categoryExistsSql . ')';
    }
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

        // Virtual/computed/linked fields (listed in $excludeFields) have no real t.<key>
        // column in the base table. Linked elements are already handled by the
        // printFieldListSearch hook above; any excluded field still reaching here would
        // otherwise produce "Unknown column 't.<key>'" (e.g. signatory role columns such
        // as 'Controller'). Skip the generic column filter for them.
        if (!empty($excludeFields) && in_array($key, $excludeFields, true)) {
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
        if (empty($object->fields[$key]['searchmulti'])) {
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

// Snapshot of the full filtered query (all joins, filters and search criteria applied) before sorting and pagination.
// Aggregate/KPI hooks (e.g. printFieldPreListTitle) can wrap it as a subquery to compute totals over the whole filtered set.
$sqlForList = $sql;

// Count total nb of records
$nbTotalOfRecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    if (preg_match('/\bGROUP\s+BY\b/i', $sqlForList)) {
        // A printFieldListFrom hook can add a JOIN that multiplies the rows of one object (one row
        // per linked record), collapsed again by the GROUP BY of printFieldListGroupBy - and possibly
        // filtered on that aggregate by printFieldListHaving. Turning the SELECT into a COUNT(*) and
        // dropping the GROUP BY would count the joined rows instead of the objects, so count the rows
        // the grouped query actually returns.
        $sqlForCount = 'SELECT COUNT(*) as nbtotalofrecords FROM (' . $sqlForList . ') as countedrows';
    } else {
        /* The fast and low memory method to get and count full list converts the sql into a sql count */
        $countSelect = 'SELECT COUNT(*) as nbtotalofrecords';
        $sqlForCount = preg_replace('/^' . preg_quote($sqlFields, '/') . '/', $countSelect, $sql);
        // Only strip the extrafields LEFT JOIN (not searchAll joins which are referenced in WHERE)
        $sqlForCount = preg_replace('/ LEFT JOIN \S+_extrafields\s+as\s+ef\s+ON\s+\([^)]+\)/', '', $sqlForCount);
    }
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

// Guard against an invalid sort field (e.g. a stale URL/session value pointing at a non-sortable
// linked or computed column such as a linked product) that would crash the whole query with
// DB_ERROR_NOSUCHFIELD. Build the set of identifiers actually produced by the SELECT and drop the
// sort when it references something else, so the list self-heals instead of erroring.
if (!empty($sortfield)) {
    $validSortFields = [];
    $selectList      = preg_replace('/^\s*SELECT\s+/i', '', $sqlFields);
    foreach (explode(',', $selectList) as $selectPart) {
        $selectPart = trim($selectPart);
        if (preg_match('/\bAS\s+([A-Za-z0-9_]+)$/i', $selectPart, $matches)) {
            $validSortFields[$matches[1]] = true;
        } elseif (preg_match('/^([A-Za-z0-9_]+\.[A-Za-z0-9_]+)$/', $selectPart, $matches)) {
            $validSortFields[$matches[1]] = true;
        }
    }

    foreach (explode(',', $sortfield) as $sortFieldToken) {
        $sortFieldToken = trim($sortFieldToken);
        if ($sortFieldToken !== '' && empty($validSortFields[$sortFieldToken])) {
            dol_syslog('Saturne list: dropping invalid sortfield "' . $sortfield . '" not present in SELECT', LOG_WARNING);
            $sortfield = '';
            $sortorder = '';
            break;
        }
    }
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

// Direct jump if only one record found, out of reach once the page header has been printed : the redirect
// would only raise a "headers already sent" warning and leave the list truncated
if ($num == 1 && !headers_sent() && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $searchAll && !$page) {
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    //@todo parameter
    header('Location: ' . dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?id=' . $id);
    exit;
}
