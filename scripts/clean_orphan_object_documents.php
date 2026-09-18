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
 * \file    scripts/clean_orphan_object_documents.php
 * \ingroup saturne
 * \brief   Remove the saturne_object_documents rows left behind by a failed generation
 *
 * Usage: php scripts/clean_orphan_object_documents.php [--entity=<id>] [--type=<type>] [--go]
 *
 * A row of that table exists to name a produced file, in last_main_doc. Until issue #1634 the row
 * was created before the output directory was resolved, so every generation that failed after that
 * point left a row naming no file — 26% of the table at one customer. The column is never written
 * empty, so "last_main_doc IS NULL" is exactly that leftover set.
 *
 * Nothing is written without --go.
 */

if (php_sapi_name() !== 'cli') {
    print 'This script must be run from the command line.' . "\n";
    exit(1);
}

define('INC_FROM_CRON_SCRIPT', true);

// Load Dolibarr environment
$res = @include __DIR__ . '/../../../master.inc.php';
if (!$res) {
    $res = @include __DIR__ . '/../../../../master.inc.php';
}
if (!$res) {
    die("Include of main fails\n");
}

require_once __DIR__ . '/../lib/entity_transfer.lib.php';

global $db;

$arguments = saturne_entity_transfer_parse_args($argv);

if (isset($arguments['help'])) {
    print "\n";
    print "Remove the saturne_object_documents rows left behind by a failed generation.\n";
    print "\n";
    print "Usage: php scripts/clean_orphan_object_documents.php [options]\n";
    print "\n";
    print "  --entity=<id>   Only that entity (default: every entity)\n";
    print "  --type=<type>   Only that document type, e.g. workunitdocument (default: every type)\n";
    print "  --go            Actually delete. Without it the script only counts.\n";
    print "\n";
    exit(0);
}

$table  = MAIN_DB_PREFIX . 'saturne_object_documents';
$where  = ' WHERE last_main_doc IS NULL';
$where .= isset($arguments['entity']) ? ' AND entity = ' . ((int) $arguments['entity']) : '';
$where .= isset($arguments['type']) ? " AND type = '" . $db->escape($arguments['type']) . "'" : '';

$resql = $db->query('SELECT entity, type, COUNT(*) AS nb FROM ' . $table . $where . ' GROUP BY entity, type ORDER BY entity, type');
if (!$resql) {
    print 'SQL error: ' . $db->lasterror() . "\n";
    exit(1);
}

$total = 0;
print "\n";
printf("%-8s %-32s %s\n", 'entity', 'type', 'rows');
while ($object = $db->fetch_object($resql)) {
    printf("%-8d %-32s %d\n", $object->entity, $object->type, $object->nb);
    $total += (int) $object->nb;
}
$db->free($resql);

print "\n" . $total . " orphan row(s)\n";

if ($total == 0) {
    exit(0);
}

if (empty($arguments['go'])) {
    print "Dry run: nothing deleted. Add --go to delete them.\n\n";
    exit(0);
}

$db->begin();

$resql = $db->query('DELETE FROM ' . $table . $where);
if (!$resql) {
    print 'SQL error: ' . $db->lasterror() . "\n";
    $db->rollback();
    exit(1);
}

$deleted = $db->affected_rows($resql);
$db->commit();

print $deleted . " row(s) deleted.\n\n";

exit(0);
