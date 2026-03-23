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
 * Phan configuration for the Saturne module.
 *
 * NOTE: Requires the php-ast extension. Cannot run locally on PHP 7.4
 * without this extension. This config is used exclusively in CI (PHP 8.1).
 *
 * Run: vendor/bin/phan --config-file=.phan/config.php
 *      (or: php vendor/bin/phan --config-file=.phan/config.php)
 */

// Dolibarr root is two directories above this module (htdocs/).
define('SAT_MODULE_ROOT', __DIR__ . '/..');
define('DOL_DOCUMENT_ROOT', realpath(SAT_MODULE_ROOT . '/../../'));

return [
    // PHP version to target in CI.
    'target_php_version' => '8.1',

    // Directories to include in analysis.
    // Saturne itself is analyzed; Dolibarr core is scanned for type info only.
    'directory_list' => [
        SAT_MODULE_ROOT,
        DOL_DOCUMENT_ROOT . '/core/class/',
        DOL_DOCUMENT_ROOT . '/core/lib/',
        __DIR__ . '/stubs/',
    ],

    // Scan Dolibarr core and stubs but do not report errors in them.
    'exclude_analysis_directory_list' => [
        DOL_DOCUMENT_ROOT . '/core/class/',
        DOL_DOCUMENT_ROOT . '/core/lib/',
        __DIR__ . '/stubs/',
        SAT_MODULE_ROOT . '/vendor/',
        SAT_MODULE_ROOT . '/node_modules/',
    ],

    // Report only normal severity and above (no LOW).
    'minimum_severity' => 5,

    // Analysis depth — 3 is a good trade-off for a module.
    'quick_mode' => false,

    // Suppress issues common in Dolibarr-style code.
    'suppress_issue_types' => [
        'PhanDeprecatedProperty',
        'PhanDeprecatedImplicitNullableParam',
        'PhanCompatibleNegativeStringOffset',
        'PhanTypeArraySuspiciousNullable',
        'PhanUnreferencedUseNormal',
        'PhanReadonlyPropertyNotPositioned',
        // Dolibarr globals are set by main.inc.php, not declared.
        'PhanUndeclaredGlobalVariable',
        // Template files (*.tpl.php) use extract() and globals heavily.
        'PhanUndeclaredVariable',
    ],

    // File extensions to analyze.
    'file_list' => [],

    // Exclude compiled/generated assets.
    'exclude_file_regex' => '@(vendor|node_modules|css|js|documents|langs)/.*@',

    // Enable the most useful plugins.
    'plugins' => [
        'AlwaysReturnPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'UnreachableCodePlugin',
        'UseReturnValuePlugin',
        'RedundantAssignmentPlugin',
    ],
];
