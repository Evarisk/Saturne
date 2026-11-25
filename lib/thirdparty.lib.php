<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/thirdparty.lib.php
 * \ingroup saturne
 * \brief   Library files keeping the objects of Saturne and of the modules built on it attached to their thirdparty
 *          when two thirdparties are merged
 */

/**
 * Element types under which Saturne and its modules store a link to a thirdparty in a type column
 * (element_type, object_type...): the Dolibarr element "societe", or the Saturne object key "thirdparty"
 *
 * @return array<string> Element types
 */
function saturne_thirdparty_element_types(): array
{
    return ['societe', 'thirdparty'];
}

/**
 * List the columns of a table pointing at a thirdparty. A fk_soc like column holds a thirdparty id on its own, an
 * element_id like column only when the type column beside it names a thirdparty.
 *
 * @param  array<string,string>                            $columns Column name => column type
 * @return array<int,array{column:string,type:string}>              Id column, and its type column ('' when the id column alone points at a thirdparty)
 */
function saturne_thirdparty_link_columns(array $columns): array
{
    $links = [];

    foreach (['fk_soc', 'fk_societe', 'fk_thirdparty'] as $column) {
        if (isset($columns[$column])) {
            $links[] = ['column' => $column, 'type' => ''];
        }
    }

    $pairs = [
        'element_type' => ['element_id', 'fk_element'],
        'elementtype'  => ['fk_element'],
        'object_type'  => ['object_id', 'fk_object'],
    ];
    foreach ($pairs as $typeColumn => $idColumns) {
        if (!isset($columns[$typeColumn])) {
            continue;
        }

        foreach ($idColumns as $idColumn) {
            if (isset($columns[$idColumn])) {
                $links[] = ['column' => $idColumn, 'type' => $typeColumn];
            }
        }
    }

    return $links;
}

/**
 * Read the tables a SQL file creates
 *
 * @param  string        $sql Content of the SQL file
 * @return array<string>      Table names without the llx_ prefix the module files are written with
 */
