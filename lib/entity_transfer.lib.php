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
 * \file    lib/entity_transfer.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions to move one entity to a mono entity Dolibarr
 *
 * Shared by admin/entity_transfer.php, scripts/export_entity.php and scripts/import_entity.php.
 * MySQL / MariaDB only: the generated dump uses backquoted identifiers.
 */

/**
 * Placeholder compiled twice in a WHERE template: with the source entities to
 * select the rows to export, with the target entity to purge the destination.
 */
const SATURNE_TRANSFER_ENTITY_PLACEHOLDER = '{{ENTITIES}}';

/**
 * Tables never exported. Their primary keys are rebuilt by the module activation
 * on the target install, so carrying the source values over would create rows
 * pointing at the wrong definitions.
 *
 * @return array<string> Table names without the database prefix
 */
function saturne_entity_transfer_excluded_tables(): array
{
    return [
        'rights_def', 'user_rights', 'usergroup_rights',
        'menu', 'boxes', 'boxes_def', 'document_model',
        'events', 'session', 'notify', 'cronjob',
        'accounting_bookkeeping', 'accounting_bookkeeping_tmp',
        // The list of the entities themselves has no meaning on a mono entity install
        'entity', 'entity_extrafields'
    ];
}

/**
 * Table name patterns owned by the Saturne framework itself. The tables of the modules
 * built on it are added by the caller, one pattern per module taken along.
 *
 * @return array<string> Patterns matched with fnmatch() against the unprefixed table name
 */
function saturne_entity_transfer_base_patterns(): array
{
    return ['saturne_*'];
}

/**
 * Dictionary tables shipped by Saturne. Those of the modules follow the c_<module>_*
 * convention and are added by the caller, module by module: a dictionary named after
 * its object rather than after its module travels with --include-table.
 * Only exported on demand, because the module activation already fills them on the
 * target install.
 *
 * @return array<string> Patterns matched with fnmatch() against the unprefixed table name
 */
function saturne_entity_transfer_dictionary_patterns(): array
{
    return ['c_saturne_*'];
}

/**
 * Core Dolibarr tables the objects of the modules point at. Exported with --scope=core
 * so that users, third parties, tickets and projects still resolve after import.
 *
 * @return array<string> Patterns matched with fnmatch() against the unprefixed table name
 */
function saturne_entity_transfer_core_patterns(): array
{
    return [
        'user', 'user_extrafields', 'usergroup', 'usergroup_user',
        'societe', 'societe_extrafields', 'socpeople', 'socpeople_extrafields',
        'ticket', 'ticket_extrafields', 'projet', 'projet_extrafields',
        'projet_task', 'projet_task_extrafields', 'projet_task_time',
        'actioncomm', 'actioncomm_extrafields', 'actioncomm_resources',
        'categorie', 'categorie_lang', 'categories_extrafields',
        'ecm_files', 'ecm_files_extrafields', 'ecm_directories',
        'c_email_templates'
    ];
}

/**
 * Satellite tables shared by every module: they hold the rows of the export among
 * rows of other modules, so they always need their own WHERE clause.
 *
 * @return array<string> Table names without the database prefix
 */
function saturne_entity_transfer_satellite_tables(): array
{
    return ['const', 'extrafields', 'ecm_files', 'ecm_directories', 'element_element'];
}

/**
 * List the modules holding tables of their own, so the caller can offer to take them
 * along. Their tables belong to the entity just like the Saturne ones, and an export
 * leaving them behind carries signatures and documents pointing at nothing.
 *
 * @param  DoliDB                             $db     Database handler
 * @param  array<string,array<string,string>> $schema Database structure, read when not given
 * @return array<string>                              Module names, sorted
 */
function saturne_entity_transfer_modules(DoliDB $db, array $schema = []): array
{
    global $conf;

    if (empty($schema)) {
        $schema = saturne_entity_transfer_get_schema($db);
    }

    // Multicompany owns the entity list itself: its tables must never travel to a mono entity install
    $ignored = ['saturne', 'multicompany'];
    $modules = [];

    foreach ((array) $conf->modules as $module) {
        $module = strtolower($module);
        if (in_array($module, $ignored, true)) {
            continue;
        }

        // Only the modules installed under custom/: a core module named like the prefix of a
        // core table (user, societe, facture...) would otherwise land in the list and let the
        // caller drag half of Dolibarr into the dump
        if (!is_dir(DOL_DOCUMENT_ROOT . '/custom/' . $module)) {
            continue;
        }

        foreach ($schema as $short => $columns) {
            if (strpos($short, $module . '_') === 0 && isset($columns['entity'])) {
                $modules[] = $module;
                break;
            }
        }
    }

    sort($modules);

    return $modules;
}


/**
 * List the modules a dump really carries rows for, read from the tables of its manifest.
 * The export ticks every module of the source install, so its module list names modules
 * that brought nothing: requiring their activation on the target would block an import
 * that has no need of them.
 *
 * @param  array<string,mixed> $manifest Manifest of the dump
 * @return array<string>                 Module names, sorted
 */
function saturne_entity_transfer_dump_modules(array $manifest): array
{
    $declared = array_map('strtolower', (array) ($manifest['modules'] ?? []));
    if (empty($declared) || empty($manifest['tables'])) {
        return $declared;
    }

    $modules = [];

    foreach ((array) $manifest['tables'] as $table) {
        $short = (string) ($table['short'] ?? '');

        foreach ($declared as $module) {
            // A dictionary is named c_<module>_xxx, the tables of the objects <module>_xxx
            if (strpos($short, $module . '_') === 0 || strpos($short, 'c_' . $module . '_') === 0) {
                $modules[$module] = $module;
            }
        }
    }

    $modules = array_values($modules);
    sort($modules);

    return $modules;
}

/**
 * Map a Dolibarr element type (as stored in llx_element_element or in the name of a
 * llx_categorie_xxx link table) to the table holding those objects.
 *
 * The types of the modules are read from saturne_get_objects_metadata(), which every
 * module extends through the saturneExtendGetObjectsMetadata hook: no module is named
 * here. A type resolved by neither is read as the name of its own table, the convention
 * followed by the Saturne objects (digiriskdolibarr_risk => llx_digiriskdolibarr_risk).
 *
 * @return array<string,string> Element type => table name without the database prefix
 */
