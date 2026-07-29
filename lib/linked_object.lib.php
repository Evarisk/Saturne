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