function saturne_thirdparty_created_tables(string $sql): array
{
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?llx_([a-z0-9_]+)`?/i', $sql, $matches);

    return array_values(array_unique(array_map('strtolower', $matches[1])));
}

/**
 * List the tables a module creates, read from the CREATE TABLE statements of its sql directory. The name of the module
 * is not enough: DoliSIRH creates element_workinghours and ReedCRM element_geolocation.
 *
 * @param  string        $moduleName Module name, in lower case
 * @return array<string>             Table names without prefix, sorted
 */
function saturne_get_module_tables(string $moduleName): array
{
    $sqlDir = dol_buildpath('/' . $moduleName . '/sql', 0);
    if (!is_dir($sqlDir)) {
        return [];
    }

    $tables   = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sqlDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (strtolower($file->getExtension()) !== 'sql') {
            continue;
        }

        $tables = array_merge($tables, saturne_thirdparty_created_tables((string) file_get_contents($file->getPathname())));
    }

    $tables = array_values(array_unique($tables));
    sort($tables);

    return $tables;
}

/**
 * Move to the kept thirdparty the rows of Saturne and of the enabled modules built on it that point at the absorbed
 * one. Societe::mergeCompany() only moves the core objects before deleting the absorbed thirdparty, and leaves the
 * objects of the external modules to the replaceThirdparty hook: without this, a DoliMeet session, a certificate or a
 * Digirisk external society kept pointing at a thirdparty that no longer exists.
 *
 * A row the unique key of its table refuses to move, because the kept thirdparty already holds the same one (ex.
 * reedcrm_digirisk_dismissed, unique on entity and fk_soc), is deleted: the row of the kept thirdparty wins, the other
 * one would only point at a deleted thirdparty. This fallback relies on MySQL/MariaDB carrying on with the
 * transaction after a refused statement.
 *
 * @param  DoliDB        $db       Database handler
 * @param  int           $originId Id of the absorbed thirdparty, deleted after the merge
 * @param  int           $destId   Id of the kept thirdparty
 * @param  array<string> $errors   Errors, filled on failure
 * @return int                     Number of rows moved or deleted, < 0 on error
 */
function saturne_replace_thirdparty(DoliDB $db, int $originId, int $destId, array &$errors = []): int
{
    if ($originId <= 0 || $destId <= 0 || $originId == $destId) {
        return 0;
    }

    require_once __DIR__ . '/object.lib.php';
    require_once __DIR__ . '/entity_transfer.lib.php';

    $tables = [];
    foreach (array_merge(['saturne'], saturne_get_dependent_modules()) as $moduleName) {
        $tables = array_merge($tables, saturne_get_module_tables($moduleName));
    }
    if (empty($tables)) {
        return 0;
    }

    $types     = "'" . implode("', '", saturne_thirdparty_element_types()) . "'";
    $nbHandled = 0;

    // Only the tables really created in the database: a module may ship the file of a table its version has not created yet
    foreach (saturne_entity_transfer_get_schema($db, array_values(array_unique($tables))) as $short => $columns) {
        $table      = MAIN_DB_PREFIX . $short;
        $primaryKey = saturne_entity_transfer_primary_key($columns);

        foreach (saturne_thirdparty_link_columns($columns) as $link) {
            $where = ' WHERE ' . $link['column'] . ' = ' . $originId;
            if ($link['type'] !== '') {
                $where .= ' AND ' . $link['type'] . ' IN (' . $types . ')';
            }

            $resql = $db->query('UPDATE ' . $table . ' SET ' . $link['column'] . ' = ' . $destId . $where);
            if ($resql) {
                $nbMoved = (int) $db->affected_rows($resql);
                if ($nbMoved > 0) {
                    dol_syslog(__FUNCTION__ . ' ' . $short . '.' . $link['column'] . ' : ' . $nbMoved . ' row(s) moved from thirdparty ' . $originId . ' to ' . $destId, LOG_INFO);
                }
                $nbHandled += $nbMoved;
                continue;
            }

            $result = saturne_replace_thirdparty_one_by_one($db, $table, $primaryKey, $link['column'], $where, $destId, $errors);
            if ($result < 0) {
                return -1;
            }
            $nbHandled += $result;
        }
    }

    return $nbHandled;
}

/**
 * Move the rows a whole table update refused one by one, and delete those the unique key of the table still refuses:
 * the kept thirdparty already holds the same row
 *
 * @param  DoliDB        $db         Database handler
 * @param  string        $table      Prefixed table name
 * @param  string        $primaryKey Primary key of the table, '' if it has none
 * @param  string        $column     Column holding the thirdparty id
 * @param  string        $where      WHERE clause selecting the rows of the absorbed thirdparty
 * @param  int           $destId     Id of the kept thirdparty
 * @param  array<string> $errors     Errors, filled on failure
 * @return int                       Number of rows moved or deleted, < 0 on error
 */
function saturne_replace_thirdparty_one_by_one(DoliDB $db, string $table, string $primaryKey, string $column, string $where, int $destId, array &$errors): int
{
    // Without a primary key the rows cannot be told apart: they would only point at a deleted thirdparty
    if ($primaryKey === '') {
        $resql = $db->query('DELETE FROM ' . $table . $where);
        if (!$resql) {
            $errors[] = $table . ' : ' . $db->lasterror();
            return -1;
        }
        dol_syslog(__FUNCTION__ . ' ' . $table . '.' . $column . ' : rows of the absorbed thirdparty deleted, the kept one already holds them', LOG_WARNING);
        return (int) $db->affected_rows($resql);
    }

    $resql = $db->query('SELECT ' . $primaryKey . ' as id FROM ' . $table . $where);
    if (!$resql) {
        $errors[] = $table . ' : ' . $db->lasterror();
        return -1;
    }

    $ids = [];
    while ($row = $db->fetch_object($resql)) {
        $ids[] = (int) $row->id;
    }
    $db->free($resql);

    $nbHandled = 0;
    foreach ($ids as $id) {
        $rowWhere = ' WHERE ' . $primaryKey . ' = ' . $id;
        if ($db->query('UPDATE ' . $table . ' SET ' . $column . ' = ' . $destId . $rowWhere)) {
            $nbHandled++;
            continue;
        }

        if (!$db->query('DELETE FROM ' . $table . $rowWhere)) {
            $errors[] = $table . ' : ' . $db->lasterror();
            return -1;
        }
        dol_syslog(__FUNCTION__ . ' ' . $table . '.' . $column . ' : row ' . $id . ' of the absorbed thirdparty deleted, the kept one already holds it', LOG_WARNING);
        $nbHandled++;
    }

    return $nbHandled;
}
