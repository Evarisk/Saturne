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
 * \file    class/saturneobject.class.php
 * \ingroup saturne
 * \brief   This file is a CRUD class file for SaturneObject (Create/Read/Update/Delete).
 */

// Load Dolibarr Libraries.
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for SaturneObject.
 */
abstract class SaturneObject extends CommonObject
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

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
	 * Constructor.
	 *
	 * @param DoliDb $db Database handler.
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
     * @param  int|string  $id        ID object.
     * @param  string|null $ref       Ref.
     * @param  string      $morewhere More SQL filters (' AND ...').
     * @return int                    0 < if KO, 0 if not found, > 0 if OK.
     */
    public function fetch($id, string $ref = null, string $morewhere = ''): int
    {
        $result = $this->fetchCommon($id, $ref, $morewhere);
        if ($result > 0 && !empty($this->table_element_line)) {
            $this->fetchLines();
        }
        return $result;
    }

    /**
     * Load object lines in memory from the database.
     *
     * @return int 0 < if KO, 0 if not found, >0 if OK.
     */
    public function fetchLines(): int
    {
        $this->lines = [];
        return $this->fetchLinesCommon();
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

				$record = new $this($this->db);
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
		if ($this->status == $this::STATUS_VALIDATED) {
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
			$sql .= ' status = ' . $this::STATUS_VALIDATED;
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
				$result = $this->call_trigger(strtoupper($this->element) . '_VALIDATE', $user);
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
			$this->status = $this::STATUS_VALIDATED;
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
		if ($this->status <= $this::STATUS_DRAFT) {
			return 0;
		}

		return $this->setStatusCommon($user, $this::STATUS_DRAFT, $notrigger, strtoupper($this->element) . '_UNVALIDATE');
	}

    /**
     * Set locked status.
     *
     * @param  User $user      Object user that modify.
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers.
     * @return int             0 < if KO, > 0 if OK.
     */
    public function setLocked(User $user, int $notrigger = 0): int
    {
        return $this->setStatusCommon($user, $this::STATUS_LOCKED, $notrigger, strtoupper($this->element) . '_LOCKED');
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
        return $this->setStatusCommon($user, $this::STATUS_ARCHIVED, $notrigger, strtoupper($this->element) . '_ARCHIVED');
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

    /**
     * Sets object to supplied categories.
     *
     * Deletes object from existing categories not supplied.
     * Adds it to non-existing supplied categories.
     * Existing categories are left untouched.
     *
     * @param  int[]|int $categories Category or categories IDs.
     * @return float|int
     */
    public function setCategories($categories)
    {
        return parent::setCategoriesCommon($categories, $this->element);
    }
}