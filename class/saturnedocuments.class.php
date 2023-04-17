<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 * \file        class/dolimeetdocuments.class.php
 * \ingroup     dolimeet
 * \brief       This file is a CRUD class file for DoliMeetDocuments (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for DoliMeetDocuments
 */
class DoliMeetDocuments extends CommonObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = [];

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var string Element type of object.
	 */
	public $element = 'dolimeetdocuments';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'dolimeet_dolimeetdocuments';

	/**
	 * @var int Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public int $ismultientitymanaged = 1;

	/**
	 * @var int Does object support extrafields ? 0=No, 1=Yes
	 */
	public int $isextrafieldmanaged = 1;

	/**
	 * @var string Name of icon for dolimeetdocuments. Must be the part after the 'object_' into object_dolimeetdocuments.png
	 */
	public string $picto = '';

	/**
	 * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public array $fields = [
		'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
		'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
		'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
		'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
		'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
		'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
		'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
		'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 0, 'default' => 0, 'index' => 1, 'validate' => 1],
		'type'          => ['type' => 'varchar(128)', 'label' => 'Type',             'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0],
		'json'          => ['type' => 'text',         'label' => 'JSON',             'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
		'model_pdf'     => ['type' => 'varchar(255)', 'label' => 'Model pdf',        'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 0],
		'model_odt'     => ['type' => 'varchar(255)', 'label' => 'Model ODT',        'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0],
		'last_main_doc' => ['type' => 'varchar(128)', 'label' => 'LastMainDoc',      'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0],
		'parent_type'   => ['type' => 'varchar(255)', 'label' => 'Parent_type',      'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => 0, 'default' => 1],
		'parent_id'     => ['type' => 'integer',      'label' => 'Parent_id',        'enabled' => 1, 'position' => 150, 'notnull' => 1, 'visible' => 0, 'default' => 1],
		'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 130, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

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
     * @var string Type
     */
	public string $type;

    /**
     * @var string Json
     */
	public string $json;

    /**
     * @var string Pdf model name
     */
    public $model_pdf;

    /**
     * @var string ODT model name
     */
	public string $model_odt;

    /**
     * @var string Last document name
     */
    public $last_main_doc;

    /**
     * @var string Object parent type
     */
	public string $parent_type;

    /**
     * @var int Object parent ID
     */
	public int $parent_id;

    /**
     * @var int User ID
     */
    public int $fk_user_creat;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             0 < if KO, ID of created object if OK
	 */
	public function create(User $user, bool $notrigger = false, $parentObject = 0): int
    {
		$now = dol_now();

		$this->ref_ext       = 'dolimeet_' . $this->ref;
		$this->date_creation = $this->db->idate($now);
		$this->tms           = $now;
		$this->status        = 1;
		$this->type          = $this->element;
        $this->json          = '';
        $this->model_odt     = '';
		$this->fk_user_creat = $user->id ?: 1;
		$this->parent_id     = $parentObject->id;
		$this->parent_type   = $parentObject->element_type ?: $parentObject->element;

		//$this->DocumentFillJSON($this);
		return $this->createCommon($user, $notrigger);
	}

//	/**
//	 * Function for JSON filling before saving in database
//	 *
//	 * @param $object
//	 */
//	public function DocumentFillJSON($object) {
//		switch ($object->element) {
//			case "timesheetdocument":
//				$this->json = $this->TimeSheetDocumentFillJSON($object);
//				break;
//		}
//	}

    /**
     * Load object in memory from the database
     *
     * @param  int|string  $id  ID object
     * @param  string|null $ref Ref
     * @return int              0 < if KO, 0 if not found, >0 if OK
     */
	public function fetch($id, string $ref = null): int
    {
		return $this->fetchCommon($id, $ref);
	}

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder  Sort Order
     * @param  string      $sortfield  Sort field
     * @param  int         $limit      Limit
     * @param  int         $offset     Offset
     * @param  array       $filter     Filter array. Example array('field'=>'value', 'customurl'=>...)
     * @param  string      $filtermode Filter mode (AND/OR)
     * @return int|array               0 < if KO, array of pages if OK
     * @throws Exception
     */
	public function fetchAll(string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = [];

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = [];
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             0 < if KO, >0 if OK
	 */
	public function update(User $user, bool $notrigger = false): int
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             0 < if KO, >0 if OK
	 */
	public function delete(User $user, bool $notrigger = false): int
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 * Initialise object with example values
	 * ID must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen(): void
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	string     $modele      Force template to use ('' to not force)
	 *  @param  Translate  $outputlangs	Object langs
	 *  @param  int        $hidedetails Hide details of lines
	 *  @param  int        $hidedesc    Hide description
	 *  @param  int        $hideref     Hide ref
	 *  @param  null|array $moreparams  Array to provide more information
	 *  @return int                     0 if KO, 1 if OK
	 */
	public function generateDocument(string $modele, Translate $outputlangs, int $hidedetails = 0, int $hidedesc = 0, int $hideref = 0, $moreparams = null): int
	{
		global $langs;
		$langs->load('dolimeet@dolimeet');

		$modelpath = 'custom/dolimeet/core/modules/dolimeet/dolimeetdocuments/' . $this->element . 'document/';

		$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

		$this->call_trigger(strtoupper($this->type).'_GENERATE', $moreparams['user']);

		return $result;
	}
}
