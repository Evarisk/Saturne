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
 * \file    lib/maintenance.lib.php
 * \ingroup saturne
 * \brief   Library of the maintenance operations shared by admin/maintenance.php and the CLI scripts
 */

/**
 * Build the WHERE clause selecting the object document rows that name no file.
 *
 * A row of saturne_object_documents exists to name a produced file, in last_main_doc. Until #1634
 * the row was created before the output directory was resolved, so every generation failing after
 * that point left a row naming nothing. The column is never written empty, so IS NULL is exactly
 * that leftover set.
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $filters Optional 'entity' and 'type' restrictions
 * @return string          WHERE clause, leading space included
 */
function saturne_orphan_documents_filter(DoliDB $db, array $filters = []): string
{
    $where = ' WHERE last_main_doc IS NULL';

    if (!empty($filters['entity'])) {
        $where .= ' AND entity = ' . ((int) $filters['entity']);
    }
    if (!empty($filters['type'])) {
        $where .= " AND type = '" . $db->escape($filters['type']) . "'";
    }

    return $where;
}

/**
 * Count the object document rows that name no file, grouped by entity and type.
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $filters Optional 'entity' and 'type' restrictions
 * @return array           List of ['entity' => int, 'type' => string, 'nb' => int], empty on error
 */
function saturne_orphan_documents_count(DoliDB $db, array $filters = []): array
{
    $sql  = 'SELECT entity, type, COUNT(*) AS nb FROM ' . MAIN_DB_PREFIX . 'saturne_object_documents';
    $sql .= saturne_orphan_documents_filter($db, $filters);
    $sql .= ' GROUP BY entity, type ORDER BY entity, type';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog('saturne_orphan_documents_count: ' . $db->lasterror(), LOG_ERR);
        return [];
    }

    $rows = [];
    while ($object = $db->fetch_object($resql)) {
        $rows[] = ['entity' => (int) $object->entity, 'type' => $object->type, 'nb' => (int) $object->nb];
    }
    $db->free($resql);

    return $rows;
}

/**
 * Delete the object document rows that name no file.
 *
 * The table carries no extrafields and no child table, so the row is the whole record.
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $filters Optional 'entity' and 'type' restrictions
 * @return int             Number of deleted rows, -1 on error
 */
function saturne_orphan_documents_delete(DoliDB $db, array $filters = []): int
{
    $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'saturne_object_documents' . saturne_orphan_documents_filter($db, $filters);

    $db->begin();

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog('saturne_orphan_documents_delete: ' . $db->lasterror(), LOG_ERR);
        $db->rollback();
        return -1;
    }

    $deleted = $db->affected_rows($resql);
    $db->commit();

    return (int) $deleted;
}