function saturne_entity_transfer_element_table_map(): array
{
    global $db, $hookmanager;

    static $map = null;

    if ($map !== null) {
        return $map;
    }

    $map = [
        'action'            => 'actioncomm',
        'agenda'            => 'actioncomm',
        'commande'          => 'commande',
        'contact'           => 'socpeople',
        'contrat'           => 'contrat',
        'expedition'        => 'expedition',
        'facture'           => 'facture',
        'facture_fourn'     => 'facture_fourn',
        'fichinter'         => 'fichinter',
        'invoice'           => 'facture',
        'invoice_supplier'  => 'facture_fourn',
        'member'            => 'adherent',
        'order'             => 'commande',
        'order_supplier'    => 'commande_fournisseur',
        'product'           => 'product',
        'productlot'        => 'product_lot',
        'project'           => 'projet',
        'project_task'      => 'projet_task',
        'propal'            => 'propal',
        'reception'         => 'reception',
        'shipping'          => 'expedition',
        'societe'           => 'societe',
        'supplier_proposal' => 'supplier_proposal',
        'task'              => 'projet_task',
        'thirdparty'        => 'societe',
        'ticket'            => 'ticket',
        'user'              => 'user'
    ];

    require_once __DIR__ . '/object.lib.php';

    // A CLI script loads master.inc.php only, which leaves the hook manager unset,
    // and saturne_get_objects_metadata() opens its hook straight away
    if (!is_object($hookmanager)) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
        $hookmanager = new HookManager($db);
    }

    // The metadata instantiate one object per module: a module shipping a broken class
    // must not take the whole export down with it
    try {
        foreach (saturne_get_objects_metadata() as $type => $metadata) {
            // An alias duplicates another entry under a legacy name, both point at the same table
            $table = (string) ($metadata['table_element'] ?? '');
            if (empty($table)) {
                continue;
            }

            foreach ([(string) $type, (string) ($metadata['link_name'] ?? '')] as $elementType) {
                if (!empty($elementType) && !isset($map[$elementType])) {
                    $map[$elementType] = $table;
                }
            }
        }
    } catch (Throwable $exception) {
        dol_syslog('saturne_entity_transfer_element_table_map: ' . $exception->getMessage(), LOG_WARNING);
    }

    return $map;
}

/**
 * Child tables whose owner cannot be guessed from their name. The xxx_extrafields
 * convention is handled separately and does not need an entry here.
 *
 * @return array<string,array{parent:string,key:string}> Child table => owner table and foreign key
 */
function saturne_entity_transfer_child_map(): array
{
    return [
        'projet_task'         => ['parent' => 'projet',      'key' => 'fk_projet'],
        'projet_task_time'    => ['parent' => 'projet_task', 'key' => 'fk_task'],
        'usergroup_user'      => ['parent' => 'usergroup',   'key' => 'fk_usergroup'],
        'categorie_lang'      => ['parent' => 'categorie',   'key' => 'fk_category'],
        'actioncomm_resources' => ['parent' => 'actioncomm', 'key' => 'fk_actioncomm'],
        // The table is plural while its owner is not, the xxx_extrafields convention misses it
        'categories_extrafields' => ['parent' => 'categorie', 'key' => 'fk_object'],
        // Line tables whose foreign key does not repeat the name of their owner
        'commande_fournisseurdet' => ['parent' => 'commande_fournisseur', 'key' => 'fk_commande'],
        'expeditiondet_batch'     => ['parent' => 'expeditiondet',        'key' => 'fk_expeditiondet'],
        'hrm_skilldet'            => ['parent' => 'hrm_skill',            'key' => 'fk_skill'],
        'product_batch'           => ['parent' => 'product_stock',        'key' => 'fk_product_stock'],
        'receptiondet_batch'      => ['parent' => 'reception',            'key' => 'fk_reception'],
        'subscription'            => ['parent' => 'adherent',             'key' => 'fk_adherent']
    ];
}

/**
 * List the database views, to keep them out of the introspection. SHOW FULL TABLES is
 * MySQL only: on another engine the list stays empty and the introspection keeps its
 * former behaviour rather than logging a failed query.
 *
 * @param  DoliDB            $db Database handler
 * @return array<string,int>     Prefixed view name => 1
 */
function saturne_entity_transfer_view_names(DoliDB $db): array
{
    $views = [];

    if ($db->type !== 'mysqli') {
        return $views;
    }

    $resql = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    if (!$resql) {
        return $views;
    }

    while ($row = $db->fetch_row($resql)) {
        $views[$row[0]] = 1;
    }
    $db->free($resql);

    return $views;
}

/**
 * Read the structure of the database tables.
 *
 * @param  DoliDB                            $db       Database handler
 * @param  array<string>                     $patterns Only introspect the tables matching one of those patterns, all of them if empty
 * @return array<string,array<string,string>>           Unprefixed table name => column name => column type
 */
function saturne_entity_transfer_get_schema(DoliDB $db, array $patterns = []): array
{
    $schema = [];
    $views  = saturne_entity_transfer_view_names($db);

    foreach ($db->DDLListTables($db->database_name) as $table) {
        if (strpos($table, MAIN_DB_PREFIX) !== 0) {
            continue;
        }

        // DDLListTables() returns the views too. Introspecting one whose underlying table
        // is gone raises a warning on every call, and a view carrying an entity column
        // would enter the plan, with INSERT statements written against it
        if (isset($views[$table])) {
            continue;
        }

        $short = substr($table, strlen(MAIN_DB_PREFIX));
        if (!empty($patterns) && !saturne_entity_transfer_match($short, $patterns)) {
            continue;
        }

        $columns = [];
        foreach ($db->DDLInfoTable($table) as $info) {
            // The rows of SHOW FULL COLUMNS come back as numeric arrays, name then type,
            // while the core signature only promises an array: read them by position
            $info = array_values((array) $info);
            if (count($info) < 2) {
                continue;
            }

            $columns[(string) $info[0]] = strtolower((string) $info[1]);
        }

        $schema[$short] = $columns;
    }

    ksort($schema);

    return $schema;
}

/**
 * Tell whether a table name matches one of the given patterns.
 *
 * @param  string        $table    Unprefixed table name
 * @param  array<string> $patterns Patterns understood by fnmatch()
 * @return bool
 */
function saturne_entity_transfer_match(string $table, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        if ($table === $pattern || fnmatch($pattern, $table)) {
            return true;
        }
    }

    return false;
}

/**
 * Return the primary key of a table.
 *
 * @param  array<string,string> $columns Column name => column type
 * @return string                        Primary key name, empty if the table has none
 */
function saturne_entity_transfer_primary_key(array $columns): string
{
    foreach (['rowid', 'id'] as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }

    return '';
}

/**
 * Guess the owner of a table whose name is the name of its owner plus a suffix:
 * llx_facturedet belongs to llx_facture, llx_societe_commerciaux to llx_societe.
 * The longest matching prefix wins, and the foreign key must really exist, so a
 * wrong guess ends up unresolved and reported instead of silently filtering rows.
 *
 * @param  string                            $short  Unprefixed table name
 * @param  array<string,array<string,string>> $schema Database structure
 * @return array{parent:string,key:string}|null      Owner table and foreign key, null when not resolved
 */
function saturne_entity_transfer_guess_parent(string $short, array $schema): ?array
{
    // Foreign keys Dolibarr names after an alias rather than after the table
    $aliases = ['societe' => 'soc', 'projet' => 'project', 'adherent' => 'member'];

    for ($length = strlen($short) - 1; $length > 2; $length--) {
        $candidate = rtrim(substr($short, 0, $length), '_');

        if ($candidate === $short || !isset($schema[$candidate])) {
            continue;
        }

        $keys = ['fk_' . $candidate];
        if (isset($aliases[$candidate])) {
            $keys[] = 'fk_' . $aliases[$candidate];
        }

        foreach ($keys as $key) {
            if (isset($schema[$short][$key])) {
                return ['parent' => $candidate, 'key' => $key];
            }
        }
    }

    return null;
}

