<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 * 	\defgroup   saturne     Module Saturne
 *  \brief      Saturne module descriptor.
 *
 *  \file       htdocs/saturne/core/modules/modSaturne.class.php
 *  \ingroup    saturne
 *  \brief      Description and activation file for module Saturne
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Saturne
 */
class modSaturne extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

        $langs->loadLangs(['saturne@saturne', 'other@saturne']);
		$this->numero = 436318;
		$this->rights_class = 'saturne';
		$this->family = '';
		$this->module_position = '50';
        $this->familyinfo = ['Evarisk' => ['position' => '01', 'label' => $langs->trans('Evarisk')]];
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans('SaturneDescription');
		$this->descriptionlong = $langs->trans('SaturneDescriptionLong');
		$this->editor_name = 'Evarisk';
		$this->editor_url  = 'https://evarisk.com/';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'Saturne_color@saturne';
		$this->module_parts = [
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => ['/saturne/css/Saturne_all.css'],
			'js' => [],
			'hooks' => [],
			'moduleforexternal' => 0,
        ];
		$this->dirs = [
			'/saturne/temp',
		];
		$this->config_page_url = ['setup.php@saturne'];
		$this->hidden = false;
		$this->depends = [];
		$this->requiredby = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = ['saturne@saturne'];
		$this->phpmin = [7, 0]; // Minimum version of PHP required by module
		$this->need_dolibarr_version = [14, 0]; // Minimum version of Dolibarr required by module
		$this->warnings_activation = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$j = 0;
		$this->const = [
			// CONST MEDIAS
			$j++ => array('SATURNE_MEDIA_MAX_WIDTH_MEDIUM', 'integer', 854, '', 0, 'current'),
			$j++ => array('SATURNE_MEDIA_MAX_HEIGHT_MEDIUM', 'integer', 480, '', 0, 'current'),
			$j++ => array('SATURNE_MEDIA_MAX_WIDTH_LARGE', 'integer', 1280, '', 0, 'current'),
			$j++ => array('SATURNE_MEDIA_MAX_HEIGHT_LARGE', 'integer', 720, '', 0, 'current'),
		];

		if (!isset($conf->saturne) || !isset($conf->saturne->enabled)) {
			$conf->saturne = new stdClass();
			$conf->saturne->enabled = 0;
		}

		$this->tabs = array();

		$this->rights = [];
		$r = 0;

        /* SATURNE PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('LireSaturne');
        $this->rights[$r][4] = 'lire';
        $this->rights[$r][5] = 1;
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadSaturne');
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = 1;
        $r++;

        /* ADMINPAGE PANEL ACCESS PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadAdminPage');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'read';

		$this->menu = [];
		$r = 0;

		$modulesList = [
			'DoliSMQ'  => 'dolismq',
			'DoliMeet' => 'dolimeet',
			'DoliSIRH' => 'dolisirh',
			'DoliCar'  => 'dolicar'
		];

		foreach ($modulesList as $moduleName => $moduleNameLowerCase) {

			$this->menu[$r++] = [
				'fk_menu'  => 'fk_mainmenu=' . $moduleNameLowerCase,
				'type'     => 'left',
				'titre'    => $langs->trans('ModuleConfig'),
				'prefix'   => '<i class="fas fa-cog pictofixedwidth"></i>',
				'mainmenu' => $moduleNameLowerCase,
				'leftmenu' => $moduleNameLowerCase . 'config',
				'url'      => '/'. $moduleNameLowerCase .'/admin/setup.php',
				'langs'    => $moduleNameLowerCase . '@' . $moduleNameLowerCase,
				'position' => 2000 + $r,
				'enabled'  => '$conf->'. $moduleNameLowerCase .'->enabled',
				'perms'    => '$user->rights->'. $moduleNameLowerCase .'->adminpage->read',
				'target'   => '',
				'user'     => 0,
			];

			$this->menu[$r++] = [
				'fk_menu'  => 'fk_mainmenu=' . $moduleNameLowerCase,
				'type'     => 'left',
				'titre'    => $langs->transnoentities('MinimizeMenu'),
				'prefix'   => '<i class="fas fa-chevron-circle-left pictofixedwidth"></i>',
				'mainmenu' => $moduleNameLowerCase,
				'leftmenu' => 'minimizemenu',
				'url'      => '',
				'langs'    => $moduleNameLowerCase . '@' . $moduleNameLowerCase,
				'position' => 2000 + $r,
				'enabled'  => '$conf->'. $moduleNameLowerCase .'->enabled',
				'perms'    => '$user->rights->'. $moduleNameLowerCase .'->lire',
				'target'   => '',
				'user'     => 0,
			];
		}
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);
		$sql = [];

		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if ( ! preg_match('/\./', $subFolder)) {
				$this->_load_tables('/saturne/sql/' . $subFolder . '/');
			}
		}

		$result = $this->_load_tables('/saturne/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = [];
		return $this->_remove($sql, $options);
	}
}
