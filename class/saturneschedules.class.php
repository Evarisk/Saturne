<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file        class/saturneschedules.class.php
 * \ingroup     saturne
 * \brief       This file is a CRUD class file for SaturneSchedules (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for SaturneSchedules
 */
class SaturneSchedules extends CommonObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Element type of object.
	 */
	public $element = 'saturne_schedules';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'saturne_schedules';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public int $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public int $isextrafieldmanaged = 1;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public array $fields = [
		'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
		'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 0, 'index' => 1],
		'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 20,  'notnull' => 1, 'visible' => 0],
		'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 30,  'notnull' => 0, 'visible' => 0],
		'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0, 'index' => 1],
		'element_type'  => ['type' => 'varchar(50)',  'label' => 'ElementType',      'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
		'element_id'    => ['type' => 'integer',      'label' => 'ElementID',        'enabled' => 1, 'position' => 60,  'notnull' => 1, 'visible' => 0],
		'monday'        => ['type' => 'varchar(128)', 'label' => 'Day 0',            'enabled' => 1, 'position' => 70,  'notnull' => 0, 'visible' => 1],
		'tuesday'       => ['type' => 'varchar(128)', 'label' => 'Day 1',            'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1],
		'wednesday'     => ['type' => 'varchar(128)', 'label' => 'Day 2',            'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 1],
		'thursday'      => ['type' => 'varchar(128)', 'label' => 'Day 3',            'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 1],
		'friday'        => ['type' => 'varchar(128)', 'label' => 'Day 4',            'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 1],
		'saturday'      => ['type' => 'varchar(128)', 'label' => 'Day 5',            'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 1],
		'sunday'        => ['type' => 'varchar(128)', 'label' => 'Day 6',            'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 1],
		'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 140, 'notnull' => 1,  'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'enabled' => 1, 'position' => 150, 'notnull' => 0,  'visible' => 0, 'foreignkey' => 'user.rowid'],
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
     * @var int Timestamp
     */
    public int $tms;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string Element object type
     */
	public string $element_type;

    /**
     * @var int Element object ID
     */
	public int $element_id;

    /**
     * @var string First day week
     */
	public string $monday = '';

    /**
     * @var string Second day week
     */
	public string $tuesday = '';

    /**
     * @var string Third day week
     */
	public string $wednesday = '';

    /**
     * @var string Fourth day week
     */
	public string $thursday = '';

    /**
     * @var string Fifth day week
     */
	public string $friday = '';

    /**
     * @var string Sixth day week
     */
	public string $saturday = '';

    /**
     * @var string Seventh day week
     */
	public string $sunday = '';

    /**
     * @var int User ID
     */
	public int $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public ?int $fk_user_modif;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])){
            $this->fields['rowid']['visible'] = 0;
        }
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
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
	public function create(User $user, bool $notrigger = false): int
    {
		return $this->createCommon($user, $notrigger);
	}

    /**
     * Load object in memory from the database
     *
     * @param  int         $id        Id object
     * @param  string|null $ref       Ref
     * @param  string      $morewhere More SQL filters (' AND ...')
     * @return int                    0 < if KO, 0 if not found, >0 if OK
     */
	public function fetch(int $id, string $ref = null, string $morewhere = ''): int
    {
		return $this->fetchCommon($id, $ref, $morewhere);
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
}
