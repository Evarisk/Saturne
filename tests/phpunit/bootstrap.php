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
 * PHPUnit bootstrap for Saturne unit tests.
 *
 * Stub-only mode: no Dolibarr installation required, no database connection.
 * Defines constants, stub classes and functions that Dolibarr code expects
 * to find at runtime. Tests may load specific saturne lib files on top of this.
 *
 * Migration path: once CI runs with a full Dolibarr checkout, replace this
 * bootstrap with one that calls main.inc.php for integration tests.
 */

// ─── Constants ────────────────────────────────────────────────────────────────

define('DOL_DOCUMENT_ROOT', realpath(__DIR__ . '/../../../../'));
define('DOL_DATA_ROOT', dirname(DOL_DOCUMENT_ROOT) . '/documents');
define('DOL_URL_ROOT', '/');
define('DOL_VERSION', '0.0.0');
define('GETPOST_ALLOWHTML', 1);
define('NOLOGIN', '1');
define('NOSESSION', '1');

// ─── Dolibarr stub functions ───────────────────────────────────────────────────

if (!function_exists('dol_syslog')) {
    /**
     * @param string $msg
     * @param int    $level
     */
    function dol_syslog(string $msg, int $level = LOG_DEBUG): void
    {
        // No-op in tests.
    }
}

if (!function_exists('dol_escape_htmltag')) {
    /** @return string */
    function dol_escape_htmltag(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('dol_sanitize_filename')) {
    /** @return string */
    function dol_sanitize_filename(string $s): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-.]/', '', $s) ?? $s;
    }
}

if (!function_exists('img_picto')) {
    /**
     * @param string $titlealt
     * @param string $picto
     * @param mixed  ...$args
     * @return string
     */
    function img_picto(string $titlealt, string $picto, ...$args): string
    {
        return '<img alt="' . htmlspecialchars($titlealt) . '">';
    }
}

if (!function_exists('GETPOST')) {
    /**
     * @param string $paramname
     * @param string $check
     * @param mixed  ...$args
     * @return mixed
     */
    function GETPOST(string $paramname, string $check = 'none', ...$args)
    {
        return $_REQUEST[$paramname] ?? '';
    }
}

if (!function_exists('GETPOSTINT')) {
    /** @return int */
    function GETPOSTINT(string $paramname): int
    {
        return isset($_REQUEST[$paramname]) ? (int)$_REQUEST[$paramname] : 0;
    }
}

if (!function_exists('GETPOSTISSET')) {
    /** @return bool */
    function GETPOSTISSET(string $paramname): bool
    {
        return isset($_REQUEST[$paramname]);
    }
}

if (!function_exists('getDolGlobalInt')) {
    /** @return int */
    function getDolGlobalInt(string $key, int $default = 0): int
    {
        global $conf;
        return isset($conf->global->$key) ? (int) $conf->global->$key : $default;
    }
}

if (!function_exists('getDolGlobalString')) {
    /** @return string */
    function getDolGlobalString(string $key, string $default = ''): string
    {
        global $conf;
        return isset($conf->global->$key) ? (string) $conf->global->$key : $default;
    }
}

if (!function_exists('llxHeader')) {
    function llxHeader(): void
    {
    }
}

// ─── Stub classes ──────────────────────────────────────────────────────────────

if (!class_exists('DoliDB')) {
    class DoliDB
    {
        /** @var string */
        public $type = '';

        /**
         * @param mixed $value
         * @return string
         */
        public function escape($value): string
        {
            return (string) $value;
        }
    }
}

if (!class_exists('Conf')) {
    class Conf
    {
        /** @var stdClass */
        public $global;

        /** @var int */
        public $entity = 1;

        /** @var int */
        public $liste_limit = 25;

        public function __construct()
        {
            $this->global = new stdClass();
        }

        /**
         * @param string $name
         * @return mixed
         */
        public function __get(string $name)
        {
            return null;
        }
    }
}

if (!class_exists('Translate')) {
    class Translate
    {
        /** @var string */
        public $defaultlang = 'en_EN';

        /**
         * @param mixed ...$args
         * @return string
         */
        public function trans(string $key, ...$args): string
        {
            return $key;
        }

        /**
         * @param mixed ...$args
         * @return string
         */
        public function transnoentities(string $key, ...$args): string
        {
            return $key;
        }

        /** @return string */
        public function transnoentitiesnoconv(string $key): string
        {
            return $key;
        }

        /** @return void */
        public function setDefaultLang(string $lang): void
        {
        }
    }
}

if (!class_exists('User')) {
    class User
    {
        /** @var int */
        public $id = 0;

        /** @var stdClass */
        public $rights;

        /** @var mixed[] */
        public $users = [];

        /** @var string[] */
        public $errors = [];

        /** @var int */
        public $socid = 0;

        public function __construct()
        {
            $this->rights = new stdClass();
        }

        /** @return int */
        public function fetchAll(): int
        {
            return 1;
        }
    }
}

if (!class_exists('HookManager')) {
    class HookManager
    {
        /** @var string */
        public $resPrint = '';

        /** @var string */
        public $error = '';

        /** @var string[] */
        public $errors = [];

        /** @var mixed[] */
        public $resArray = [];

        /** @param string[] $hooks */
        public function initHooks(array $hooks): void
        {
        }

        /**
         * @param string  $hookname
         * @param mixed[] $parameters
         * @param mixed   $object
         * @param string  $action
         * @return int
         */
        public function executeHooks(string $hookname, array $parameters, &$object = null, &$action = ''): int
        {
            return 0;
        }
    }
}

// ─── Global variables ─────────────────────────────────────────────────────────

global $conf, $db, $hookmanager, $langs, $mysoc, $user;

$conf        = new Conf();
$db          = new DoliDB();
$langs       = new Translate();
$user        = new User();
$hookmanager = new HookManager();
