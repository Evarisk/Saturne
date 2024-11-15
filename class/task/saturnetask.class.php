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
        global $user, $langs;

        $confName        = dol_strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = json_decode($user->conf->$confName);
        $array = ['graphs' => [], 'disabledGraphs' => []];

        if (empty($dashboardConfig->graphs->TasksRepartition->hide)) {
            $array['graphs'][] = $this->getTasksByProgress($projectId);
        } else {
            $array['disabledGraphs']['TasksRepartition'] = $langs->transnoentities('TasksRepartition');
        }

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
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

        $form = new Form($this->db);

        // Graph Title parameters
        $array['title'] = $form->textwithpicto($langs->transnoentities('TasksRepartition'), $langs->transnoentities('TasksFromProject'));
        $array['name']  = 'TasksRepartition';
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
	 *	Return clickable name (with picto eventually)
	 *
	 * @param  int		$withpicto		        0=No picto, 1=Include picto into link, 2=Only picto
	 * @param  string	$option			        'withproject' or ''
	 * @param  string	$mode			        Mode 'task', 'time', 'contact', 'note', document' define page to link to.
	 * @param  int		$addlabel		        0=Default, 1=Add label into string, >1=Add first chars into string
	 * @param  string	$sep					Separator between ref and label if option addlabel is set
	 * @param  int   	$notooltip		        1=Disable tooltip
	 * @param  int      $saveLastSearchValue    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return string					        Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $mode = 'task', $addlabel = 0, $sep = ' - ', $notooltip = 0, $saveLastSearchValue = -1, $showFavorite = 0)
	{
		global $action, $conf, $hookmanager, $langs, $user;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result     = '';
		$label      = img_picto('', $this->picto) . ' <u>' . $langs->trans("Task") . '</u>';
		if ( ! empty($this->ref))
			$label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if ( ! empty($this->label))
			$label .= '<br><b>' . $langs->trans('LabelTask') . ':</b> ' . $this->label;
		if ($this->date_start || $this->date_end) {
			$label .= "<br>" . get_date_range($this->date_start, $this->date_end, '', $langs, 0);
		}

		$url = DOL_URL_ROOT . '/projet/tasks/' . $mode . '.php?id=' . $this->id . ($option == 'withproject' ? '&withproject=1' : '');
		// Add param to save lastsearch_values or not
		$addSaveLastSearchValue                                                                                      = ($saveLastSearchValue == 1 ? 1 : 0);
		if ($saveLastSearchValue == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $addSaveLastSearchValue = 1;
		if ($addSaveLastSearchValue) $url                                                                           .= '&save_lastsearch_values=1';

		$linkclose = '';
		if (empty($notooltip)) {
			if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label      = $langs->trans("ShowTask");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip nowraponall"';
		} else {
			$linkclose .= ' class="nowraponall"';
		}

		$linkstart  = '<a target="_blank" href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';

		$picto = 'projecttask';

		$result                      .= $linkstart;
		if ($withpicto) $result      .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result                      .= $linkend;
		if ($withpicto != 2) $result .= (($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		if ($showFavorite) {
			if (function_exists('is_task_favorite') && is_task_favorite($this->id, $user->id)) {
				$favoriteStar = '<span class="fas fa-star toggleTaskFavorite" onclick="toggleTaskFavorite()"></span>';
			} else {
				$favoriteStar = '<span class="far fa-star toggleTaskFavorite" onclick="toggleTaskFavorite()"></span>';
			}

			$result .= $favoriteStar;
		}

        $hookmanager->initHooks(['saturnetaskdao']);
        $parameters = ['id' => $this->id, 'getnomurl' => &$result];
        $resHook    = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($resHook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

		return $result;
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
            $sql .= " ptt.elementtype AS elementtype,";
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