/**
 * Build the WHERE template restricting a child table to the rows owned by its parent.
 * Recursive: a grand child produces nested sub queries up to the table holding the entity.
 *
 * @param  string                            $short   Unprefixed child table name
 * @param  array<string,array<string,string>> $schema  Database structure
 * @param  int                               $depth   Current recursion depth
 * @return array{where:string,depth:int}|null         WHERE template and depth of the chain, null when unresolved
 */
function saturne_entity_transfer_child_where(string $short, array $schema, int $depth = 0): ?array
{
    if ($depth > 4) {
        return null;
    }

    $childMap = saturne_entity_transfer_child_map();

    if (isset($childMap[$short])) {
        $parent = $childMap[$short]['parent'];
        $key    = $childMap[$short]['key'];
    } elseif (substr($short, -12) === '_extrafields') {
        $parent = substr($short, 0, -12);
        $key    = 'fk_object';
    } else {
        $guess = saturne_entity_transfer_guess_parent($short, $schema);
        if ($guess === null) {
            return null;
        }

        $parent = $guess['parent'];
        $key    = $guess['key'];
    }

    if (!isset($schema[$parent]) || !isset($schema[$short][$key])) {
        return null;
    }

    $parentKey = saturne_entity_transfer_primary_key($schema[$parent]);
    if (empty($parentKey)) {
        return null;
    }

    if (isset($schema[$parent]['entity'])) {
        $parentWhere = 'entity IN (' . SATURNE_TRANSFER_ENTITY_PLACEHOLDER . ')';
    } else {
        $parentResult = saturne_entity_transfer_child_where($parent, $schema, $depth + 1);
        if ($parentResult === null) {
            return null;
        }
        $parentWhere = $parentResult['where'];
        $depth       = $parentResult['depth'];
    }

    $where = $key . ' IN (SELECT ' . $parentKey . ' FROM ' . MAIN_DB_PREFIX . $parent . ' WHERE ' . $parentWhere . ')';

    return ['where' => $where, 'depth' => $depth + 1];
}

/**
 * Build the WHERE template of a llx_categorie_xxx link table, restricted to the
 * categorised objects of the entity.
 *
 * @param  string                            $short  Unprefixed link table name
 * @param  array<string,array<string,string>> $schema Database structure
 * @return string|null                                WHERE template, null when the linked table is unknown
 */
function saturne_entity_transfer_category_link_where(string $short, array $schema): ?string
{
    $type = substr($short, strlen('categorie_'));
    $map  = saturne_entity_transfer_element_table_map();

    $target = $map[$type] ?? $type;
    if (!isset($schema[$target]) || !isset($schema[$target]['entity'])) {
        return null;
    }

    foreach (array_keys($schema[$short]) as $column) {
        if ($column === 'fk_categorie' || strpos($column, 'fk_') !== 0) {
            continue;
        }

        return $column . ' IN (SELECT ' . saturne_entity_transfer_primary_key($schema[$target])
            . ' FROM ' . MAIN_DB_PREFIX . $target . ' WHERE entity IN (' . SATURNE_TRANSFER_ENTITY_PLACEHOLDER . '))';
    }

    return null;
}

/**
 * Build the WHERE template of llx_element_element. Both ends of a link must be
 * exported, otherwise the import would create links pointing at rows that do not
 * exist on the target, which breaks fetchObjectLinked().
 *
 * @param  DoliDB                            $db      Database handler
 * @param  array<string,array<string,string>> $schema  Database structure
 * @param  array<string>                     $allowed Unprefixed names of the tables already selected for the export
 * @return string|null                                WHERE template, null when no element type could be resolved
 */
function saturne_entity_transfer_element_element_where(DoliDB $db, array $schema, array $allowed): ?string
{
    $map = saturne_entity_transfer_element_table_map();

    $sql  = 'SELECT DISTINCT sourcetype AS type FROM ' . MAIN_DB_PREFIX . 'element_element';
    $sql .= ' UNION SELECT DISTINCT targettype AS type FROM ' . MAIN_DB_PREFIX . 'element_element';

    $resql = $db->query($sql);
    if (!$resql) {
        return null;
    }

    $types = [];
    while ($obj = $db->fetch_object($resql)) {
        $types[] = $obj->type;
    }
    $db->free($resql);

    $sourceConditions = [];
    $targetConditions = [];

    foreach ($types as $type) {
        $table = $map[$type] ?? $type;

        // The end must be a table of the export, and entity scoped so the sub query can filter it
        if (!in_array($table, $allowed, true) || !isset($schema[$table]['entity'])) {
            continue;
        }

        $subQuery = 'SELECT ' . saturne_entity_transfer_primary_key($schema[$table]) . ' FROM ' . MAIN_DB_PREFIX . $table
            . ' WHERE entity IN (' . SATURNE_TRANSFER_ENTITY_PLACEHOLDER . ')';

        $sourceConditions[] = "(sourcetype = '" . $db->escape($type) . "' AND fk_source IN (" . $subQuery . '))';
        $targetConditions[] = "(targettype = '" . $db->escape($type) . "' AND fk_target IN (" . $subQuery . '))';
    }

    if (empty($sourceConditions)) {
        return null;
    }

    return '(' . implode(' OR ', $sourceConditions) . ') AND (' . implode(' OR ', $targetConditions) . ')';
}

/**
 * Build the WHERE template of the tables shared by every module.
 *
 * @param  string              $short   Unprefixed table name
 * @param  array<string,mixed> $options Export options
 * @return string|null                  WHERE template, null when the table needs no special case
 */
function saturne_entity_transfer_satellite_where(string $short, array $options): ?string
{
    $entityWhere = 'entity IN (' . SATURNE_TRANSFER_ENTITY_PLACEHOLDER . ')';

    // Only the module scope narrows those tables down to the rows of Saturne and of the
    // modules taken along: as soon as the core objects travel too, their settings, their
    // extrafields and their files must follow
    $moduleScope = ($options['scope'] === 'module');
    $prefixes    = array_merge(['saturne'], array_map('strtolower', (array) ($options['modules'] ?? [])));

    switch ($short) {
        case 'const':
            // MAIN_MODULE_ constants are written by the module activation: importing them
            // would flag the modules as enabled without creating their menus and directories
            $where = $entityWhere . " AND name NOT LIKE 'MAIN_MODULE_%'";
            if ($moduleScope) {
                $where .= ' AND ' . saturne_entity_transfer_prefix_condition('name', $prefixes, true);
            }
            return $where;

        case 'extrafields':
            // A definition written by a module activation carries entity 0, shared by every
            // entity: filtered on the entity of the export, the table comes out empty and the
            // target install then knows none of the custom fields the exported rows fill in
            $sharedWhere = '(' . $entityWhere . ' OR entity = 0)';

            if (!$moduleScope) {
                return $sharedWhere;
            }
            return $sharedWhere . ' AND ' . saturne_entity_transfer_prefix_condition('elementtype', $prefixes);

        case 'ecm_files':
        case 'ecm_directories':
            if (!$moduleScope) {
                return $entityWhere;
            }
            return $entityWhere . ' AND ' . saturne_entity_transfer_prefix_condition(($short === 'ecm_files' ? 'filepath' : 'label'), $prefixes);
    }

    return null;
}

