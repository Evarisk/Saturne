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
 * \file    class/saturnedocuments.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneDocuments (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/saturneobject.class.php';

/**
 * Class for SaturneDocuments.
 */
class SaturneDocuments extends SaturneObject
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Module name.
     */
    public $module = 'saturne';

    /**
     * @var string Element type of object.
     */
    public $element = 'saturne_documents';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'saturne_object_documents';

    /**
     * @var int Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 0, 'default' => 1, 'index' => 1, 'validate' => 1],
        'type'          => ['type' => 'varchar(128)', 'label' => 'Type',             'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0],
        'module_name'   => ['type' => 'varchar(128)', 'label' => 'ModuleName',       'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'json'          => ['type' => 'text',         'label' => 'JSON',             'enabled' => 1, 'position' => 100,  'notnull' => 0, 'visible' => 0],
        'model_pdf'     => ['type' => 'varchar(255)', 'label' => 'Model pdf',        'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0],
        'model_odt'     => ['type' => 'varchar(255)', 'label' => 'Model ODT',        'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0],
        'last_main_doc' => ['type' => 'varchar(128)', 'label' => 'LastMainDoc',      'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 0],
        'parent_type'   => ['type' => 'varchar(255)', 'label' => 'Parent_type',      'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => 0, 'default' => 1],
        'parent_id'     => ['type' => 'integer',      'label' => 'Parent_id',        'enabled' => 1, 'position' => 150, 'notnull' => 1, 'visible' => 0, 'default' => 1],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 160, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int ID.
     */
    public int $rowid;

    /**
     * @var string Ref.
     */
    public $ref;

    /**
     * @var string Ref ext.
     */
    public $ref_ext;

    /**
     * @var int Entity.
     */
    public $entity;

    /**
     * @var int|string Creation date.
     */
    public $date_creation;

    /**
     * @var int|string Timestamp.
     */
    public $tms;

    /**
     * @var string Import key.
     */
    public $import_key = 0;

    /**
     * @var int Status.
     */
    public $status;

    /**
     * @var string Type.
     */
    public string $type = '';

    /**
     * @var string Module name.
     */
    public string $module_name = '';

    /**
     * @var string|null Json.
     */
    public ?string $json = null;

    /**
     * @var string Pdf model name.
     */
    public $model_pdf;

    /**
     * @var string|null ODT model name.
     */
    public ?string $model_odt = null;

    /**
     * @var string Last document name.
     */
    public $last_main_doc;

    /**
     * @var string Object parent type.
     */
    public string $parent_type = '';

    /**
     * @var int Object parent ID.
     */
    public int $parent_id;

    /**
     * @var int User ID.
     */
    public $fk_user_creat;

    /**
     * Constructor.
     *
     * @param DoliDb $db                  Database handler.
     * @param string $moduleNameLowerCase Module name.
     * @param string $objectType          Object element type.
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_documents')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }

    /**
     * Create object into database
     *
     * @param  User        $user         User that creates
     * @param  bool        $notrigger    false=launch triggers after, true=disable triggers
     * @param  object|null $parentObject Current object
     * @return int                       0 < if KO, ID of created object if OK
     */
    public function create(User $user, bool $notrigger = false, object $parentObject = null): int
    {
        $now = dol_now();

        $this->ref_ext       = $this->module . '_' . $this->ref;
        $this->date_creation = $this->db->idate($now);
        $this->tms           = $now;
        $this->status        = 1;
        if (empty($this->type)) {
            $this->type = $this->element;
        }
        $this->module_name   = $this->module;
        $this->parent_id     = $parentObject->id ?: 0;
        $this->parent_type   = $parentObject->element_type ?: $parentObject->element ?: '';
        $this->fk_user_creat = $user->id ?: 1;

        //$this->DocumentFillJSON($this);
        return $this->createCommon($user, $notrigger);
    }

//  /**
//   * Function for JSON filling before saving in database
//   *
//   * @param $object
//   */
//  public function DocumentFillJSON($object) {
//      switch ($object->element) {
//          case "timesheetdocument":
//              $this->json = $this->TimeSheetDocumentFillJSON($object);
//              break;
//      }
//  }

    /**
     * Initialise object with example values.
     * ID must be 0 if object instance is a specimen.
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        $this->initAsSpecimenCommon();
    }

    /**
     * Create a document onto disk according to template module.
     *
     * @param  string     $modele      Force template to use ('' to not force).
     * @param  Translate  $outputlangs Object langs.
     * @param  int        $hidedetails Hide details of lines.
     * @param  int        $hidedesc    Hide description.
     * @param  int        $hideref     Hide ref.
     * @param array|null $moreparams  Array to provide more information.
     * @return int                     0 if KO, 1 if OK.
     */
    public function generateDocument(string $modele, Translate $outputlangs, int $hidedetails = 0, int $hidedesc = 0, int $hideref = 0, array $moreparams = null): int
    {
        if (is_dir(__DIR__ . '/../../' . $this->module . '/core/modules/' . $this->module . '/' . $this->module . 'documents/' . $this->element . '/') && $moreparams['zone'] == 'private') {
            $modelpath = 'custom/' . $this->module . '/core/modules/' . $this->module . '/' . $this->module . 'documents/' . $this->element . '/';
        } else {
            $modelpath = 'custom/' . $this->module . '/core/modules/' . $this->module . '/' . $this->module . 'documents/' . $moreparams['objectType'] . 'document/';
        }

        $result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        if ($result > 0) {
            $this->call_trigger(strtoupper($this->type) . '_GENERATE', $moreparams['user']);
        }

        return $result;
    }

    /**
     * Get last document of a type in a dir
     *
     * @param  string    $moduleNameLowerCase Module name in lowercase
     * @param  string    $fileDir             File directory
     * @param  string    $fileType            Type of file
     * @param  int       $entity              Entity
     * @return array|int $result              Array of document or -1 if not found
     */
    public function getLastDocument(string $moduleNameLowerCase = '', string $fileDir = '', string $fileType = '', int $entity = 1)
    {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $fileDir   = DOL_DATA_ROOT . '/' . ($entity > 1 ? $entity . '/' : '') . $moduleNameLowerCase . '/' . $fileDir;
        $fileList  = dol_dir_list($fileDir, 'files', 0, '(\.' . $fileType .  ')', '', 'date', 'SORT_DESC', 1);
        if (count($fileList)) {
            $result = $fileList[0];
        } else {
            $result = -1;
        }

        return $result;
    }

    /**
     * Get URL of last generated document as a html link
     *
     * @param  string $moduleNameLowerCase Module name in lowercase
     * @param  string $fileDir             File directory
     * @param  string $fileType            Type of file
     * @param  string $icon                Icon for download button
     * @param  int    $entity              Entity
     * @return string                      String of html button
     */
    public function showUrlOfLastGeneratedDocument(string $moduleNameLowerCase = '', string $fileDir = '', string $fileType = '', string $icon = 'fa-file-word', int $entity = 1): string
    {
        global $langs;

        $out      = '';
        $document = $this->getLastDocument($moduleNameLowerCase, $fileDir, $fileType, $entity);
        if (is_array($document)) {
            $documentUrl = DOL_URL_ROOT . '/document.php';
            $fileUrl     = $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode($fileDir . '/' . $document['name']);
            $icon        = $fileType == 'pdf' ? 'fa-file-pdf' : $icon;
            $out         = '<a class="marginleftonly" href="' . $fileUrl . '" download>' . img_picto($langs->trans('File') . ' : ' . $document['name'], $icon) . '</a>';
        }

        return $out;
    }
}
