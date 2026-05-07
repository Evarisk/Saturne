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
 * Phan-only stubs for classes/functions not covered by the sparse checkout.
 *
 * Classes whose real files are now in directory_list (via quality.yml sparse
 * checkout) are intentionally absent — Phan reads them from htdocs/ directly:
 *   Societe, Contact, Project, ActionComm, Expedition, Reception, Task,
 *   Categorie, EcmFiles, BOM, Commande, CommandeFournisseur, Contrat, Entrepot,
 *   Facture, Fichinter, Inventory, Mo, MouvementStock, Product, ProductLot,
 *   Propal, Ticket, User.
 *
 * Remaining stubs cover:
 *   - DoliDB        → htdocs/core/db/ (not in sparse checkout)
 *   - CommonObject  → redeclared to add $photo / $picto absent from real class
 *   - DolibarrModules / DolibarrTriggers → not in core/class/
 *   - Third-party libs: Odf, OdfException, Segment, Parsedown, TCPDF2DBarcode
 *   - Dolibarr helpers not resolvable from class dirs: Activity, EcmDirectory
 *   - Global functions: llxHeader, llxFooter, top_httphead
 *   - Constants:  MAIN_DB_PREFIX, DOL_MAIN_URL_ROOT, ODTPHP_PATH, TCPDF_PATH
 *
 * No class_exists() guards: Phan parses AST and does not execute PHP.
 * PhanRedefinedClassReference is suppressed in .phan/config.php for the
 * CommonObject redeclaration.
 */

// ─── Constants ────────────────────────────────────────────────────────────────

define('MAIN_DB_PREFIX', 'llx_');
define('DOL_MAIN_URL_ROOT', '');
define('ODTPHP_PATH', '');
define('TCPDF_PATH', '');

// ─── DoliDB ───────────────────────────────────────────────────────────────────
// In htdocs/core/db/ — outside the sparse-checkout directories.

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

// ─── DolibarrModules ──────────────────────────────────────────────────────────
// Abstract base for Dolibarr module descriptors. Defined in core/modules/ which
// may not be fully parsed due to the volume of files in that directory.

abstract class DolibarrModules
{
    /** @var int */
    public $numero = 0;

    /** @var string */
    public $version = '';

    /** @var string */
    public $editor_name = '';

    public function __construct(DoliDB $db)
    {
    }

    /** @return int */
    public function init(string $options = ''): int
    {
        return 1;
    }

    /** @return int */
    public function remove(string $options = ''): int
    {
        return 1;
    }

    /** @return string */
    public function getDescLong(): string
    {
        return '';
    }
}

// ─── DolibarrTriggers ─────────────────────────────────────────────────────────
// Abstract base for Dolibarr event triggers. Not in htdocs/core/class/.

abstract class DolibarrTriggers
{
    /** @var string */
    public $version = '';

    /** @var string */
    public $picto = '';

    public function __construct(DoliDB $db)
    {
    }

    /** @return string */
    abstract public function getName(): string;

    /** @return string */
    abstract public function getDesc(): string;

    /**
     * @param string       $action
     * @param CommonObject $object
     * @param User         $user
     * @param Translate    $langs
     * @param Conf         $conf
     * @return int
     */
    abstract public function runTrigger(string $action, $object, User $user, Translate $langs, Conf $conf): int;
}

// ─── Activity ─────────────────────────────────────────────────────────────────
// Dolibarr activity/log helper not resolvable from the scanned class directories.

class Activity extends CommonObject
{
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
    }
}

// ─── EcmDirectory ─────────────────────────────────────────────────────────────
// ECM directory helper. Kept as stub to avoid import-chain issues from ecm/class/.

class EcmDirectory extends CommonObject
{
    /** @var string */
    public $label = '';

    /** @var string */
    public $relativepath = '';

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
}

// ─── Third-party: ODT (odtphp) ────────────────────────────────────────────────

class OdfException extends Exception
{
}

class OdfExceptionSegmentNotFound extends OdfException
{
}

class SegmentException extends Exception
{
}

class Segment
{
    /** @return void */
    public function setVars(string $key, string $value, bool $encode = true, string $charset = 'UTF-8'): void
    {
    }
}

class Odf
{
    /**
     * @param string  $filename
     * @param mixed[] $options
     */
    public function __construct(string $filename, array $options = [])
    {
    }

    /** @return Segment */
    public function setSegment(string $segment): Segment
    {
        return new Segment();
    }

    /** @return void */
    public function mergeSegment(Segment $segment): void
    {
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function setVars(string $key, $value, bool $encode = true, string $charset = 'UTF-8'): void
    {
    }

    /** @return void */
    public function exportAsAttachedFile(string $name = ''): void
    {
    }

    /** @return string */
    public function exportAsString(): string
    {
        return '';
    }
}

// ─── Third-party: Parsedown ───────────────────────────────────────────────────

class Parsedown
{
    /** @return string */
    public function text(string $text): string
    {
        return $text;
    }

    /** @return string */
    public function line(string $text): string
    {
        return $text;
    }
}

// ─── Third-party: TCPDF2DBarcode ──────────────────────────────────────────────

class TCPDF2DBarcode
{
    /**
     * @param string $code
     * @param string $type
     */
    public function __construct(string $code, string $type)
    {
    }

    /**
     * @param int    $w
     * @param int    $h
     * @param string $color
     * @return string
     */
    public function getBarcodeSVG(int $w = 3, int $h = 3, string $color = 'black'): string
    {
        return '';
    }

    /**
     * @param int    $w
     * @param int    $h
     * @param string $type
     * @param string $color
     * @return string
     */
    public function getBarcodeHTML(int $w = 10, int $h = 10, string $type = 'SVG', string $color = 'black'): string
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
