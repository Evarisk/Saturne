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
 * Phan-only stubs for classes NOT present in htdocs/core/class/ (sparse checkout).
 *
 * Phan reads real Dolibarr classes from htdocs/core/class/ (Conf, Translate,
 * HookManager, Form, CommonObject, …) so those are intentionally absent here.
 *
 * Classes covered:
 *   - DoliDB   → htdocs/core/db/ (different directory, not in directory_list)
 *   - User     → htdocs/user/class/ (not in directory_list)
 *   - Contact  → htdocs/contact/class/ (not in sparse checkout)
 *   - Project  → htdocs/projet/class/ (not in sparse checkout)
 *
 * No class_exists() guards: Phan parses AST and does not execute PHP.
 * dev/phpstan/stubs.php is excluded from Phan via exclude_file_list.
 */

// ─── DoliDB ───────────────────────────────────────────────────────────────────

abstract class DoliDB
{
    /** @var string */
    public $type = '';

    /**
     * @param mixed $value
     * @return string
     */
    abstract public function escape($value);

    /**
     * @param string  $sql
     * @param int     $usesavepoint
     * @param string  $type
     * @param bool    $result_mode
     * @return resource|bool|null
     */
    abstract public function query($sql, $usesavepoint = 0, $type = 'auto', $result_mode = false);

    /**
     * @param resource|bool|null $resultset
     * @return object|false
     */
    abstract public function fetch_object($resultset);

    /**
     * @param resource|bool|null $resultset
     * @return int
     */
    abstract public function num_rows($resultset);

    /** @return string */
    abstract public function lasterror();

    /** @return string */
    abstract public function lasterrno();

    /** @return bool */
    abstract public function close();
}

// ─── User ─────────────────────────────────────────────────────────────────────

class User extends CommonObject
{
    /** @var int */
    public $id = 0;

    /** @var string */
    public $login = '';

    /** @var string */
    public $email = '';

    /** @var int */
    public $socid = 0;

    /** @var mixed[] */
    public $users = [];

    /** @var string[] */
    public $errors = [];

    /** @var stdClass */
    public $rights;

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->rights = new stdClass();
    }

    /** @return bool */
    public function hasRight(string $module, string $permlevel, string $permright = ''): bool
    {
        return false;
    }

    /**
     * @param string $sortorder
     * @param string $sortfield
     * @param int    $limit
     * @param int    $offset
     * @param string $filter
     * @param string $filtermode
     * @param bool   $entityfilter
     * @return int
     */
    public function fetchAll(
        string $sortorder = '',
        string $sortfield = '',
        int $limit = 0,
        int $offset = 0,
        string $filter = '',
        string $filtermode = 'AND',
        bool $entityfilter = false
    ): int {
        return 1;
    }
}

// ─── Contact ──────────────────────────────────────────────────────────────────

class Contact extends CommonObject
{
    /** @var string */
    public $lastname = '';

    /** @var string */
    public $firstname = '';

    /** @var string */
    public $email = '';

    /** @var int */
    public $fk_soc = 0;

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
    }

    /**
     * @param int    $id
     * @param string $ref
     * @return int
     */
    public function fetch($id, string $ref = ''): int
    {
        return 1;
    }

    /** @return string */
    public function getFullName(Translate $langs): string
    {
        return '';
    }

    /** @return string */
    public function getCivilityLabel(): string
    {
        return '';
    }

    /**
     * @param mixed[] $filters
     * @return mixed[]|int
     */
    public function liste_contact(array $filters = [])
    {
        return [];
    }
}

// ─── Project ──────────────────────────────────────────────────────────────────

class Project extends CommonObject
{
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
    }

    /**
     * @param int    $id
     * @param string $ref
     * @return int
     */
    public function fetch($id, string $ref = ''): int
    {
        return 1;
    }

    /**
     * @param int    $withpicto
     * @param string $option
     * @param int    $notooltip
     * @param string $morecss
     * @param int    $save_lastsearch_value
     * @return string
     */
    public function getNomUrl(
        int $withpicto = 0,
        string $option = '',
        int $notooltip = 0,
        string $morecss = '',
        int $save_lastsearch_value = -1
    ): string {
        return '';
    }
}

// ─── Global functions ─────────────────────────────────────────────────────────

function llxFooter(): void
{
}