/**
 * Build the condition keeping the rows of a column starting with one of the given prefixes.
 *
 * @param  string        $column    Column name
 * @param  array<string> $prefixes  Module prefixes
 * @param  bool          $uppercase Compare on the upper case prefix, as the constants are named
 * @return string                   Parenthesized condition
 */
function saturne_entity_transfer_prefix_condition(string $column, array $prefixes, bool $uppercase = false): string
{
    $conditions = [];

    foreach ($prefixes as $prefix) {
        $prefix = preg_replace('/[^a-z0-9_]/', '', strtolower($prefix));
        if (empty($prefix)) {
            continue;
        }

        $conditions[] = $column . " LIKE '" . ($uppercase ? strtoupper($prefix) : $prefix) . "%'";
    }

    // No prefix left means no row of the export in this table
    if (empty($conditions)) {
        return '1 = 0';
    }

    return '(' . implode(' OR ', $conditions) . ')';
}

/**
 * Sort weight of a table, so that the dump inserts owners before the rows pointing at them.
 *
 * @param  string                $short   Unprefixed table name
 * @param  array<string,mixed>   $table   Table description of the plan
 * @return int                            Lower is inserted first
 */
function saturne_entity_transfer_weight(string $short, array $table): int
{
    if (strpos($short, 'c_') === 0) {
        return 10;
    }

    $priorities = [
        'user'      => 20,
        'usergroup' => 20,
        'societe'   => 21,
        'socpeople' => 22,
        'projet'    => 23,
        'categorie' => 24
    ];

    if (isset($priorities[$short])) {
        return $priorities[$short];
    }

    if ($table['origin'] === 'entity') {
        return 30;
    }

    if ($table['origin'] === 'parent') {
        return 40 + (int) $table['depth'];
    }

    return 50;
}

/**
 * Build the list of the tables to export and the WHERE template of each of them.
 *
 * @param  DoliDB              $db      Database handler
 * @param  array<string,mixed> $options Export options: scope, with_dictionaries, include, exclude
 * @return array{tables:array<string,array<string,mixed>>,skipped:array<string,string>}
 */
function saturne_entity_transfer_build_plan(DoliDB $db, array $options): array
{
    $schema = saturne_entity_transfer_get_schema($db);

    $patterns = saturne_entity_transfer_base_patterns();
    if ($options['scope'] !== 'module') {
        $patterns = array_merge($patterns, saturne_entity_transfer_core_patterns());
    }
    if (!empty($options['with_dictionaries'])) {
        $patterns = array_merge($patterns, saturne_entity_transfer_dictionary_patterns());
    }

    // Modules taken along: their own tables and their dictionaries
    foreach ((array) ($options['modules'] ?? []) as $module) {
        $module = preg_replace('/[^a-z0-9_]/', '', strtolower($module));
        if (empty($module)) {
            continue;
        }

        $patterns[] = $module . '_*';
        if (!empty($options['with_dictionaries'])) {
            $patterns[] = 'c_' . $module . '_*';
        }
    }
    $patterns = array_merge($patterns, saturne_entity_transfer_satellite_tables(), $options['include']);

    $excluded  = array_merge(saturne_entity_transfer_excluded_tables(), $options['exclude']);
    $satellite = saturne_entity_transfer_satellite_tables();

    $tables  = [];
    $skipped = [];

    foreach ($schema as $short => $columns) {
        if (saturne_entity_transfer_match($short, $excluded)) {
            continue;
        }

        $selected = ($options['scope'] === 'full') || saturne_entity_transfer_match($short, $patterns);
        if (!$selected) {
            continue;
        }

        // Deferred: its WHERE needs the list of the tables selected by this very loop
        if ($short === 'element_element') {
            continue;
        }

        $where = null;
        $origin = '';
        $depth  = 0;

        // A dictionary row, like an extrafield definition, is written by a module activation
        // and carries entity 0, the Dolibarr convention for a value shared by every entity:
        // filtering it on the entity of the export leaves the table empty, whatever the entity
        $shared = ((strpos($short, 'c_') === 0 || $short === 'extrafields') && isset($columns['entity']));

        if (in_array($short, $satellite, true)) {
            $where  = saturne_entity_transfer_satellite_where($short, $options);
            $origin = 'custom';
        }

        if ($where === null && $shared) {
            $where  = '(entity IN (' . SATURNE_TRANSFER_ENTITY_PLACEHOLDER . ') OR entity = 0)';
            $origin = 'entity';
        }

        if ($where === null && isset($columns['entity'])) {
            $where  = 'entity IN (' . SATURNE_TRANSFER_ENTITY_PLACEHOLDER . ')';
            $origin = 'entity';
        }

        if ($where === null && strpos($short, 'categorie_') === 0 && isset($columns['fk_categorie'])) {
            $where  = saturne_entity_transfer_category_link_where($short, $schema);
            $origin = 'custom';
        }

        if ($where === null) {
            $child = saturne_entity_transfer_child_where($short, $schema);
            if ($child !== null) {
                $where  = $child['where'];
                $origin = 'parent';
                $depth  = $child['depth'];
            }
        }

        if ($where === null) {
            // Core dictionaries are the same on every install and belong to no entity:
            // reporting them would bury the tables that really lose data
            if (strpos($short, 'c_') !== 0 || saturne_entity_transfer_match($short, $options['include'])) {
                $skipped[$short] = 'No entity column and no owner table resolved';
            }
            continue;
        }

        $tables[$short] = [
            'name'           => MAIN_DB_PREFIX . $short,
            'short'          => $short,
            'columns'        => $columns,
            'where_template' => $where,
            'origin'         => $origin,
            'depth'          => $depth,
            'shared'         => $shared,
            'primary_key'    => saturne_entity_transfer_primary_key($columns)
        ];
    }

    if (isset($schema['element_element']) && !saturne_entity_transfer_match('element_element', $excluded)) {
        $where = saturne_entity_transfer_element_element_where($db, $schema, array_keys($tables));

        if ($where === null) {
            $skipped['element_element'] = 'No exported element type on both ends of the links';
        } else {
            $tables['element_element'] = [
                'name'           => MAIN_DB_PREFIX . 'element_element',
                'short'          => 'element_element',
                'columns'        => $schema['element_element'],
                'where_template' => $where,
                'origin'         => 'custom',
                'depth'          => 0,
                'primary_key'    => saturne_entity_transfer_primary_key($schema['element_element'])
            ];
        }
    }

    uasort($tables, function ($a, $b) {
        $weightA = saturne_entity_transfer_weight($a['short'], $a);
        $weightB = saturne_entity_transfer_weight($b['short'], $b);

        if ($weightA === $weightB) {
            return strcmp($a['short'], $b['short']);
        }

        return ($weightA < $weightB ? -1 : 1);
    });

    return ['tables' => $tables, 'skipped' => $skipped];
}

