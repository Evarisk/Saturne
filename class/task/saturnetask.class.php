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
 *       \file       class/task/saturnetask.class.php
 *       \ingroup    saturne
 *       \brief      This file is a CRUD class file for SaturneTask (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

//require_once __DIR__ . '/digiriskstats.php';

/**
 *	Class for SaturneTask
 */
class SaturneTask extends Task
{
    /**
     * @var int Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 1;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = [
		'rowid'              => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => 'Id'],
		'ref'                => ['type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => 'Reference of object'],
		'label'              => ['type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'help' => "Help text", 'showoncombobox' => '1',],
		'description'        => ['type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 30, 'notnull' => 0, 'visible' => 3,],
		'entity'             => ['type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0],
		'datec'              => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 50, 'positioncard' => 10, 'notnull' => 1, 'visible' => 5],
		'dateo'              => ['type' => 'datetime', 'label' => 'DateStart', 'enabled' => '1', 'position' => 60, 'positioncard' => 10, 'notnull' => 1, 'visible' => 5],
		'datee'              => ['type' => 'datetime', 'label' => 'DateEnd', 'enabled' => '1', 'position' => 70, 'positioncard' => 10, 'notnull' => 1, 'visible' => 5],
		'tms'                => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 0],
		'duration_effective' => ['type' => 'integer', 'label' => 'EffectiveDuration', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 0],
		'planned_workload'   => ['type' => 'integer', 'label' => 'PlannedWorkload', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 0],
		'progress'           => ['type' => 'integer', 'label' => 'Progress', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => 0],
		'budget_amount'      => ['type' => 'integer', 'label' => 'Budget', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 0],
		'priority'           => ['type' => 'integer', 'label' => 'Priority', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => 0],
		'note_public'        => ['type' => 'html', 'label' => 'PublicNote', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 0],
		'note_private'       => ['type' => 'html', 'label' => 'PrivateNote', 'enabled' => '1', 'position' => 150, 'notnull' => 0, 'visible' => 0],
		'rang'               => ['type' => 'integer', 'label' => 'Rank', 'enabled' => '1', 'position' => 160, 'notnull' => 0, 'visible' => 0],
		'fk_statut'          => ['type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 170, 'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => '0', 'arrayofkeyval' => ['0' => 'Draft', '1' => 'Validated', '2' => 'Locked']],
		'fk_projet'          => ['type' => 'integer', 'label' => 'Project', 'enabled' => '1', 'position' => 180, 'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => '0', 'arrayofkeyval' => ['0' => 'Draft', '1' => 'Validated', '2' => 'Locked']],
		'fk_task_parent'     => ['type' => 'integer', 'label' => 'TaskParent', 'enabled' => '1', 'position' => 190, 'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => '0', 'arrayofkeyval' => ['0' => 'Draft', '1' => 'Validated', '2' => 'Locked']],
		'fk_user_creat'      => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 200, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
		'fk_user_valid'      => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 210, 'notnull' => -1, 'visible' => 0],
	];


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    /**
     * Load dashboard info task
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard($projectId): array
    {
        $arrayTasksByProgress = $this->getTasksByProgress($projectId);

        $array['graphs'] = [$arrayTasksByProgress];

        return $array;
    }

	/**
	 * Get tasks by progress.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getTasksByProgress($projectId = 0)
	{
        global $conf, $langs, $form;

        // Graph Title parameters
        $array['title'] = $form->textwithpicto($langs->transnoentities('TasksRepartition'), $langs->transnoentities('TasksFromProject'));
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('TaskAt0Percent') . ' %',
                'color' => '#e05353'
            ],
            1 => [
                'label' => $langs->transnoentities('TaskInProgress'),
                'color' => '#e9ad4f'
            ],
            2 => [
                'label' => $langs->transnoentities('TaskAt100Percent') . ' %',
                'color' => '#47e58e'
            ]
        ];

        $array['data'][0] = 0;
        $array['data'][1] = 0;
        $array['data'][2] = 0;
        $tasks            = $this->getTasksArray(0, 0, $projectId);
        if (is_array($tasks) && !empty($tasks)) {
            foreach ($tasks as $task) {
                if ($task->progress == 0) {
                    $array['data'][0] = $array['data'][0] + 1;
                } elseif ($task->progress > 0 && $task->progress < 100) {
                    $array['data'][1] = $array['data'][1] + 1;
                } else {
                    $array['data'][2] = $array['data'][2] + 1;
                }
            }
        }

        return $array;
	}

	/**
	 * get task progress css class.
	 *
	 * @param  float  $progress Progress of the task
	 *
	 * @return string           CSS class
	 */
	public function getTaskProgressColorClass($progress)
	{
		switch (true) {
			case $progress < 50 :
				return 'progress-red';
			case $progress < 99 :
				return 'progress-yellow';
			case $progress :
				return 'progress-green';
		}
	}

	/**
	 *  Load all records of time spent for all user
	 *
	 * @param string       $morewherefilter Add more filter into where SQL request (must start with ' AND ...')
	 * @param string       $sortorder       Sort Order
     * @param string       $sortfield       Sort field
     * @param string       $sortedByTasks   Sort result array by tasks
	 *
	 * @return array|int                    0 < if KO, array of time spent if OK
	 * @throws Exception
	 */
	public function fetchAllTimeSpentAllUsers($morewherefilter = '', $sortfield = '', $sortorder = '', $sortedByTasks = 0)
	{
        $versionEighteenOrMore = 0;

        if ((float) DOL_VERSION >= 18.0) {
            $versionEighteenOrMore = 1;
        }
        $arrayres = array();

        $sql = "SELECT * FROM (";
        $sql .= "SELECT";
        $sql .= " s.rowid as socid,";
        $sql .= " s.nom as thirdparty_name,";
        $sql .= " s.email as thirdparty_email,";
        $sql .= " ptt.rowid,";
        if ($versionEighteenOrMore) {
            $sql .= " ptt.fk_element AS fk_element,";
            $sql .= " ptt.element_date AS element_date,";
            $sql .= " ptt.element_datehour AS element_datehour,";
            $sql .= " ptt.element_date_withhour AS element_date_withhour,";
            $sql .= " ptt.element_duration AS element_duration,";
        } else {
            $sql .= " ptt.fk_task AS fk_element,";
            $sql .= " ptt.task_date AS element_date,";
            $sql .= " ptt.task_datehour AS element_datehour,";
            $sql .= " ptt.task_date_withhour AS element_date_withhour,";
            $sql .= " ptt.task_duration AS element_duration,";
        }
        $sql .= " ptt.fk_user,";
        $sql .= " ptt.note,";
        $sql .= " ptt.thm,";
        $sql .= " pt.rowid as task_id,";
        $sql .= " pt.ref as task_ref,";
        $sql .= " pt.label as task_label,";
        $sql .= " pt.fk_projet as project_linked_id,";
        $sql .= " pt.entity as task_entity,";
        $sql .= " p.rowid as project_id,";
        $sql .= " p.ref as project_ref,";
        $sql .= " p.title as project_label,";
        $sql .= " p.public as public";
        $sql .= " FROM ".MAIN_DB_PREFIX;
        if ($versionEighteenOrMore) {
            $sql .= "element_time as ptt, ";
        } else {
            $sql .= "projet_task_time as ptt, ";
        }
        $sql .= MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
        $sql .= ") AS selector";
        $sql .= " WHERE fk_element = task_id AND project_linked_id = project_id";
        $sql .= " AND task_entity IN (".getEntity('project').")";
        if ($morewherefilter) {
            $sql .= $morewherefilter;
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }

        dol_syslog(get_class($this)."::fetchAllTimeSpentAllUser", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);

            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $newobj = new stdClass();

                $newobj->socid            = $obj->socid;
                $newobj->thirdparty_name  = $obj->thirdparty_name;
                $newobj->thirdparty_email = $obj->thirdparty_email;

                $newobj->fk_project    = $obj->project_id;
                $newobj->project_ref   = $obj->project_ref;
                $newobj->project_label = $obj->project_label;
                $newobj->public        = $obj->project_public;

                $newobj->fk_task	= $obj->task_id;
                $newobj->task_ref   = $obj->task_ref;
                $newobj->task_label = $obj->task_label;

                $newobj->timespent_id       = $obj->rowid;
                $newobj->timespent_date     =  $this->db->jdate($obj->element_date);
                $newobj->timespent_datehour	=  $this->db->jdate($obj->element_datehour);
                $newobj->timespent_withhour =  $obj->element_date_withhour;
                $newobj->timespent_duration =  $obj->element_duration;
                $newobj->timespent_fk_user  = $obj->fk_user;
                $newobj->timespent_thm      = $obj->thm;	// hourly rate
                $newobj->timespent_note     = $obj->note;

                $arrayres[] = $newobj;

                $i++;
            }

            $this->db->free($resql);
        } else {
            dol_print_error($this->db);
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }

        if ($sortedByTasks > 0) {
            $timeSpentSortedByTasks = [];
            if (is_array($arrayres) && !empty($arrayres)) {
                foreach ($arrayres as $timeSpent) {
                    $timeSpentSortedByTasks[$timeSpent->fk_task][$timeSpent->timespent_id] = $timeSpent;
                }
            }
            return $timeSpentSortedByTasks;
        } else {
            return $arrayres;
        }
	}
}

