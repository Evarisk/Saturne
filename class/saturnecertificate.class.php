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
 * \file        class/saturnecertificate.class.php
 * \ingroup     saturne
 * \brief       This file is a CRUD class file for SaturneSaturneCertificate (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for SaturneCertificate
 */
class SaturneCertificate extends CommonObject
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Module name.
	 */
	public string $module = 'saturne';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'saturne_certificate';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'saturne_object_certificate';

	/**
	 * @var int Does this object support multicompany module ?
	 * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
	 */
	public int $ismultientitymanaged = 1;

	/**
	 * @var int Does object support extrafields ? 0 = No, 1 = Yes.
	 */
	public int $isextrafieldmanaged = 1;

	/**
	 * @var string Name of icon for certificate. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'certificate@saturne' if picto is file 'img/object_certificate.png'.
	 */
	public string $picto = 'fontawesome_fa-user-graduate_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_EXPIRED   = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     *  'type' field format:
     *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *  	'select' (list of values are in 'options'),
     *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *  	'chkbxlst:...',
     *  	'varchar(x)',
     *  	'text', 'text:none', 'html',
     *   	'double(24,8)', 'real', 'price',
     *  	'date', 'datetime', 'timestamp', 'duration',
     *  	'boolean', 'checkbox', 'radio', 'array',
     *  	'mail', 'phone', 'url', 'password', 'ip'
     *		Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'picto' is code of a picto to show before value in forms
     *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *	'validate' is 1 if you need to validate with $this->validateField()
     *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public array $fields = [
        'rowid'             => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'               => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'           => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'            => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'     => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
        'tms'               => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0],
        'import_key'        => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'            => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 220, 'notnull' => 1, 'visible' => 2, 'default' => 0, 'index' => 1, 'validate' => 1, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'ValidatePendingSignature', 2 => 'Expired', 3 => 'Archived']],
        'label'             => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'showoncombobox' => 2, 'validate' => 1, 'autofocusoncreate' => 1],
        'date_start'        => ['type' => 'date',         'label' => 'DateStart',        'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 1],
        'date_end'          => ['type' => 'date',         'label' => 'DateEnd',          'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 1],
        'description'       => ['type' => 'html',         'label' => 'Description',      'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 3, 'validate' => 1],
        'note_public'       => ['type' => 'html',         'label' => 'NotePublic',       'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => 1],
        'note_private'      => ['type' => 'html',         'label' => 'NotePrivate',      'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => 1],
        'element_type'      => ['type' => 'varchar(255)', 'label' => 'ElementType',      'enabled' => 1, 'position' => 125, 'notnull' => 0, 'visible' => 0],
        'fk_element'        => ['type' => 'integer',      'label' => 'FkElement',        'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 3, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx'],
        'sha256'            => ['type' => 'text',         'label' => 'Sha256',           'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 0],
        'url'               => ['type' => 'text',         'label' => 'Url',              'enabled' => 1, 'position' => 170, 'notnull' => 0, 'visible' => 0],
        'public_url'        => ['type' => 'text',         'label' => 'PublicUrl',        'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => 0],
        'status_validation' => ['type' => 'smallint',     'label' => 'StatusValidation', 'enabled' => 1, 'position' => 190, 'notnull' => 0, 'visible' => 0],
        'fk_soc'            => ['type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 80,  'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'societe.rowid'],
        'fk_project'        => ['type' => 'integer:Project:projet/class/project.class.php:1',  'label' => 'Project',    'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 90,  'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'validate' => 1, 'foreignkey' => 'projet.rowid'],
        'fk_user_creat'     => ['type' => 'integer:User:user/class/user.class.php',            'label' => 'UserAuthor', 'picto' => 'user',    'enabled' => 1,                         'position' => 200, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'     => ['type' => 'integer:User:user/class/user.class.php',            'label' => 'UserModif',  'picto' => 'user',    'enabled' => 1,                         'position' => 210, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
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
    public $import_key;

    /**
     * @var int Status.
     */
    public $status;

    /**
     * @var string Label.
     */
    public string $label;

    /**
     * @var int|string Start date.
     */
    public $date_start;

    /**
     * @var int|string End date.
     */
    public $date_end;

    /**
     * @var string Description.
     */
	public string $description;

    /**
     * @var string Public note.
     */
    public $note_public;

    /**
     * @var string Private note.
     */
    public $note_private;

    /**
     * @var string Element type.
     */
	public string $element_type;

    /**
     * @var int|string Element ID.
     */
	public $fk_element;

    /**
     * @var string Sha256.
     */
    public string $sha256 = '';

    /**
     * @var string Url.
     */
    public string $url = '';

    /**
     * @var string Public url.
     */
    public string $public_url = '';

    /**
     * @var int|null Status validation.
     */
    public ?int $status_validation;


    /**
     * @var int|string ThirdParty ID.
     */
    public $fk_soc;

    /**
     * @var int Project ID.
     */
    public $fk_project;

    /**
     * @var int User ID.
     */
    public int $fk_user_creat;

    /**
     * @var int|null User ID.
     */
    public ?int $fk_user_modif;

	/**
	 * Constructor.
	 *
	 * @param DoliDb $db Database handler.
	 */
	public function __construct(DoliDB $db, string $moduleNameLowerCase = 'saturne', string $objectType = 'saturne_certificate')
	{
		global $conf, $langs;

		$this->db      = $db;
        $this->module  = $moduleNameLowerCase;
        $this->element = $objectType;

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
     * Create object into database.
     *
     * @param  User $user      User that creates.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, ID of created object if OK.
     */
	public function create(User $user, bool $notrigger = false): int
    {
        return $this->createCommon($user, $notrigger);
	}

    /**
     * Load object in memory from the database.
     *
     * @param  int         $id        Id object.
     * @param  string|null $ref       Ref.
     * @param  string      $morewhere More SQL filters (' AND ...').
     * @return int                    0 < if KO, 0 if not found, > 0 if OK.
     */
    public function fetch(int $id, string $ref = null, string $morewhere = ''): int
    {
        return $this->fetchCommon($id, $ref, $morewhere);
    }

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string     $sortorder  Sort Order.
	 * @param  string     $sortfield  Sort field.
	 * @param  int        $limit      Limit.
	 * @param  int        $offset     Offset.
	 * @param  array      $filter     Filter array. Example array('field'=>'valueforlike', 'customurl'=>...).
	 * @param  string     $filtermode Filter mode (AND/OR).
	 * @return array|int              Int <0 if KO, array of pages if OK.
     * @throws Exception
	 */
	public function fetchAll(string $sortorder = '', string $sortfield = '', int $limit = 0, int $offset = 0, array $filter = [], string $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = [];

		$sql = 'SELECT ';
		$sql .= $this->getFieldList('t');
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN (' . getEntity($this->element) . ')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter.
		$sqlwhere = [];
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . ' = ' . ((int) $value);
				} elseif (in_array($this->fields[$key]['type'], ['date', 'datetime', 'timestamp'])) {
					$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
				} else {
					$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($value) . "%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

    /**
     * Update object into database.
     *
     * @param  User $user      User that modifies.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, > 0 if OK.
     */
    public function update(User $user, bool $notrigger = false): int
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database.
     *
     * @param  User $user      User that deletes.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, > 0 if OK.
     */
    public function delete(User $user, bool $notrigger = false): int
    {
        return $this->deleteCommon($user, $notrigger);
    }

    /**
     * Validate object.
     *
     * @param  User      $user      User making status change.
     * @param  int       $notrigger 1 = Does not execute triggers, 0 = execute triggers.
     * @return int                  0 < if OK, 0=Nothing done, > 0 if KO.
     * @throws Exception
     */
	public function validate(User $user, int $notrigger = 0): int
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error               = 0;
        $moduleNameLowerCase = $this->module;
        $objectType          = $this->element;

		// Protection.
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this) . '::validate action abandonned: already validated', LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		// Define new ref.
		if ((preg_match('/^\(?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happen, but when it occurs, the test save life.
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate.
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " SET ref = '" . $this->db->escape($num) . "',";
			$sql .= ' status = ' . self::STATUS_VALIDATED;
			$sql .= ' WHERE rowid = ' . ($this->id);

			dol_syslog(get_class($this) . '::validate()', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger.
				$result = $this->call_trigger('SATURNECERTIFICATE_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers.
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref.
			if (preg_match('/^\(?PROV/i', $this->ref)) {
				// Now we rename also files into index.
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'ecm_files';
				$sql .= " SET filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ')),';
				$sql .= " filepath = '" . $this->db->escape($objectType . '/' . $this->ref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = '" . $this->db->escape($objectType . '/' . $this->ref) . "' AND entity = " . $conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments.
				$oldRef    = dol_sanitizeFileName($this->ref);
				$newRef    = dol_sanitizeFileName($num);
				$dirSource = $conf->$moduleNameLowerCase->dir_output . '/' . $objectType . '/' . $oldRef;
				$dirDest   = $conf->$moduleNameLowerCase->dir_output . '/' . $objectType . '/' . $newRef;
				if (!$error && file_exists($dirSource)) {
					dol_syslog(get_class($this) . '::validate() rename dir ' . $dirSource . ' into ' . $dirDest);

					if (@rename($dirSource, $dirDest)) {
						dol_syslog('Rename ok');
						// Rename docs starting with $oldRef with $newRef.
                        $listOfFiles = dol_dir_list($conf->$moduleNameLowerCase->dir_output . '/' . $objectType . '/' . $newRef, 'files', 1, '^' . preg_quote($oldRef, '/'));
						foreach ($listOfFiles as $fileEntry) {
							$dirSource = $fileEntry['name'];
							$dirDest   = preg_replace('/^' . preg_quote($oldRef, '/') . '/', $newRef, $dirSource);
							$dirSource = $fileEntry['path'] . '/' . $dirSource;
							$dirDest   = $fileEntry['path'] . '/' . $dirDest;
							@rename($dirSource, $dirDest);
						}
					}
				}
			}
		}

		// Set new ref and current status.
		if (!$error) {
			$this->ref    = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

    /**
     * Set draft status.
     *
     * @param  User $user      Object user that modify.
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers.
     * @return int             0 < if KO, > 0 if OK.
     */
	public function setDraft(User $user, int $notrigger = 0): int
	{
		// Protection.
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'SATURNECERTIFICATE_UNVALIDATE');
	}

    /**
     * Set archived status.
     *
     * @param  User $user      Object user that modify.
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers.
     * @return int             0 < if KO, > 0 if OK.
     */
    public function setArchived(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, self::STATUS_ARCHIVED, $notrigger, 'SATURNECERTIFICATE_ARCHIVED');
    }

    /**
     *  Return a link to the object card (with optionaly the picto).
     *
     *  @param  int     $withpicto              Include picto in link (0 = No picto, 1 = Include picto into link, 2 = Only picto).
     *  @param  string  $option                 On what the link point to ('nolink', ...).
     *  @param  int     $notooltip              1 = Disable tooltip.
     *  @param  string  $morecss                Add more css on link.
     *  @param  int     $save_lastsearch_value -1 = Auto, 0 = No save of lastsearch_values when clicking, 1 = Save lastsearch_values whenclicking.
     *  @return	string                          String with URL.
     */
	public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1): string
	{
		global $conf, $langs;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips.
		}

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans(ucfirst($this->element)) . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ' : </b> ' . $this->ref;

		$url = dol_buildpath('/' . $this->module . '/view/' . $this->element . '/' . $this->element . '_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not.
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER['PHP_SELF'])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans('Show' . ucfirst($this->element));
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="' . $url . '"';
		}
        if ($option == 'blank') {
            $linkstart .= 'target=_blank';
        }
		$linkstart .= $linkclose . '>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

        $result .= $linkstart;

        if ($withpicto > 0) {
            $result .= img_picto('', $this->picto) . ' ';
        }

        if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;

		global $action, $hookmanager;
		$hookmanager->initHooks([$this->element . 'dao']);
		$parameters = ['id' => $this->id, 'getnomurl' => $result];
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

    /**
     * Return the label of the status.
     *
     * @param  int    $mode 0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
     * @return string       Label of status.
     */
    public function getLibStatut(int $mode = 0): string
    {
        return $this->LibStatut($this->status, $mode);
    }

    /**
     * Return the status.
     *
     * @param  int    $status ID status.
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
     * @return string         Label of status.
     */
	public function LibStatut(int $status, int $mode = 0): string
	{
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
			$this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
            $this->labelStatus[self::STATUS_EXPIRED]   = $langs->transnoentitiesnoconv('Expired');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');

            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
			$this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingSignature');
            $this->labelStatusShort[self::STATUS_EXPIRED]   = $langs->transnoentitiesnoconv('Expired');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
		}

		$statusType = 'status' . $status;
        if ($status == self::STATUS_DELETED) {
            $statusType = 'status0';
        }
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status3';
        }
        if ($status == self::STATUS_EXPIRED || $status == self::STATUS_ARCHIVED) {
            $statusType  = 'status8';
        }

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

    /**
     *	Load the info information in the object.
     *
     *	@param  int   $id ID of object.
     *	@return	void
     */
	public function info(int $id)
	{
        $sql = 'SELECT t.rowid, t.date_creation as datec, t.tms as datem,';
        $sql .= ' t.fk_user_creat, t.fk_user_modif';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE t.rowid = ' . $id;

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

                $this->user_creation_id     = $obj->fk_user_creat;
                $this->user_modification_id = $obj->fk_user_modif;
                $this->date_creation        = $this->db->jdate($obj->datec);
                $this->date_modification    = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

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
     * Returns the reference to the following non-used object depending on the active numbering module.
     *
     *  @return string Object free reference.
     */
	public function getNextNumRef(): string
	{
		global $langs, $conf;

        $moduleName          = strtoupper($this->module);
        $moduleNameLowerCase = $this->module;
        $objectType          = $this->element;
        $numRefConf          = $moduleName . '_' . strtoupper($objectType) . '_ADDON';

		if (empty($conf->global->$moduleName)) {
			$conf->global->$moduleName = 'mod_' . $objectType . '_standard';
		}

		if (!empty($conf->global->$moduleName)) {
            $result    = false;
			$file      = $conf->global->$moduleName . '.php';
			$className = $conf->global->$moduleName;

			// Include file with class.
			$dirModels = array_merge(['/'], $conf->modules_parts['models']);
			foreach ($dirModels as $relDir) {
				$dir = dol_buildpath($relDir . 'core/modules/'. $moduleNameLowerCase . '/' . $objectType . '/');

				// Load file with numbering class (if found).
				$result |= @include_once $dir . $file;
			}

			if ($result === false) {
				dol_print_error('', 'Failed to include file ' . $file);
				return '';
			}

			if (class_exists($className)) {
				$obj    = new $className();
				$numRef = $obj->getNextValue($this);

				if ($numRef != '' && $numRef != '-1') {
					return $numRef;
				} else {
					$this->error = $obj->error;
					return '';
				}
			} else {
				print $langs->trans('Error') . ' ' . $langs->trans('ClassNotFound') . ' ' . $className;
				return '';
			}
		} else {
			print $langs->trans('ErrorNumberingModuleNotSetup', $this->element);
			return '';
		}
	}
}