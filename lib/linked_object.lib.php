<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/linked_object.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions to drive linked objects from a module admin page
 */

/**
 * Keep only the object types a module may actually link to
 *
 * Alias entries, duplicated table elements and the objects excluded by the caller are dropped, so that
 * a consumer iterating on the result never processes the same database table twice.
 *
 * @param  array $objectsMetadata          Result of saturne_get_objects_metadata()
 * @param  array $excludedLinkNamePrefixes Link name prefixes to drop, example ['digiquali_']
 * @return array                           Subset of $objectsMetadata, original keys preserved
 */
function saturne_filter_linkable_objects(array $objectsMetadata, array $excludedLinkNamePrefixes = []): array
{
    $linkableObjects = [];
    $seenTables      = [];

    foreach ($objectsMetadata as $objectType => $objectMetadata) {
        if (!empty($objectMetadata['alias_of'])) {
            continue;
        }

        $tableElement = $objectMetadata['table_element'] ?? '';
        if (empty($tableElement) || isset($seenTables[$tableElement])) {
            continue;
        }

        $linkName = $objectMetadata['link_name'] ?? '';
        foreach ($excludedLinkNamePrefixes as $excludedLinkNamePrefix) {
            if (strpos($linkName, $excludedLinkNamePrefix) === 0) {
                continue 2;
            }
        }

        $seenTables[$tableElement]    = true;
        $linkableObjects[$objectType] = $objectMetadata;
    }

    return $linkableObjects;
}

/**
 * Get the object types whose link is enabled by configuration
 *
 * @param  array  $linkableObjects Result of saturne_filter_linkable_objects()
 * @param  string $constPrefix     Configuration constant prefix, example 'DIGIQUALI_SHEET_LINK_'
 * @return array                   List of enabled object types
 */
function saturne_get_enabled_linked_object_types(array $linkableObjects, string $constPrefix): array
{
    $enabledObjectTypes = [];

    foreach (array_keys($linkableObjects) as $objectType) {
        if (getDolGlobalInt($constPrefix . strtoupper($objectType)) > 0) {
            $enabledObjectTypes[] = $objectType;
        }
    }

    return $enabledObjectTypes;
}

/**
 * Get the tables on which the given extrafields are currently declared
 *
 * Read once and reused, so that a missing column never turns into a failing count query.
 *
 * @param  array $extraFieldNames Extrafield names to look for
 * @return array                  name => [elementtype => true]
 */
function saturne_get_existing_extrafields(array $extraFieldNames): array
{
    global $db;

    $existingExtraFields = [];

    if (empty($extraFieldNames)) {
        return $existingExtraFields;
    }

    $escapedNames = [];
    foreach ($extraFieldNames as $extraFieldName) {
        $escapedNames[] = "'" . $db->escape($extraFieldName) . "'";
    }

    $sql  = 'SELECT name, elementtype FROM ' . MAIN_DB_PREFIX . 'extrafields';
    $sql .= ' WHERE name IN (' . implode(', ', $escapedNames) . ')';

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $existingExtraFields[$obj->name][$obj->elementtype] = true;
        }
        $db->free($resql);
    }

    return $existingExtraFields;
}

/**
 * Measure how much each linkable object is actually used
 *
 * Feeds the usage column of an admin page, sizes the confirmation shown before a destructive
 * toggle, and lets a module backward keep every link that already carries data.
 *
 * @param  array $linkableObjects    Result of saturne_filter_linkable_objects()
 * @param  array $extraFieldNames    Extrafield names to count, example ['qc_frequency']
 * @param  array $linkedElementTypes Element types on the module side, example ['digiquali_control']
 * @return array                     objectType => ['links' => int, 'extrafields' => [name => int]]
 */
