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
 * \file    lib/asset.lib.php
 * \ingroup saturne
 * \brief   Utility functions to append a build version id on CSS/JS assets URLs
 *
 * The version id is the file mtime : it changes with every release, and the
 * release zip, built by git archive, stamps every file with the commit date so
 * the URL stays identical across every server of a same release.
 *
 * A module may still ship an assets-manifest.json holding a short content hash
 * for every compiled asset : when it has an entry for the asset, that hash wins.
 */

/**
 * Load - once per request - the build manifest of a module
 *
 * @param  string $moduleName Module name (e.g. 'saturne', 'reedcrm')
 * @return array              Manifest content : ['css/saturne.min.css' => 'a1b2c3d4', ...]
 */
function saturne_get_assets_manifest(string $moduleName): array
{
    static $manifests = [];

    if (isset($manifests[$moduleName])) {
        return $manifests[$moduleName];
    }

    $manifests[$moduleName] = [];

    $manifestPath = dol_buildpath('/' . $moduleName . '/assets-manifest.json', 0);
    if (is_readable($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (is_array($manifest)) {
            $manifests[$moduleName] = $manifest;
        } else {
            dol_syslog('saturne_get_assets_manifest: unreadable JSON in ' . $manifestPath, LOG_WARNING);
        }
    }

    return $manifests[$moduleName];
}

/**
 * Get the build version id of an asset
 *
 * @param  string $relativePath Relative path of the asset, with or without the /custom prefix (e.g. '/saturne/css/saturne.min.css')
 * @return string               Version id, or an empty string when it cannot be resolved
 */
function saturne_asset_version(string $relativePath): string
{
    // External assets (CDN) are not ours to version
    if (preg_match('#^(https?:)?//#i', $relativePath)) {
        return '';
    }

    // Drop any query string / anchor already set by the caller, and the optional /custom prefix
    $cleanPath = preg_replace('/[?#].*$/', '', $relativePath);
    $cleanPath = ltrim((string) $cleanPath, '/');
    $cleanPath = preg_replace('#^custom/#', '', $cleanPath);

    $segments = explode('/', (string) $cleanPath, 2);
    if (count($segments) < 2 || empty($segments[1])) {
        return '';
    }
    list($moduleName, $assetPath) = $segments;

    // Every caller passes a hardcoded path, so anything else is a coding error we don't want to turn into a file probe
    if (!preg_match('/^[a-z0-9_-]+$/i', $moduleName) || strpos($assetPath, '..') !== false) {
        return '';
    }

    $manifest = saturne_get_assets_manifest($moduleName);
    if (!empty($manifest[$assetPath])) {
        return (string) $manifest[$assetPath];
    }

    // Manifest missing or not up to date : fall back on the file mtime
    $assetFullPath = dol_buildpath('/' . $moduleName . '/' . $assetPath, 0);
    if (is_readable($assetFullPath)) {
        return (string) filemtime($assetFullPath);
    }

    return '';
}

/**
 * Append the build version id to the relative path of an asset
 *
 * Use it for assets handed over to llxHeader() through $arrayofcss / $arrayofjs,
 * as Dolibarr builds the final URL itself
 *
 * @param  string $relativePath Relative path of the asset (e.g. '/saturne/css/saturne.min.css')
 * @return string               Same path suffixed with its version id
 */
function saturne_asset_url(string $relativePath): string
{
    $version = saturne_asset_version($relativePath);
    if (empty($version)) {
        return $relativePath;
    }

    return $relativePath . (strpos($relativePath, '?') === false ? '?' : '&') . 'v=' . $version;
}

/**
 * Build the full URL of an asset, version id included
 *
 * Use it when printing the <link> / <script> tag yourself, typically in an
 * addHtmlHeader hook
 *
 * @param  string $relativePath Relative path of the asset (e.g. '/saturne/js/saturne.min.js')
 * @return string               Full URL suffixed with its version id
 */
function saturne_asset_full_url(string $relativePath): string
{
    $version = saturne_asset_version($relativePath);
    $url     = dol_buildpath($relativePath, 1);

    if (empty($version)) {
        return $url;
    }

    return $url . (strpos($url, '?') === false ? '?' : '&') . 'v=' . $version;
}

/**
 * Append the build version id to a list of asset paths
 *
 * Handy for pages building their own $moreJS / $moreCSS arrays before calling
 * llxHeader() directly, typically the PWA views. External URLs are left untouched
 *
 * @param  string[] $relativePaths Relative paths of the assets
 * @return string[]                Same paths, each suffixed with its version id
 */
function saturne_asset_urls(array $relativePaths): array
{
    return array_map('saturne_asset_url', $relativePaths);
}

/**
 * Append the build version id to the assets declared by modules through module_parts
 *
 * Those paths live in the MAIN_MODULE_*_CSS / _JS constants, frozen in database at
 * module activation, so they cannot carry a build hash on their own. Rewriting them
 * in $conf before any llxHeader() call is the only way to version them without a
 * migration. The emitting code in main.inc.php is their sole consumer
 *
 * @return void
 */
function saturne_version_modules_parts_assets(): void
{
    global $conf;

    static $done = false;

    if ($done || empty($conf->modules_parts)) {
        return;
    }
    $done = true;

    foreach (['css', 'js'] as $part) {
        if (empty($conf->modules_parts[$part]) || !is_array($conf->modules_parts[$part])) {
            continue;
        }
        foreach ($conf->modules_parts[$part] as $module => $files) {
            $conf->modules_parts[$part][$module] = is_array($files) ? array_map('saturne_htmlhead_asset_url', $files) : saturne_htmlhead_asset_url((string) $files);
        }
    }
}

/**
 * Append the build version id to the assets handed over to llxHeader()
 *
 * Meant for the llxHeader hook : it runs before top_htmlhead() prints the <link> and
 * <script> tags, so every page gets versioned assets - Dolibarr native pages and pages
 * calling llxHeader() with hardcoded paths included - and a module update never
 * requires a Ctrl+F5 from the users
 *
 * @param  array $parameters Parameters of the llxHeader hook, holding references to the llxHeader() locals
 * @return void
 */
function saturne_version_llxheader_assets(array $parameters): void
{
    saturne_version_modules_parts_assets();

    foreach (['arrayofjs', 'arrayofcss'] as $assetList) {
        if (!empty($parameters[$assetList]) && is_array($parameters[$assetList])) {
            // The entry is a reference to the llxHeader() local, so the assignment reaches top_htmlhead()
            $parameters[$assetList] = array_map('saturne_htmlhead_asset_url', $parameters[$assetList]);
        }
    }
}

/**
 * Append the build version id to a single asset printed by top_htmlhead()
 *
 * Dynamic PHP endpoints are left untouched : main.inc.php appends its own theme
 * query string without checking for an existing one, so a '?v=' would turn the
 * separator into a literal '?' and swallow the 'lang' parameter the endpoint reads.
 * Generated stylesheets have no build hash to carry anyway.
 * Assets already carrying a query string are left untouched too : their caller
 * versioned them already, or chose not to
 *
 * @param  string $relativePath Relative path declared in the module descriptor or handed over to llxHeader()
 * @return string               Same path, versioned unless it is a PHP endpoint or already has a query string
 */
function saturne_htmlhead_asset_url(string $relativePath): string
{
    if (preg_match('/\.php([?#]|$)/i', $relativePath) || strpos($relativePath, '?') !== false) {
        return $relativePath;
    }

    return saturne_asset_url($relativePath);
}