/**
 * Compile a WHERE template with the entities it must apply to.
 *
 * @param  string        $template WHERE template holding the entity placeholder
 * @param  array<int>    $entities Entity identifiers
 * @return string                  Ready to use WHERE clause
 */
function saturne_entity_transfer_compile_where(string $template, array $entities): string
{
    $entities = array_map('intval', $entities);

    return str_replace(SATURNE_TRANSFER_ENTITY_PLACEHOLDER, implode(', ', $entities), $template);
}

/**
 * Format a value as a SQL literal. Binary columns are written as hexadecimal
 * literals, every other value is escaped, which also removes the line breaks:
 * a statement of the dump therefore always fits on a single line.
 *
 * @param  DoliDB     $db    Database handler
 * @param  string|null $value Raw value read from the database
 * @param  string     $type  Column type
 * @return string            SQL literal
 */
function saturne_entity_transfer_format_value(DoliDB $db, ?string $value, string $type): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (preg_match('/(blob|binary)/', $type)) {
        return ($value === '' ? "''" : '0x' . bin2hex($value));
    }

    return "'" . $db->escape($value) . "'";
}

/**
 * Split the arguments of a CLI script.
 *
 * @param  array<string>       $argv Arguments of the script, $argv[0] included
 * @return array<string,mixed>       Option name without the leading dashes => value, true for a flag
 */
function saturne_entity_transfer_parse_args(array $argv): array
{
    $arguments = [];

    foreach (array_slice($argv, 1) as $argument) {
        if (strpos($argument, '--') !== 0) {
            continue;
        }

        $argument = substr($argument, 2);
        $position = strpos($argument, '=');

        if ($position === false) {
            $arguments[$argument] = true;
        } else {
            $arguments[substr($argument, 0, $position)] = substr($argument, $position + 1);
        }
    }

    return $arguments;
}

/**
 * Return the document directory of an entity.
 *
 * @param  int    $entity Entity identifier
 * @return string         Absolute path without trailing slash
 */
function saturne_entity_transfer_data_root(int $entity): string
{
    return DOL_DATA_ROOT . ($entity > 1 ? '/' . $entity : '');
}

/**
 * Create a directory and its parents. dol_mkdir() runs the path through
 * dol_sanitizePathName(), which rewrites it (a double dash becomes an underscore),
 * so it cannot be trusted for a directory chosen by the caller.
 *
 * @param  string $dir Absolute path
 * @return bool        True when the directory exists at the end
 */
function saturne_entity_transfer_mkdir(string $dir): bool
{
    if (is_dir($dir)) {
        return true;
    }

    @mkdir($dir, 0755, true);

    if (!is_dir($dir)) {
        dol_mkdir($dir);
    }

    return is_dir($dir);
}

/**
 * Copy a directory tree, for the same reason as saturne_entity_transfer_mkdir():
 * dolCopyDir() creates its destination directories with dol_mkdir().
 *
 * @param  string        $source   Directory to copy
 * @param  string        $target   Destination directory
 * @param  array<string> $excludes Directory names skipped at any level
 * @return int                     Number of files copied, -1 on failure
 */
function saturne_entity_transfer_copy_dir(string $source, string $target, array $excludes = ['temp']): int
{
    if (!is_dir($source) || !saturne_entity_transfer_mkdir($target)) {
        return -1;
    }

    $copied  = 0;
    $entries = scandir($source);

    if ($entries === false) {
        return -1;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || is_link($source . '/' . $entry)) {
            continue;
        }

        if (is_dir($source . '/' . $entry)) {
            if (in_array($entry, $excludes, true)) {
                continue;
            }

            $result = saturne_entity_transfer_copy_dir($source . '/' . $entry, $target . '/' . $entry, $excludes);
            if ($result < 0) {
                return -1;
            }

            $copied += $result;
            continue;
        }

        if (!@copy($source . '/' . $entry, $target . '/' . $entry)) {
            return -1;
        }

        $copied++;
    }

    return $copied;
}

/**
 * Count the rows every table of the plan will export, and tell which tables hold
 * rows that no filter could reach. An unexportable table only matters when it is
 * not empty: saying so is the only way the caller knows what stays behind.
 *
 * @param  DoliDB              $db       Database handler
 * @param  array<string,mixed> $plan     Plan built by saturne_entity_transfer_build_plan()
 * @param  array<int>          $entities Entities to export
 * @return array{counts:array<string,int>,exported:array<string,array<string,mixed>>,total:int,skipped:array<string,string>}
 */
function saturne_entity_transfer_count_plan(DoliDB $db, array $plan, array $entities): array
{
    $counts   = [];
    $exported = [];
    $total    = 0;
    $skipped  = [];

    foreach ($plan['tables'] as $short => $table) {
        $where = saturne_entity_transfer_compile_where($table['where_template'], $entities);

        $resql = $db->query('SELECT COUNT(*) AS nb FROM ' . $table['name'] . ' WHERE ' . $where);
        if (!$resql) {
            $skipped[$short] = $db->lasterror();
            continue;
        }

        $object = $db->fetch_object($resql);
        $db->free($resql);

        $counts[$short] = (!empty($object) ? (int) $object->nb : 0);
        $total         += $counts[$short];

        if ($counts[$short] > 0) {
            $exported[$short] = $table;
        }
    }

    foreach ($plan['skipped'] as $short => $reason) {
        $resql = $db->query('SELECT COUNT(*) AS nb FROM ' . MAIN_DB_PREFIX . $short);
        if (!$resql) {
            $skipped[$short] = $reason;
            continue;
        }

        $object      = $db->fetch_object($resql);
        $rowsInTable = (!empty($object) ? (int) $object->nb : 0);
        $db->free($resql);

        if ($rowsInTable > 0) {
            $skipped[$short] = $reason . ', ' . $rowsInTable . ' rows in the table';
        }
    }

    return ['counts' => $counts, 'exported' => $exported, 'total' => $total, 'skipped' => $skipped];
}

/**
 * Export the tables of one entity into a dump replayable on a mono entity Dolibarr.
 * Shared by scripts/export_entity.php and admin/entity_transfer.php.
 *
 * @param  DoliDB              $db      Database handler
 * @param  array<string,mixed> $options entities, target_entity, scope, output_dir and the flags of the CLI script
 * @return array<string,mixed>          dir, sql_file, sql_path, rows, counts, skipped, documents, manifest, errors
 */
