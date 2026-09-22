<?php

/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \brief   This file is a CRUD class file for SaturneDocuments (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/saturneobject.class.php';

/**
 * Class for SaturneDocuments
 */
abstract class SaturneDocuments extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'saturne';

    /**
     * @var string Element type of object
     */
    public $element = 'saturne_documents';

    /**
     * @var string Name of table without prefix where object is stored
     *             This is also the key used for extrafields management
     */
    public $table_element = 'saturne_object_documents';

    /**
     * @var array Array with all fields and their property
     *            Do not use it as a static var. It may be modified by constructor
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
     * @var string|null ODT model name.
     */
    public ?string $model_odt = null;

    /**
     * @var string Object parent type.
     */
    public string $parent_type = '';

    /**
     * @var int Object parent ID.
     */
    public int $parent_id;

    /**
     * Constructor
     *
     * @param DoliDB $db                  Database handler
     * @param string $moduleNameLowerCase Module name
     * @param string $objectType          Object element type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_documents')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectType);
    }

    /**
     * Create object into database
     *
     * @param  User        $user         User that creates
     * @param  int<0,1>    $noTrigger    0 = launch triggers after, 1 = disable triggers
     * @param  object|null $parentObject Current object
     * @return int<-1,max>               Return integer 0 < if KO, ID of created object if OK
     */
    public function create(User $user, int $noTrigger = 0, ?object $parentObject = null): int
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
        return $this->createCommon($user, $noTrigger);
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
        $baseModulePath = $this->module . '/core/modules/' . $this->module . '/' . $this->module . 'documents/';

        $classFilePath = __DIR__ . '/../../' . $baseModulePath;
        $modelPath     = 'custom/' . $baseModulePath;

        $isPrivateZone = isset($moreparams['zone']) && $moreparams['zone'] === 'private';
        $elementPath   = $classFilePath . $this->element . '/';

        if ($isPrivateZone && is_dir($elementPath)) {
            $modelPath .= $this->element . '/';
        } else {
            $objectType = $moreparams['objectType'] ?? '';
            $modelPath .= $objectType . 'document/';
        }

        $modele = $this->checkDocumentTemplate($modele);
        if (!dol_strlen($modele)) {
            return -1;
        }

        $result = $this->commonGenerateDocument($modelPath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

        // Fallback for Saturne modules: if doc generator wasn't found in the first path, try the 'document' suffixed path
        if ($result <= 0 && (strpos($this->error, 'Failed to load doc generator') !== false || $this->error == 'ErrorFailedToLoadDocumentGenerator')) {
            $modelPathFallback = 'custom/' . $baseModulePath . $this->element . 'document/';
            $this->error = '';
            $this->errors = [];
            $result = $this->commonGenerateDocument($modelPathFallback, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        }

        // Need to reset $document->error because commonGenerateDocument call unwanted function dol_delete_preview
        if ($this->error == 'ErrorObjectNoSupportedByFunction') {
            $this->error = '';
        }

        if ($result > 0) {
            $this->call_trigger(strtoupper($this->type) . '_GENERATE', $moreparams['user']);

            if (empty($this->last_main_doc)) {
                if (!empty($this->result['fullpath'])) {
                    $this->last_main_doc = basename($this->result['fullpath']);
                } else {
                    $this->last_main_doc = !empty($moreparams['specimen']) ? 'SPECIMEN.pdf' : '';
                }
            }
        }

        return $result;
    }

    /**
     * Check the template carried by a model key and return a key usable by commonGenerateDocument()
     *
     * A model key is either 'modelname' for a native generator, or 'modelname:/full/path/of/template.odt' for an ODT one.
     * That path is sent by the request, so it is checked against the template directories the module declares.
     * Dolibarr 24 also refuses every template stored outside DOL_DATA_ROOT/ecm and DOL_DATA_ROOT/doctemplates -
     * commonGenerateDocument() then answers BadDirForTemplateFile - while Saturne modules ship theirs in their own
     * directory : the selected template is mirrored under DOL_DATA_ROOT/doctemplates to keep the generation working
     *
     * @param  string $modele Model key asked for the generation
     * @return string         Model key to pass to commonGenerateDocument(), empty if the template is not allowed
     */
    protected function checkDocumentTemplate(string $modele): string
    {
        global $langs;

        // Load Dolibarr libraries
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $modelData = explode(':', $modele, 2);
        if (!isset($modelData[1]) || !dol_strlen($modelData[1])) {
            return $modele;
        }

        $modelName    = $modelData[0];
        $documentType = preg_replace('/_(custom_)?odt$/', '', $modelName);

        $templatePath = realpath($modelData[1]);
        $templatePath = $templatePath !== false ? strtr($templatePath, DIRECTORY_SEPARATOR, '/') : '';

        $isAllowed = false;
        if (dol_strlen($templatePath) > 0) {
            foreach ($this->getDocumentTemplateDirs($documentType) as $templateDir) {
                if (strpos($templatePath, $templateDir) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
        }
        if (!$isAllowed) {
            $langs->load('saturne@saturne');
            $this->error    = $langs->trans('ErrorTemplateFileNotAllowed', $modelData[1]);
            $this->errors[] = $this->error;
            dol_syslog('SaturneDocuments::checkDocumentTemplate refused template file ' . $modelData[1], LOG_WARNING);
            return '';
        }

        // Dolibarr 24 only reads a template stored under DOL_DATA_ROOT/ecm or DOL_DATA_ROOT/doctemplates : mirror the
        // ones shipped inside the module there, and refresh the copy as soon as the file of the module changes
        $dataRoot = rtrim(strtr(DOL_DATA_ROOT, DIRECTORY_SEPARATOR, '/'), '/');
        if (version_compare(DOL_VERSION, '24.0.0', '>=') && strpos($templatePath, $dataRoot . '/ecm/') !== 0 && strpos($templatePath, $dataRoot . '/doctemplates/') !== 0) {
            $mirrorDir  = $dataRoot . '/doctemplates/' . $this->module . '/' . $documentType;
            $mirrorPath = $mirrorDir . '/' . basename($templatePath);
            if (!dol_is_file($mirrorPath) || filesize($mirrorPath) != filesize($templatePath) || filemtime($mirrorPath) < filemtime($templatePath)) {
                dol_mkdir($mirrorDir);
                if (dol_copy($templatePath, $mirrorPath, '0', 1) < 1) {
                    $this->error    = $langs->trans('ErrorFailToCopyFile', $templatePath, $mirrorPath);
                    $this->errors[] = $this->error;
                    return '';
                }
            }
            $templatePath = $mirrorPath;
        }

        return $modelName . ':' . $templatePath;
    }

    /**
     * Get the directories a document template of the module can be read from
     *
     * @param  string   $documentType Document type of the model asked for the generation
     * @return string[]               Existing absolute directories, slash ended
     */
    protected function getDocumentTemplateDirs(string $documentType): array
    {
        $dirs = [
            dol_buildpath('/' . $this->module . '/documents/doctemplates/'),
            DOL_DATA_ROOT . '/ecm/' . $this->module . '/',
            DOL_DATA_ROOT . '/doctemplates/' . $this->module . '/'
        ];

        // The module declares where its templates live, the custom ones included
        $constPrefix = dol_strtoupper($this->module . '_' . $documentType);
        foreach (['_ADDON_ODT_PATH', '_CUSTOM_ADDON_ODT_PATH', '_SPECIMEN_ADDON_ODT_PATH'] as $constSuffix) {
            $constValue = getDolGlobalString($constPrefix . $constSuffix);
            foreach (explode(',', preg_replace('/[\r\n]+/', ',', $constValue)) as $dir) {
                $dirs[] = str_replace(['DOL_DATA_ROOT', 'DOL_DOCUMENT_ROOT'], [DOL_DATA_ROOT, DOL_DOCUMENT_ROOT], trim($dir));
            }
        }

        $templateDirs = [];
        foreach ($dirs as $dir) {
            if (!dol_strlen($dir)) {
                continue;
            }
            $realDir = realpath($dir);
            if ($realDir === false) {
                continue;
            }
            $templateDirs[] = rtrim(strtr($realDir, DIRECTORY_SEPARATOR, '/'), '/') . '/';
        }

        return $templateDirs;
    }

    /**
     * Get last document of a type in a dir
     *
     * @param  string    $moduleNameLowerCase Module name in lowercase
     * @param  string    $fileDir             File directory
     * @param  string    $fileType            Type of file
     * @param  int       $entity              Entity (0 = current entity)
     * @return array|int $result              Array of document or -1 if not found
     */
    public function getLastDocument(string $moduleNameLowerCase = '', string $fileDir = '', string $fileType = '', int $entity = 0)
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $entity = $entity > 0 ? $entity : $conf->entity;

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
     * @param  int    $entity              Entity (0 = current entity)
     * @return string                      String of html button
     */
    public function showUrlOfLastGeneratedDocument(string $moduleNameLowerCase = '', string $fileDir = '', string $fileType = '', string $icon = 'fa-file-word', int $entity = 0): string
    {
        global $conf, $langs;

        $entity = $entity > 0 ? $entity : $conf->entity;

        $out      = '';
        $document = $this->getLastDocument($moduleNameLowerCase, $fileDir, $fileType, $entity);
        if (is_array($document)) {
            $documentUrl = DOL_URL_ROOT . '/document.php';
            $fileUrl     = $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode($fileDir . '/' . $document['name']) . '&entity=' . $entity;
            $icon        = $fileType == 'pdf' ? 'fa-file-pdf' : $icon;
            $out         = '<a class="marginleftonly" href="' . $fileUrl . '" download>' . img_picto($langs->trans('File') . ' : ' . $document['name'], $icon) . '</a>';
        }

        return $out;
    }
}
