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
 * \file    lib/object.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Object
 */

/**
 * Load list of objects in memory from the database.
 *
 * @param  string      $className  Object className
 * @param  string      $sortorder  Sort Order
 * @param  string      $sortfield  Sort field
 * @param  int         $limit      Limit
 * @param  int         $offset     Offset
 * @param  array       $filter     Filter array. Example array('field'=>'value', 'customurl'=>...)
 * @param  string      $filtermode Filter mode (AND/OR)
 * @return int|array               0 < if KO, array of pages if OK
 * @throws Exception
 */
function saturne_fetch_all_object_type(string $className = '', string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
{
    dol_syslog(__METHOD__, LOG_DEBUG);

    global $db;

    $object = new $className($db);

    $records = [];

    $sql = 'SELECT ';
    $sql .= $object->getFieldList('t');
    $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element . ' as t';
    if (isset($object->ismultientitymanaged) && $object->ismultientitymanaged == 1) {
        $sql .= ' WHERE entity IN (' . getEntity($object->table_element) . ')';
    } else {
        $sql .= ' WHERE 1 = 1';
    }
    // Manage filter
    $sqlwhere = [];
    if (count($filter) > 0) {
        foreach ($filter as $key => $value) {
            if ($key == 't.rowid') {
                $sqlwhere[] = $key . ' = ' . $value;
            } elseif (in_array($object->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
                $sqlwhere[] = $key .' = \'' . $object->db->idate($value) . '\'';
            } elseif ($key == 'customsql') {
                $sqlwhere[] = $value;
            } elseif (strpos($value, '%') === false) {
                $sqlwhere[] = $key .' IN (' . $object->db->sanitize($object->db->escape($value)) . ')';
            } else {
                $sqlwhere[] = $key .' LIKE \'%' . $object->db->escape($value) . '%\'';
            }
        }
    }
    if (count($sqlwhere) > 0) {
        $sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
    }

    if (!empty($sortfield)) {
        $sql .= $object->db->order($sortfield, $sortorder);
    }
    if (!empty($limit)) {
        $sql .= ' ' . $object->db->plimit($limit, $offset);
    }

    $resql = $object->db->query($sql);
    if ($resql) {
        $num = $object->db->num_rows($resql);
        $i = 0;
        while ($i < ($limit ? min($limit, $num) : $num)) {
            $obj = $object->db->fetch_object($resql);

            $record = new $className($db);
            $record->setVarsFromFetchObj($obj);

            $records[$record->id] = $record;

            $i++;
        }
        $object->db->free($resql);

        return $records;
    } else {
        $object->errors[] = 'Error ' . $object->db->lasterror();
        dol_syslog(__METHOD__ . ' ' . join(',', $object->errors), LOG_ERR);

        return -1;
    }
}