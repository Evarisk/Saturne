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

        $langs->load('saturne@saturne');

		// Id for module (must be unique).
		$this->numero = 436311;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'saturne';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = '';

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '50';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = ['Evarisk' => ['position' => '01', 'label' => $langs->trans('Evarisk')]];
		// Module label (no space allowed), used if translation string 'ModuleSaturneName' not found (Saturne is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleSaturneDesc' not found (Saturne is name of module).
		$this->description = $langs->trans('SaturneDescription');
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = $langs->trans('SaturneDescriptionLong');

		// Author
		$this->editor_name = 'Evarisk';
		$this->editor_url  = 'https://evarisk.com/';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where SATURNE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'Saturne_color@saturne';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = [
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 0,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => ['/saturne/css/Saturne_all.css'],
			// Set this to relative path of js file if module must load a js on all pages
			'js' => [
				//   '/saturne/js/saturne.js.php',
            ],
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => [
				//   'data' => array(
				//       'hookcontext1',
				//       'hookcontext2',
				//   ),
				//   'entity' => '0',
            ],
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
        ];

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/saturne/temp","/saturne/subdir");
		$this->dirs = ['/saturne/temp'];

		// Config pages. Put here list of php page, stored into saturne/admin directory, to use to setup module.
		$this->config_page_url = ['setup.php@saturne'];

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = [];
		$this->requiredby = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = ['saturne@saturne'];

		// Prerequisites
		$this->phpmin = [7, 0]; // Minimum version of PHP required by module
		$this->need_dolibarr_version = [14, 0]; // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$j = 0;
		$this->const = [
			$j++ => array('SATURNE_MEDIA_MAX_WIDTH_MEDIUM', 'integer', 854, '', 0, 'current'),
			$j++ => array('SATURNE_MEDIA_MAX_HEIGHT_MEDIUM', 'integer', 480, '', 0, 'current'),
			$j++ => array('SATURNE_MEDIA_MAX_WIDTH_LARGE', 'integer', 1280, '', 0, 'current'),
			$j++ => array('SATURNE_MEDIA_MAX_HEIGHT_LARGE', 'integer', 720, '', 0, 'current'),
		];

		if (!isset($conf->saturne) || !isset($conf->saturne->enabled)) {
			$conf->saturne = new stdClass();
			$conf->saturne->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();

		// Permissions provided by this module
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

		// Main menu entries to add
		$this->menu = [];
		$r = 0;

		$modules_array = [
			'dolismq'
		];

		foreach ($modules_array as $module) {
			$this->menu[$r++] = [
				'fk_menu'  => 'fk_mainmenu=' . $module,
				'type'     => 'left',
				'titre'    => $langs->transnoentities('MinimizeMenu'),
				'prefix'   => '<i class="fas fa-chevron-circle-left pictofixedwidth"></i>',
				'mainmenu' => $module,
				'leftmenu' => 'minimizemenu',
				'url'      => '',
				'langs'    => $module . '@' . $module,
				'position' => 2000 + $r,
				'enabled'  => '$conf->'. $module .'->enabled',
				'perms'    => '$user->rights->'. $module .'->lire',
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
