<?php

/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/saturneredirection.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneRedirection (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/saturneobject.class.php';

class SaturneRedirection extends SaturneObject
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Module name
     */
    public $module = 'saturne';

    /**
     * @var string Element type of object
     */
    public $element = 'saturne_redirection';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'saturne_object_redirection';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for saturne_redirection. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'saturne_redirection@saturne' if picto is file 'img/object_saturne_redirection.png'
     */
    public string $picto = 'fontawesome_fa-forward_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;

    /**
     * @var int Length of generated short codes : 31^6, about 887 million combinations, too many to be enumerated
     */
    public const CODE_LENGTH = 6;

    /**
     * @var string Characters of generated short codes : no i, l, o, 0 or 1, that are mistaken for each other when typed again
     */
    public const CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 0],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 0],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 90, 'notnull' => 1, 'visible' => 0, 'default' => 1, 'index' => 1, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'Validated']],
        'from_url'      => ['type' => 'text',         'label' => 'FromURL',          'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 0],
        'to_url'        => ['type' => 'text',         'label' => 'ToURL',            'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 0],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 70, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string From URL
     */
    public string $from_url;

    /**
     * @var string To URL
     */
    public string $to_url;

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * Constructor
     *
     * @param DoliDB $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_redirection')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }

    /**
     * Check that a short code is well formed
     *
     * @param  string $code Short code
     * @return bool         True if the code can be used in a short URL : letters, digits, "-" and "_", 4 to 64 characters
     */
    public static function isValidCode(string $code): bool
    {
        return preg_match('/^[a-z0-9_-]{4,64}$/i', $code) === 1;
    }

    /**
     * Check that a target URL can be followed by the public entry point
     *
     * @param  string $url Target URL
     * @return bool        True for an http(s) URL or a path relative to the host, never javascript: or data:
     */
    public static function isValidTargetUrl(string $url): bool
    {
        if (preg_match('#^https?://[^\s]+$#i', $url) === 1) {
            return true;
        }

        return strpos($url, '/') === 0 && strpos($url, '//') !== 0;
    }

    /**
     * Get the default root of short URLs : the public entry point s/index.php, served without login
     *
     * @return string Root URL, ending with "/"
     */
    public static function getDefaultShortUrlRoot(): string
    {
        return dol_buildpath('/saturne/s/', 3);
    }

    /**
     * Get the root of short URLs : SATURNE_SHORT_URL_ROOT when the server provides a shorter one, else the default one
     *
     * @return string Root URL
     */
    public static function getShortUrlRoot(): string
    {
        $root = getDolGlobalString('SATURNE_SHORT_URL_ROOT');

        return self::isValidShortUrlRoot($root) ? $root : self::getDefaultShortUrlRoot();
    }

    /**
     * Check that a short URL root can be used : an http(s) URL without query string, the code is appended after "?"
     *
     * @param  string $root Root URL
     * @return bool         True if valid
     */
    public static function isValidShortUrlRoot(string $root): bool
    {
        return preg_match('#^https?://[^\s?\#]+$#i', $root) === 1;
    }

    /**
     * Build the public short URL of a code
     *
     * @param  string $code Short code
     * @return string       Short URL
     */
    public static function getShortUrlFromCode(string $code): string
    {
        return self::getShortUrlRoot() . '?' . $code;
    }

    /**
     * Get the code of a short URL built by this instance, under the current root or the default one
     *
     * A link created before SATURNE_SHORT_URL_ROOT was set keeps working, and stays recognized
     *
     * @param  string $shortUrl Short URL
     * @return string           Short code, empty string if the URL is not a short URL of this instance
     */
    public static function getCodeFromShortUrl(string $shortUrl): string
    {
        foreach (array_unique([self::getShortUrlRoot(), self::getDefaultShortUrlRoot()]) as $root) {
            $prefix = $root . '?';
            if (strpos($shortUrl, $prefix) === 0) {
                $code = substr($shortUrl, strlen($prefix));

                return self::isValidCode($code) ? $code : '';
            }
        }

        return '';
    }

    /**
     * Get the short URL redirecting to a target URL, creating its code on first call
     *
     * @param  string $toUrl Target URL
     * @param  User   $user  User creating the redirection
     * @return string        Short URL, empty string on error
     * @throws Exception
     */
    public function getShortUrl(string $toUrl, User $user): string
    {
        $result = $this->fetch(0, '', " AND t.to_url = '" . $this->db->escape($toUrl) . "' AND t.status = " . self::STATUS_VALIDATED);
        if ($result > 0 && self::isValidCode($this->from_url)) {
            return self::getShortUrlFromCode($this->from_url);
        }

        return $this->createShortUrl($toUrl, $user);
    }

    /**
     * Create a new short URL, even if another one already redirects to the same target
     *
     * @param  string $toUrl Target URL
     * @param  User   $user  User creating the redirection
     * @param  string $code  Wanted code, a random one is generated when empty
     * @return string        Short URL, empty string on error (bad target, bad or already used code)
     * @throws Exception
     */
    public function createShortUrl(string $toUrl, User $user, string $code = ''): string
    {
        if (!self::isValidTargetUrl($toUrl)) {
            $this->error = 'ErrorBadUrl';
            return '';
        }

        if ($code === '') {
            $code = $this->generateCode();
            if ($code === '') {
                return '';
            }
        } elseif (!self::isValidCode($code) || $this->isCodeUsed($code)) {
            $this->error = 'ErrorShortCodeNotAvailable';
            return '';
        }

        $this->from_url = $code;
        $this->to_url   = $toUrl;
        $this->status   = self::STATUS_VALIDATED;

        if ($this->create($user, 1) <= 0) {
            return '';
        }

        return self::getShortUrlFromCode($code);
    }

    /**
     * Change the target of a short code, a printed QR code then leads to the new target
     *
     * @param  string $code  Short code
     * @param  string $toUrl New target URL
     * @param  User   $user  User updating the redirection
     * @return int           1 on success, -1 on error
     * @throws Exception
     */
    public function updateTargetUrl(string $code, string $toUrl, User $user): int
    {
        if (!self::isValidCode($code) || !self::isValidTargetUrl($toUrl)) {
            $this->error = 'ErrorBadUrl';
            return -1;
        }

        $result = $this->fetch(0, '', " AND t.from_url = '" . $this->db->escape($code) . "'");
        if ($result <= 0) {
            $this->error = 'ErrorRecordNotFound';
            return -1;
        }

        $this->to_url = $toUrl;

        return $this->update($user, 1) > 0 ? 1 : -1;
    }

    /**
     * Get the target URL of a short code, whatever its entity, since the visitor of a public page has none
     *
     * @param  string $code Short code
     * @return string       Target URL, empty string if the code is unknown or its target is not safe
     */
    public function fetchTargetUrl(string $code): string
    {
        if (!self::isValidCode($code)) {
            return '';
        }

        $sql  = 'SELECT t.to_url FROM ' . $this->db->prefix() . $this->table_element . ' as t';
        $sql .= " WHERE t.from_url = '" . $this->db->escape($code) . "'";
        $sql .= ' AND t.status = ' . self::STATUS_VALIDATED;
        $sql .= ' ORDER BY t.rowid ASC';
        $sql .= $this->db->plimit(1);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return '';
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);
        if (!is_object($obj) || !self::isValidTargetUrl((string) $obj->to_url)) {
            return '';
        }

        return (string) $obj->to_url;
    }

    /**
     * Check whether a short code is already used, in any entity since codes are resolved without one
     *
     * @param  string $code Short code
     * @return bool         True if used, or if the check failed
     */
    private function isCodeUsed(string $code): bool
    {
        $sql   = 'SELECT COUNT(t.rowid) as nb FROM ' . $this->db->prefix() . $this->table_element . ' as t';
        $sql  .= " WHERE t.from_url = '" . $this->db->escape($code) . "'";
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return true;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        return !is_object($obj) || (int) $obj->nb > 0;
    }

    /**
     * Generate a short code not used yet
     *
     * @return string Short code, empty string on error
     * @throws Exception
     */
    private function generateCode(): string
    {
        $maxIndex = strlen(self::CODE_ALPHABET) - 1;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, $maxIndex)];
            }
            if (!$this->isCodeUsed($code)) {
                return $code;
            }
        }

        $this->error = 'ErrorFailedToGenerateShortCode';
        return '';
    }
}