function saturne_entity_transfer_export(DoliDB $db, array $options): array
{
    $options += [
        'entities'          => [1],
        'target_entity'     => 1,
        'scope'             => 'module',
        'with_dictionaries' => false,
        'with_files'        => false,
        'purge'             => false,
        'modules'           => [],
        'insert_mode'       => 'insert',
        'chunk'             => 200,
        'include'           => [],
        'exclude'           => [],
        'output_dir'        => '',
        'plan'              => null,
        'counted'           => null
    ];

    $result = ['dir' => $options['output_dir'], 'rows' => 0, 'errors' => [], 'documents' => ['included' => false]];

    if (!saturne_entity_transfer_mkdir($options['output_dir'])) {
        $result['errors'][] = 'Cannot create the output directory ' . $options['output_dir'];
        return $result;
    }

    $plan    = $options['plan'] ?? saturne_entity_transfer_build_plan($db, $options);
    $counted = $options['counted'] ?? saturne_entity_transfer_count_plan($db, $plan, $options['entities']);

    $result['counts']  = $counted['counts'];
    $result['total']   = $counted['total'];
    $result['skipped'] = $counted['skipped'];

    $sqlFileName = 'saturne_entity_' . ((int) $options['entities'][0]) . '.sql';
    $sqlPath     = $options['output_dir'] . '/' . $sqlFileName;

    $handle = fopen($sqlPath, 'w');
    if ($handle === false) {
        $result['errors'][] = 'Cannot write ' . $sqlPath;
        return $result;
    }

    $insertVerb = ($options['insert_mode'] === 'replace' ? 'REPLACE INTO' : ($options['insert_mode'] === 'ignore' ? 'INSERT IGNORE INTO' : 'INSERT INTO'));

    fwrite($handle, "-- Saturne entity export\n");
    fwrite($handle, '-- Generated     : ' . dol_print_date(dol_now(), 'standard') . "\n");
    fwrite($handle, '-- Source        : ' . $db->database_name . ' entity ' . implode(', ', $options['entities']) . ' (prefix ' . MAIN_DB_PREFIX . ")\n");
    fwrite($handle, '-- Target entity : ' . (int) $options['target_entity'] . "\n");
    fwrite($handle, '-- Scope         : ' . $options['scope'] . "\n");
    fwrite($handle, "-- Enable the modules of this dump on the target install BEFORE replaying it.\n");
    fwrite($handle, "SET NAMES utf8mb4;\n");
    fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");

    if (!empty($options['purge'])) {
        fwrite($handle, "-- Purge of the target entity, children first\n");
        foreach (array_reverse($counted['exported'], true) as $table) {
            $purgeWhere = saturne_entity_transfer_compile_where($table['where_template'], [(int) $options['target_entity']]);
            fwrite($handle, 'DELETE FROM ' . $table['name'] . ' WHERE ' . $purgeWhere . ";\n");
        }
    }

    $manifestTables = [];

    foreach ($counted['exported'] as $short => $table) {
        $where       = saturne_entity_transfer_compile_where($table['where_template'], $options['entities']);
        $columnNames = array_keys($table['columns']);
        $columnList  = '`' . implode('`, `', $columnNames) . '`';
        $primaryKey  = $table['primary_key'];

        // The module activation already filled the dictionaries of the target install with
        // the very same primary keys: a plain INSERT would fail on each of them, so the rows
        // of the export take their place and carry the values edited on the source
        $tableVerb = (!empty($table['shared']) ? 'REPLACE INTO' : $insertVerb);

        fwrite($handle, '-- ' . $table['name'] . ' (' . $counted['counts'][$short] . " rows)\n");

        // Read by pages, a table like llx_actioncomm does not fit in memory as a whole.
        // A table without primary key cannot be paged safely, it is read in one go
        $pageSize = 2000;
        $offset   = 0;
        $rows     = [];
        $bytes    = 0;

        do {
            $sql = 'SELECT ' . $columnList . ' FROM ' . $table['name'] . ' WHERE ' . $where;
            if (!empty($primaryKey)) {
                $sql .= ' ORDER BY ' . $primaryKey . ' LIMIT ' . $pageSize . ' OFFSET ' . $offset;
            }

            $resql = $db->query($sql);
            if (!$resql) {
                $result['errors'][] = $table['name'] . ' : ' . $db->lasterror();
                break;
            }

            $fetched = 0;
            while ($row = $db->fetch_array($resql)) {
                $values = [];
                foreach ($columnNames as $column) {
                    if ($column === 'entity') {
                        // Entity 0 means "every entity": rewriting it to the target would nail
                        // a shared value to a single entity
                        $sharedRow = (!empty($table['shared']) && (int) $row[$column] === 0);
                        $values[]  = (string) ($sharedRow ? 0 : (int) $options['target_entity']);
                        continue;
                    }

                    $values[] = saturne_entity_transfer_format_value($db, $row[$column], $table['columns'][$column]);
                }

                $tuple  = '(' . implode(', ', $values) . ')';
                $rows[] = $tuple;
                $bytes += strlen($tuple);
                $fetched++;
                $result['rows']++;

                // Flush on the row count and on the statement size, to stay below max_allowed_packet
                if (count($rows) >= $options['chunk'] || $bytes > 2000000) {
                    fwrite($handle, $tableVerb . ' ' . $table['name'] . ' (' . $columnList . ') VALUES ' . implode(', ', $rows) . ";\n");
                    $rows  = [];
                    $bytes = 0;
                }
            }
            $db->free($resql);

            $offset += $pageSize;
        } while (!empty($primaryKey) && $fetched === $pageSize);

        if (!empty($rows)) {
            fwrite($handle, $tableVerb . ' ' . $table['name'] . ' (' . $columnList . ') VALUES ' . implode(', ', $rows) . ";\n");
        }

        $manifestTables[] = [
            'name'  => $table['name'],
            'short' => $short,
            'rows'  => $counted['counts'][$short],
            'purge' => saturne_entity_transfer_compile_where($table['where_template'], [(int) $options['target_entity']])
        ];
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($handle);

    if (!empty($options['with_files'])) {
        $sourceRoot = saturne_entity_transfer_data_root((int) $options['entities'][0]);

        // Module scope: only the document directories of the modules of the export travel,
        // the whole tree of the entity otherwise
        $sourceDirs = ['' => $sourceRoot];
        if ($options['scope'] === 'module') {
            $sourceDirs = [];
            foreach (array_merge(['saturne'], (array) $options['modules']) as $module) {
                $module = preg_replace('/[^a-z0-9_]/', '', strtolower($module));
                if (!empty($module) && is_dir($sourceRoot . '/' . $module)) {
                    $sourceDirs[$module] = $sourceRoot . '/' . $module;
                }
            }
        }

        if (empty($sourceDirs)) {
            $result['errors'][] = 'No document directory found under ' . $sourceRoot . ', no document copied';
        } else {
            $copiedFiles = 0;

            foreach ($sourceDirs as $module => $sourceDir) {
                $targetDir = $options['output_dir'] . '/documents' . ($module !== '' ? '/' . $module : '');

                $copied = saturne_entity_transfer_copy_dir($sourceDir, $targetDir);
                if ($copied < 0) {
                    $result['errors'][] = 'Copy of ' . $sourceDir . ' failed';
                    continue;
                }

                $copiedFiles += $copied;
            }

            $result['documents'] = ['included' => true, 'source' => $sourceRoot, 'path' => 'documents', 'files' => $copiedFiles];
        }
    }

    $manifest = [
        'generated'        => dol_print_date(dol_now(), 'standard'),
        'dolibarr_version' => DOL_VERSION,
        'saturne_version'  => getDolGlobalString('SATURNE_VERSION'),
        'source'           => [
            'database' => $db->database_name,
            'prefix'   => MAIN_DB_PREFIX,
            'entities' => array_map('intval', $options['entities'])
        ],
        'target_entity'    => (int) $options['target_entity'],
        'scope'            => $options['scope'],
        'modules'          => array_values((array) $options['modules']),
        'insert_mode'      => $options['insert_mode'],
        'sql_file'         => $sqlFileName,
        'rows'             => $result['rows'],
        'tables'           => $manifestTables,
        'skipped_tables'   => $counted['skipped'],
        // Read again as structured data: replaying the rows of llx_extrafields tells the target
        // install which custom fields exist, but only a DDL creates the columns holding them
        'extrafields'      => saturne_entity_transfer_extrafield_definitions($db, $counted['exported'], $options['entities']),
        'documents'        => $result['documents']
    ];

    file_put_contents($options['output_dir'] . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $result['sql_file'] = $sqlFileName;
    $result['sql_path'] = $sqlPath;
    $result['manifest'] = $manifest;

    return $result;
}

/**
 * Read the definitions of the custom fields the exported rows fill in. They travel in the
 * manifest, not only as rows of llx_extrafields: replaying those rows tells the target
 * install that a field exists, while the column holding its values is created by a DDL.
 *
 * @param  DoliDB                     $db       Database handler
 * @param  array<string,array<string,mixed>> $exported Tables of the export, as counted
 * @param  array<int>                 $entities Source entities
 * @return array<int,array<string,mixed>>       Definitions, empty when llx_extrafields is out of the export
 */
function saturne_entity_transfer_extrafield_definitions(DoliDB $db, array $exported, array $entities): array
{
    if (empty($exported['extrafields'])) {
        return [];
    }

    $where  = saturne_entity_transfer_compile_where($exported['extrafields']['where_template'], $entities);
    $fields = [];

    $resql = $db->query('SELECT name, entity, elementtype, label, type, size, param, pos, alwayseditable, perms, list, fielddefault, fieldcomputed, fieldrequired, fieldunique, langs, enabled, totalizable, printable, help'
        . ' FROM ' . MAIN_DB_PREFIX . 'extrafields WHERE ' . $where . ' ORDER BY elementtype, pos');

    if (!$resql) {
        return [];
    }

    while ($object = $db->fetch_object($resql)) {
        $fields[] = [
            'name'           => $object->name,
            'entity'         => (int) $object->entity,
            'elementtype'    => $object->elementtype,
            'label'          => $object->label,
            'type'           => $object->type,
            'size'           => $object->size,
            'param'          => $object->param,
            'pos'            => (int) $object->pos,
            'alwayseditable' => (int) $object->alwayseditable,
            'perms'          => $object->perms,
            'list'           => $object->list,
            'default'        => $object->fielddefault,
            'computed'       => $object->fieldcomputed,
            'required'       => (int) $object->fieldrequired,
            'unique'         => (int) $object->fieldunique,
            'langfile'       => $object->langs,
            'enabled'        => $object->enabled,
            'totalizable'    => (int) $object->totalizable,
            'printable'      => (int) $object->printable,
            'help'           => $object->help
        ];
    }
    $db->free($resql);

    return $fields;
}

/**
 * Create on this install the columns holding the custom fields of the dump. A definition
 * replayed into llx_extrafields declares a field, it does not add the column its values
 * are written to: without this every INSERT into a table of extra fields fails on an
 * unknown column, and the values of the entity are lost without the rest noticing.
 *
 * A table absent here belongs to a module this install does not have. Creating it would
 * put back a structure whose owner is missing, so the case is reported, not repaired.
 *
 * @param  DoliDB              $db       Database handler
 * @param  array<string,mixed> $manifest Manifest of the dump
 * @return array{created:int,messages:array<string>} Columns created, and what could not be
 */
function saturne_entity_transfer_prepare_extrafields(DoliDB $db, array $manifest): array
{
    global $langs;

    $result = ['created' => 0, 'messages' => []];

    if (empty($manifest['extrafields'])) {
        return $result;
    }

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    $extrafields = new ExtraFields($db);
    $schema      = saturne_entity_transfer_get_schema($db);
    $missing     = [];

    foreach ((array) $manifest['extrafields'] as $field) {
        $elementType = (string) ($field['elementtype'] ?? '');
        $name        = (string) ($field['name'] ?? '');

        if (empty($elementType) || empty($name)) {
            continue;
        }

        // The names the core rewrites before reaching the table
        $table = ['thirdparty' => 'societe', 'contact' => 'socpeople', 'categorie' => 'categories'][$elementType] ?? $elementType;
        $table .= '_extrafields';

        if (!isset($schema[$table])) {
            $missing[$table] = $table;
            continue;
        }

        if (isset($schema[$table][strtolower($name)])) {
            continue;
        }

        $done = $extrafields->addExtraField(
            $name,
            ($field['label'] ?? $name),
            ($field['type'] ?? 'varchar'),
            (int) ($field['pos'] ?? 0),
            ($field['size'] ?? ''),
            $elementType,
            (int) ($field['unique'] ?? 0),
            (int) ($field['required'] ?? 0),
            ($field['default'] ?? ''),
            ($field['param'] ?? ''),
            (int) ($field['alwayseditable'] ?? 0),
            ($field['perms'] ?? ''),
            ($field['list'] ?? '-1'),
            ($field['help'] ?? ''),
            ($field['computed'] ?? ''),
            (string) ($field['entity'] ?? ''),
            ($field['langfile'] ?? ''),
            ($field['enabled'] ?? '1'),
            (int) ($field['totalizable'] ?? 0),
            (int) ($field['printable'] ?? 0)
        );

        // addExtraField() adds the column, then writes the definition. The definition may
        // already be there, replayed by an earlier import, and the call then reports the
        // duplicate although the column it was called for now exists: the column decides
        $columns = saturne_entity_transfer_get_schema($db, [$table]);

        if ($done > 0 || isset($columns[$table][strtolower($name)])) {
            $result['created']++;
            $schema[$table][strtolower($name)] = ($field['type'] ?? 'varchar');
        } else {
            $result['messages'][] = 'Custom field ' . $name . ' of ' . $elementType . ' : ' . ($extrafields->error ?: 'creation failed');
        }
    }

    foreach ($missing as $table) {
        $result['messages'][] = MAIN_DB_PREFIX . $table . ' does not exist here: the module owning it is not installed, its custom fields are not imported';
    }

    return $result;
}

/**
 * Name the table one statement of the dump writes into, without its prefix.
 *
 * @param  string $statement SQL statement of the dump
 * @return string            Unprefixed table name, empty when the statement targets none
 */
function saturne_entity_transfer_statement_table(string $statement): string
{
    if (!preg_match("/^(?:INSERT(?: IGNORE)? INTO|REPLACE INTO|DELETE FROM)\s+`?" . preg_quote(MAIN_DB_PREFIX, "/") . "([a-z0-9_]+)`?/i", $statement, $matches)) {
        return "";
    }

    return strtolower($matches[1]);
}

/**
 * Rewrite the table prefix of one statement, for a target install using another one.
 * Only the names following INTO, FROM, JOIN, UPDATE or TABLE are touched: replacing the
 * prefix everywhere would rewrite it inside the exported values too, and a row holding
 * llx_ in a text, a path or the options of a sellist field would come out corrupted.
 *
 * @param  string $statement    SQL statement of the dump
 * @param  string $sourcePrefix Prefix of the install the dump comes from
 * @return string               Statement written against the tables of this install
 */
function saturne_entity_transfer_rewrite_prefix(string $statement, string $sourcePrefix): string
{
    $pattern = '/(\b(?:INTO|FROM|JOIN|UPDATE|TABLE)\s+`?)' . preg_quote($sourcePrefix, '/') . '/i';

    return (string) preg_replace($pattern, '${1}' . MAIN_DB_PREFIX, $statement);
}

/**
 * Replay a dump produced by saturne_entity_transfer_export().
 * Shared by scripts/import_entity.php and admin/entity_transfer.php.
 *
 * @param  DoliDB              $db      Database handler
 * @param  string              $sqlFile Path of the dump to replay
 * @param  array<string,mixed> $options manifest, purge, dry_run, stop_on_error, documents_dir, target_entity
 * @return array<string,mixed>          statements, executed, purged, errors, messages, checks
 */
function saturne_entity_transfer_import(DoliDB $db, string $sqlFile, array $options): array
{
    $options += [
        'manifest'      => [],
        'purge'         => false,
        'dry_run'       => false,
        'stop_on_error' => false,
        'documents_dir' => '',
        'target_entity' => 1
    ];

    $manifest     = $options['manifest'];
    $sourcePrefix = (string) ($manifest['source']['prefix'] ?? MAIN_DB_PREFIX);

    $result = ['statements' => 0, 'executed' => 0, 'purged' => 0, 'errors' => 0, 'messages' => [], 'checks' => [], 'documents' => 0, 'extrafields' => 0];

    // Before anything is replayed: the INSERT of a table of extra fields names its columns,
    // they have to exist by then
    if (empty($options['dry_run'])) {
        $prepared              = saturne_entity_transfer_prepare_extrafields($db, $manifest);
        $result['extrafields'] = $prepared['created'];
        $result['messages']    = array_merge($result['messages'], $prepared['messages']);
    }

    $run = function (string $sql) use ($db, $options, &$result): bool {
        if (!empty($options['dry_run'])) {
            return true;
        }

        if (!$db->query($sql)) {
            $result['errors']++;
            $result['messages'][] = $db->lasterror() . ' | ' . dol_trunc($sql, 200);
            return false;
        }

        return true;
    };

    $schema        = saturne_entity_transfer_get_schema($db);
    $skippedTables = [];

    if (!empty($options['purge'])) {
        if (empty($manifest['tables'])) {
            $result['messages'][] = 'The purge needs the manifest.json of the export';
            $result['errors']++;
            return $result;
        }

        // Children first, the manifest lists the tables in insertion order
        foreach (array_reverse($manifest['tables']) as $table) {
            $name  = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['name']);
            $where = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['purge']);

            if (!isset($schema[strtolower((string) ($table['short'] ?? ''))])) {
                continue;
            }

            if ($run('DELETE FROM ' . $name . ' WHERE ' . $where)) {
                $result['purged']++;
            }
        }
    }

    $handle = fopen($sqlFile, 'r');
    if ($handle === false) {
        $result['messages'][] = 'Cannot read ' . $sqlFile;
        $result['errors']++;
        return $result;
    }

    $buffer = '';

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");

        if ($line === '' || strpos($line, '--') === 0) {
            continue;
        }

        // The export escapes every value, so a statement never spans several lines.
        // The buffer only protects against a dump edited by hand
        $buffer .= ($buffer === '' ? '' : "\n") . $line;
        if (substr($buffer, -1) !== ';') {
            continue;
        }

        $statement = substr($buffer, 0, -1);
        $buffer    = '';

        if ($sourcePrefix !== MAIN_DB_PREFIX) {
            $statement = saturne_entity_transfer_rewrite_prefix($statement, $sourcePrefix);
        }

        $result['statements']++;

        // A table of the dump that this install does not have belongs to a module absent here.
        // Running the statement would only pile up SQL errors saying the same thing on every
        // chunk of the table, so it is skipped and the table is named once
        $target = saturne_entity_transfer_statement_table($statement);
        if ($target !== '' && !isset($schema[$target])) {
            if (!isset($skippedTables[$target])) {
                $skippedTables[$target] = 0;
                $result['messages'][]   = MAIN_DB_PREFIX . $target . ' does not exist here: the module owning it is not installed, its rows are not imported';
            }

            $skippedTables[$target]++;
            continue;
        }

        if ($run($statement)) {
            $result['executed']++;
        } elseif (!empty($options['stop_on_error'])) {
            $result['messages'][] = 'Stopped on the first error';
            break;
        }
    }

    fclose($handle);

    $result['skipped_tables'] = $skippedTables;

    if (!empty($options['documents_dir']) && is_dir($options['documents_dir']) && empty($options['dry_run'])) {
        $targetRoot = saturne_entity_transfer_data_root((int) $options['target_entity']);

        $copied = saturne_entity_transfer_copy_dir($options['documents_dir'], $targetRoot);
        if ($copied < 0) {
            $result['errors']++;
            $result['messages'][] = 'Copy of the documents failed';
        } else {
            $result['documents'] = $copied;
        }
    }

    if (!empty($manifest['tables']) && empty($options['dry_run'])) {
        foreach ($manifest['tables'] as $table) {
            $name  = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['name']);
            $where = str_replace($sourcePrefix, MAIN_DB_PREFIX, $table['purge']);

            // Already reported when its statements were skipped, counting its rows would
            // only repeat that the table is not here
            if (!isset($schema[strtolower((string) ($table['short'] ?? ''))])) {
                continue;
            }

            $resql = $db->query('SELECT COUNT(*) AS nb FROM ' . $name . ' WHERE ' . $where);
            if (!$resql) {
                $result['messages'][] = $name . ' : ' . $db->lasterror();
                continue;
            }

            $object = $db->fetch_object($resql);
            $db->free($resql);

            $result['checks'][] = ['name' => $name, 'found' => (!empty($object) ? (int) $object->nb : 0), 'expected' => (int) $table['rows']];
        }
    }

    return $result;
}