function saturne_get_linked_object_usage(
    array $linkableObjects,
    array $extraFieldNames,
    array $linkedElementTypes
): array {
    global $db;

    $usage             = [];
    $objectTypeByLink  = [];
    $tableByObjectType = [];

    foreach ($linkableObjects as $objectType => $objectMetadata) {
        $usage[$objectType] = ['links' => 0, 'extrafields' => []];
        foreach ($extraFieldNames as $extraFieldName) {
            $usage[$objectType]['extrafields'][$extraFieldName] = 0;
        }

        $objectTypeByLink[$objectMetadata['link_name']] = $objectType;
        $tableByObjectType[$objectType]                 = $objectMetadata['table_element'];
    }

    if (!empty($linkedElementTypes)) {
        $escapedElementTypes = [];
        foreach ($linkedElementTypes as $linkedElementType) {
            $escapedElementTypes[] = "'" . $db->escape($linkedElementType) . "'";
        }
        $inClause = implode(', ', $escapedElementTypes);

        $sql  = 'SELECT sourcetype, targettype, COUNT(*) as nb';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_element';
        $sql .= ' WHERE sourcetype IN (' . $inClause . ') OR targettype IN (' . $inClause . ')';
        $sql .= ' GROUP BY sourcetype, targettype';

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $isSourceOnModuleSide = in_array($obj->sourcetype, $linkedElementTypes, true);
                $linkedSide           = $isSourceOnModuleSide ? $obj->targettype : $obj->sourcetype;
                if (isset($objectTypeByLink[$linkedSide])) {
                    $usage[$objectTypeByLink[$linkedSide]]['links'] += (int) $obj->nb;
                }
            }
            $db->free($resql);
        }
    }

    $existingExtraFields = saturne_get_existing_extrafields($extraFieldNames);

    foreach ($tableByObjectType as $objectType => $tableElement) {
        foreach ($extraFieldNames as $extraFieldName) {
            if (!isset($existingExtraFields[$extraFieldName][$tableElement])) {
                continue;
            }

            $sql  = 'SELECT COUNT(*) as nb FROM ' . MAIN_DB_PREFIX . $tableElement . '_extrafields';
            $sql .= ' WHERE ' . $extraFieldName . " IS NOT NULL AND " . $extraFieldName . " <> ''";

            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);

                $usage[$objectType]['extrafields'][$extraFieldName] = (int) $obj->nb;
                $db->free($resql);
            }
        }
    }

    return $usage;
}

/**
 * Add or remove the extrafields carried by the linked objects
 *
 * Idempotent : adding an already declared extrafield and deleting an absent one are both no-ops,
 * so the function can be replayed at will.
 *
 * Only the objects present in $linkableObjects are touched. An extrafield left on the table of a
 * module that no longer contributes metadata is deliberately kept : that module may simply be
 * disabled, and dropping its column would destroy data.
 *
 * @param  array $definitions        Extrafield definitions, each one holding the keys name, label, type,
 *                                   pos, size, default_value, param, alwayseditable, list, langfile,
 *                                   enabled, and object_types. An empty object_types means every enabled
 *                                   object, a filled one restricts the definition to the listed types.
 * @param  array $linkableObjects    Result of saturne_filter_linkable_objects()
 * @param  array $enabledObjectTypes Result of saturne_get_enabled_linked_object_types()
 * @return array                     ['added' => string[], 'deleted' => string[], 'errors' => int]
 */
function saturne_sync_linked_object_extrafields(
    array $definitions,
    array $linkableObjects,
    array $enabledObjectTypes
): array {
    global $db;

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    $extraFields = new ExtraFields($db);
    $report      = ['added' => [], 'deleted' => [], 'errors' => 0];

    $extraFieldNames = [];
    foreach ($definitions as $definition) {
        $extraFieldNames[] = $definition['name'];
    }
    $existingExtraFields = saturne_get_existing_extrafields($extraFieldNames);

    foreach ($definitions as $definition) {
        foreach ($linkableObjects as $objectType => $objectMetadata) {
            $tableElement = $objectMetadata['table_element'];

            $restrictedTypes = $definition['object_types'];

            $isEnabled  = in_array($objectType, $enabledObjectTypes, true);
            $isInScope  = empty($restrictedTypes) || in_array($objectType, $restrictedTypes, true);
            $isWanted   = $isEnabled && $isInScope;
            $isDeclared = isset($existingExtraFields[$definition['name']][$tableElement]);

            if ($isWanted && !$isDeclared) {
                $result = $extraFields->addExtraField(
                    $definition['name'],
                    $definition['label'],
                    $definition['type'],
                    $definition['pos'],
                    $definition['size'],
                    $tableElement,
                    0,
                    0,
                    $definition['default_value'],
                    $definition['param'],
                    $definition['alwayseditable'],
                    '',
                    $definition['list'],
                    '',
                    '',
                    0,
                    $definition['langfile'],
                    $definition['enabled']
                );

                if ($result > 0) {
                    $report['added'][] = $definition['name'] . ' @ ' . $tableElement;
                } else {
                    $report['errors']++;
                }
            } elseif (!$isWanted && $isDeclared) {
                $result = $extraFields->delete($definition['name'], $tableElement);

                if ($result >= 0) {
                    $report['deleted'][] = $definition['name'] . ' @ ' . $tableElement;
                } else {
                    $report['errors']++;
                }
            }
        }
    }

    return $report;
}
