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
    // Saturne itself is analyzed; Dolibarr core directories are scanned for type
    // info only (all listed in exclude_analysis_directory_list below).
    'directory_list' => [
        SAT_MODULE_ROOT,
        // Dolibarr core — mirrors the sparse-checkout in quality.yml
        DOL_DOCUMENT_ROOT . '/categories/class/',
        DOL_DOCUMENT_ROOT . '/comm/action/class/',
        DOL_DOCUMENT_ROOT . '/comm/propal/class/',
        DOL_DOCUMENT_ROOT . '/commande/class/',
        DOL_DOCUMENT_ROOT . '/compta/facture/class/',
        DOL_DOCUMENT_ROOT . '/contact/class/',
        DOL_DOCUMENT_ROOT . '/contrat/class/',
        DOL_DOCUMENT_ROOT . '/core/class/',
        DOL_DOCUMENT_ROOT . '/core/lib/',
        DOL_DOCUMENT_ROOT . '/core/modules/',
        DOL_DOCUMENT_ROOT . '/core/triggers/',
        DOL_DOCUMENT_ROOT . '/core/tpl/',
        DOL_DOCUMENT_ROOT . '/ecm/class/',
        DOL_DOCUMENT_ROOT . '/expedition/class/',
        DOL_DOCUMENT_ROOT . '/fichinter/class/',
        DOL_DOCUMENT_ROOT . '/fourn/class/',
        DOL_DOCUMENT_ROOT . '/mrp/class/',
        DOL_DOCUMENT_ROOT . '/product/class/',
        DOL_DOCUMENT_ROOT . '/product/inventory/class/',
        DOL_DOCUMENT_ROOT . '/product/stock/class/',
        DOL_DOCUMENT_ROOT . '/projet/class/',
        DOL_DOCUMENT_ROOT . '/reception/class/',
        DOL_DOCUMENT_ROOT . '/societe/class/',
        DOL_DOCUMENT_ROOT . '/supplier_proposal/class/',
        DOL_DOCUMENT_ROOT . '/ticket/class/',
        DOL_DOCUMENT_ROOT . '/user/class/',

        // Phan-specific stubs and third-party test framework
        __DIR__ . '/stubs/',
        SAT_MODULE_ROOT . '/dev/phan/',
        SAT_MODULE_ROOT . '/vendor/phpunit/',
    ],

    // Scan all of the above for type info but do not report errors on them.
    'exclude_analysis_directory_list' => [
        DOL_DOCUMENT_ROOT . '/categories/class/',
        DOL_DOCUMENT_ROOT . '/comm/action/class/',
        DOL_DOCUMENT_ROOT . '/comm/propal/class/',
        DOL_DOCUMENT_ROOT . '/commande/class/',
        DOL_DOCUMENT_ROOT . '/compta/facture/class/',
        DOL_DOCUMENT_ROOT . '/contact/class/',
        DOL_DOCUMENT_ROOT . '/contrat/class/',
        DOL_DOCUMENT_ROOT . '/core/class/',
        DOL_DOCUMENT_ROOT . '/core/lib/',
        DOL_DOCUMENT_ROOT . '/core/modules/',
        DOL_DOCUMENT_ROOT . '/core/triggers/',
        DOL_DOCUMENT_ROOT . '/core/tpl/',
        DOL_DOCUMENT_ROOT . '/ecm/class/',
        DOL_DOCUMENT_ROOT . '/expedition/class/',
        DOL_DOCUMENT_ROOT . '/fichinter/class/',
        DOL_DOCUMENT_ROOT . '/fourn/class/',
        DOL_DOCUMENT_ROOT . '/mrp/class/',
        DOL_DOCUMENT_ROOT . '/product/class/',
        DOL_DOCUMENT_ROOT . '/product/inventory/class/',
        DOL_DOCUMENT_ROOT . '/product/stock/class/',
        DOL_DOCUMENT_ROOT . '/projet/class/',
        DOL_DOCUMENT_ROOT . '/reception/class/',
        DOL_DOCUMENT_ROOT . '/societe/class/',
        DOL_DOCUMENT_ROOT . '/supplier_proposal/class/',
        DOL_DOCUMENT_ROOT . '/ticket/class/',
        DOL_DOCUMENT_ROOT . '/user/class/',
        __DIR__ . '/stubs/',
        SAT_MODULE_ROOT . '/vendor/',
        SAT_MODULE_ROOT . '/node_modules/',
        SAT_MODULE_ROOT . '/dev/phan/',
        SAT_MODULE_ROOT . '/tests/',
    ],

    // Report only normal severity and above (no LOW).
    'minimum_severity' => 5,

    // Analysis depth — 3 is a good trade-off for a module.
    'quick_mode' => false,

    // ── Suppressed issues ────────────────────────────────────────────────────
    // Issues marked "noise" are suppressed because they produce too many false
    // positives in Dolibarr-style code and are better covered by PHPStan.
    // Issues marked "real bugs" are kept active and intentionally absent here.
    //
    // ACTIVE (real bugs worth fixing):
    //   PhanTypeMismatchArgument, PhanTypeMismatchReturn,
    //   PhanTypeMismatchPropertyReal, PhanParamSignatureMismatch,
    //   PhanTypeMismatchDefault, PhanPluginAlwaysReturnMethod
    'suppress_issue_types' => [
        // ── Pre-existing suppressions ─────────────────────────────────────
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
        // CommonObject is redefined in dev/phan/stubs.php to add $photo/$picto.
        'PhanRedefinedClassReference',

        // ── Dynamic properties (Dolibarr relies on them heavily) ──────────
        // Too many false positives; PHPStan is more reliable for this.
        'PhanUndeclaredProperty',

        // ── Undeclared methods / classes ──────────────────────────────────
        // PHPStan covers these more reliably with full type inference.
        'PhanUndeclaredMethod',
        // Third-party or runtime-loaded classes not worth stubbing.
        'PhanUndeclaredClass',
        'PhanUndeclaredExtendedClass',
        // Dolibarr uses static calls on string class names (legacy pattern).
        'PhanUndeclaredStaticMethod',

        // ── Dynamic constants ─────────────────────────────────────────────
        // STATUS_* constants are declared dynamically per object type.
        'PhanUndeclaredConstantOfClass',
        // MAIN_DB_PREFIX and similar are defined at runtime by main.inc.php.
        'PhanUndeclaredConstant',

        // ── Runtime-defined global functions ─────────────────────────────
        // llxHeader, llxFooter, etc. are defined by Dolibarr's include chain.
        'PhanUndeclaredFunction',

        // ── Stub / inheritance conflicts ──────────────────────────────────
        // Caused by CommonObject / DolibarrModules stub redeclarations.
        'PhanRedefinedExtendedClass',
        // $picto property type differs between stub and real CommonObject.
        'PhanIncompatibleRealPropertyType',

        // ── Variable scope (Dolibarr global variable pattern) ─────────────
        'PhanPossiblyUndeclaredGlobalVariable',
        'PhanPossiblyUndeclaredVariable',

        // ── Plugin noise ──────────────────────────────────────────────────
        // RedundantAssignment fires on global-scope bootstrap assignments.
        'PhanPluginRedundantAssignmentInGlobalScope',
        // DuplicateArrayKey fires on modSaturne's $this->tabs array literal.
        'PhanPluginDuplicateArrayKeyExpression',

        // ── Dolibarr legacy patterns ──────────────────────────────────────
        // Dolibarr core calls static methods on instances in several places.
        'PhanStaticCallToNonStatic',

        // ── Deprecated functions ──────────────────────────────────────────
        // strftime() is used for date formatting, not worth fixing now.
        'PhanDeprecatedFunction',
        'PhanDeprecatedFunctionInternal',

        // ── PHP version compatibility ─────────────────────────────────────
        // CI targets PHP 8.1 only; PHP 7 compatibility is not a goal.
        'PhanCompatiblePHP7',

        // ── Miscellaneous noise ───────────────────────────────────────────
        // __METHOD__ inside closures triggers this warning spuriously.
        'PhanSuspiciousMagicConstant',

        // ── Additional suppressions ───────────────────────────────────────
        // Dolibarr convention: '' is passed where int is expected (e.g. fetch(0,'') pattern).
        'PhanTypeMismatchArgumentProbablyReal',
        // False positive on $_FILES superglobal access.
        'PhanTypeArraySuspiciousNull',
        // False positive on $_FILES['file']['name'] and similar superglobal dims.
        'PhanTypeInvalidDimOffset',
        // False positive on pathinfo() return when 'extension' key may be absent.
        'PhanTypePossiblyInvalidDimOffset',
        // False positive on string interpolation with $_FILES values.
        'PhanTypeSuspiciousStringExpression',
        // Dolibarr pattern: ++ on variables that may be null/bool on first use.
        'PhanTypeInvalidUnaryOperandIncOrDec',
        // Dolibarr dynamic property access via $object->$propertyName pattern.
        'PhanTypeInvalidPropertyName',
        // implode() false positive (Phan gets confused with the two-arg form).
        'PhanParamSpecial1',

        // ── Signature mismatches (Dolibarr child overrides widen param types) ──
        // Saturne overrides add type hints that parents lack; Phan flags this.
        'PhanParamSignatureRealMismatchHasParamType',

        // ── False positives on Dolibarr dynamic object patterns ───────────────
        // Phan cannot resolve $object from generic fetch() chains.
        'PhanTypeExpectedObjectPropAccessButGotNull',
        // Phan cannot resolve method calls on variables typed as CommonObject subtypes.
        'PhanNonClassMethodCall',
        // Phan flags int arithmetic on variables that may be string after fetch().
        'PhanTypeInvalidLeftOperandOfNumericOp',
        // Phan flags echo/print of variables that may be non-string in Dolibarr patterns.
        'PhanTypeSuspiciousEcho',
    ],

    // Exclude files whose class declarations would duplicate what Phan already
    // sees from core/class/ or from dev/phan/stubs.php.
    'exclude_file_list' => [
        DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php',
        SAT_MODULE_ROOT . '/tests/phpunit/bootstrap.php',
        SAT_MODULE_ROOT . '/dev/phpstan/stubs.php',
    ],

    // File extensions to analyze.
    'file_list' => [
        DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php',
    ],

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
