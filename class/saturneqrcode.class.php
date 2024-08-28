<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/saturneqrcode.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneQRCode (Create/Read/Update/Delete).
 */

// Load Saturne libraries
require_once __DIR__ . '/saturneobject.class.php';

// Load QRCode library
require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

class SaturneQRCode extends SaturneObject
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
    public $element = 'saturne_qrcode';

    /**
     * @var string Name of table without prefix where object is stored This is also the key used for extrafields management
     */
    public $table_element = 'saturne_qrcode';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string Last output from end job execution
     */
    public $output = '';

    /**
     * @var string Name of icon for certificate Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'certificate@saturne' if picto is file 'img/object_certificatepng'
     */
    public string $picto = 'fontawesome_fa-forward_fas_#d35968';

    /**
     * @var array  Array with all fields and their property Do not use it as a static var It may be modified by constructor
     */
    public $fields = [
        'rowid'             => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'entity'            => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'     => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
        'tms'               => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0],
        'import_key'        => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'            => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 2, 'default' => 0, 'index' => 1, 'validate' => 1, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'ValidatePendingSignature', 2 => 'Expired', 3 => 'Archived']],
        'module_name'       => ['type' => 'varchar(128)', 'label' => 'ModuleName',       'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'url'               => ['type' => 'text',         'label' => 'Url',              'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'encoded_qr_code'   => ['type' => 'text',         'label' => 'EncodedData',      'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'fk_user_creat'     => ['type' => 'integer:User:user/class/userclassphp',      'label'   => 'UserAuthor',         'picto' => 'user',              'enabled' => 1,                         'position' => 220, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'userrowid'],
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
     * @var string Module name
     */
    public $module_name;

    /**
     * @var string URL
     */
    public $url;

    /**
     * @var string QR Code encoded
     */
    public $encoded_qr_code;

    /**
     * @var int User creator
     */
    public $fk_user_creat;

    /**
     * Constructor
     *
     * @param DoliDb $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_qrcode')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }

    /**
     * Get QR Code base64
     *
     * @param string $url URL to encode
     *
     * @return string Encoded QR Code
     */
    public function getQRCodeBase64(string $url): string
{
        // Create QR Code
        $barcodeObject = new TCPDF2DBarcode($url, 'QRCODE,H');
        $qrCodePng     = $barcodeObject->getBarcodePngData(6, 6);
        $qrCodeBase64  = 'data:image/png;base64,' . base64_encode($qrCodePng);

        return $qrCodeBase64;
    }
}

?>
