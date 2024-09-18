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
     * @param DoliDb $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_redirection')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }

    /**
     * Adapt htaccess in order to redirect 404 errors to dolibarr main index
     */
    public function adaptHtAccess()
    {
        $toUrl = DOL_MAIN_URL_ROOT . '/index.php?original_url=$1';

        $redirectionLines  = "RewriteCond %{REQUEST_FILENAME} !-f" . PHP_EOL;
        $redirectionLines .= "RewriteCond %{REQUEST_FILENAME} !-d" . PHP_EOL;
        $redirectionLines .= "RewriteRule ^(.*)$ $toUrl" . PHP_EOL;

        $htaccessContent = file_get_contents(DOL_DOCUMENT_ROOT . '/../.htaccess');

        if (!strpos($htaccessContent, $redirectionLines)) {

            $rewriteEnginePos = strpos($htaccessContent, 'RewriteEngine on');
            if ($rewriteEnginePos === false) {
                $rewriteEngineLine   = 'RewriteEngine on' . PHP_EOL;
                $allRedirectionLines = $rewriteEngineLine . "\n" . $redirectionLines;
            }

            $newHtaccessContent = $htaccessContent . "\n" . ($allRedirectionLines ?? $redirectionLines);
            file_put_contents(DOL_DOCUMENT_ROOT . '/../.htaccess', $newHtaccessContent);
        }
    }
}
