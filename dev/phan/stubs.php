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
 * Phan-only stubs for Dolibarr classes not present in htdocs/core/class/.
 *
 * Phan reads real classes from htdocs/core/class/ (Conf, Translate, HookManager,
 * Form, …) so those are intentionally absent here.
 *
 * CommonObject IS redefined below to add $photo and $picto which the real class
 * does not declare — PhanRedefinedClassReference is suppressed in .phan/config.php.
 *
 * No class_exists() guards: Phan parses AST and does not execute PHP.
 * dev/phpstan/stubs.php is excluded from Phan via exclude_file_list.
 */

// ─── Constants ────────────────────────────────────────────────────────────────

define('MAIN_DB_PREFIX', 'llx_');

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
     * @param string $sql
     * @param int    $usesavepoint
     * @param string $type
     * @param bool   $result_mode
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

// ─── CommonObject (extended stub) ─────────────────────────────────────────────
// Redeclared here only to add $photo and $picto which are absent from the real
// class. PhanRedefinedClassReference is suppressed in .phan/config.php.

class CommonObject
{
    /** @var int */
    public $id = 0;

    /** @var string */
    public $ref = '';

    /** @var string */
    public $element = '';

    /** @var string */
    public $table_element = '';

    /** @var string */
    public $module = '';

    /** @var int */
    public $statut = 0;

    /** @var string */
    public $status = '';

    /** @var DoliDB */
    protected $db;

    /** @var string */
    public $error = '';

    /** @var string[] */
    public $errors = [];

    /** @var mixed[] */
    public $lines = [];

    /** @var mixed[] */
    public $array_options = [];

    /** @var string */
    public $photo = '';

    /** @var string */
    public $picto = '';

    public function __construct(DoliDB $db)
    {
        $this->db = $db;
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

    /** @return int */
    public function create(User $user): int
    {
        return 1;
    }

    /** @return int */
    public function update(User $user): int
    {
        return 1;
    }

    /** @return int */
    public function delete(User $user): int
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

    /** @return string */
    public function getLibStatut(int $mode = 0): string
    {
        return '';
    }

    /** @return string */
    public function LibStatut(int $status, int $mode = 0): string
    {
        return '';
    }
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

    /** @var string */
    public $job = '';

    /** @var string */
    public $phone = '';

    /** @var string */
    public $datelastlogin = '';

    /** @var string */
    public $datepreviouslogin = '';

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
     * @param string       $sortorder
     * @param string       $sortfield
     * @param int          $limit
     * @param int          $offset
     * @param string|array $filter
     * @param string       $filtermode
     * @param bool         $entityfilter
     * @return int
     */
    public function fetchAll(
        string $sortorder = '',
        string $sortfield = '',
        int $limit = 0,
        int $offset = 0,
        $filter = '',
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

// ─── Societe ──────────────────────────────────────────────────────────────────

class Societe extends CommonObject
{
    /** @var string */
    public $name = '';

    /** @var int */
    public $id = 0;

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

// ─── Categorie ────────────────────────────────────────────────────────────────

class Categorie extends CommonObject
{
    /** @var string */
    public $label = '';

    /** @var string */
    public $type = '';

    /** @var int */
    public $fk_parent = 0;

    /** @var string */
    public $color = '';

    /** @var string */
    public $description = '';

    /** @var int */
    public $visible = 0;

    /** @var int */
    public $imgWidth = 0;

    /** @var int */
    public $imgHeight = 0;

    /** @var int */
    public $entity = 1;

    /** @var int */
    public $id = 0;

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

    /**
     * @param string $path
     * @param int    $nbmax
     * @return mixed[]
     */
    public function liste_photos(string $path, int $nbmax = 0): array
    {
        return [];
    }

    /**
     * @param string $path
     * @param string $file
     * @return mixed[]
     */
    public function get_image_size(string $path, string $file = ''): array
    {
        return [];
    }

    /** @return string */
    public function getFilterJoinQuery(string $type, string $alias): string
    {
        return '';
    }

    /** @return int */
    public function create(User $user): int
    {
        return 1;
    }
}

// ─── BOM ──────────────────────────────────────────────────────────────────────

class BOM extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Commande ─────────────────────────────────────────────────────────────────

class Commande extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── CommandeFournisseur ──────────────────────────────────────────────────────

class CommandeFournisseur extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Contrat ──────────────────────────────────────────────────────────────────

class Contrat extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Entrepot ─────────────────────────────────────────────────────────────────

class Entrepot extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Facture ──────────────────────────────────────────────────────────────────

class Facture extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Fichinter ────────────────────────────────────────────────────────────────

class Fichinter extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Inventory ────────────────────────────────────────────────────────────────

class Inventory extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Mo ───────────────────────────────────────────────────────────────────────

class Mo extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── MouvementStock ───────────────────────────────────────────────────────────

class MouvementStock extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Product ──────────────────────────────────────────────────────────────────

class Product extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── ProductLot ───────────────────────────────────────────────────────────────

class ProductLot extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Propal ───────────────────────────────────────────────────────────────────

class Propal extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Ticket ───────────────────────────────────────────────────────────────────

class Ticket extends CommonObject
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
     * @return string
     */
    public function getNomUrl(int $withpicto = 0, string $option = ''): string
    {
        return '';
    }
}

// ─── Global functions ─────────────────────────────────────────────────────────

function llxHeader(): void
{
}

function llxFooter(): void
{
}

function top_httphead(): void
{
}
