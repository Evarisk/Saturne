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
			"/ecm/saturne/certificatedocument"
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

			// CONST CERTIFICATE
			$j++ => array('SATURNE_CERTIFICATE_ADDON', 'chaine', 'mod_certificate_standard', '', 0, 'current'),

			// CONST CERTIFICATE DOCUMENT
			$j++ => array('SATURNE_CERTIFICATEDOCUMENT_ADDON', 'chaine', 'mod_certificatedocument_standard', '', 0, 'current'),
			$j++ => array('SATURNE_CERTIFICATEDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/saturne/documents/doctemplates/certificatedocument/', '', 0, 'current'),
			$j++ => array('SATURNE_CERTIFICATEDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/saturne/certificatedocument/', '', 0, 'current'),
			$j++ => array('SATURNE_CERTIFICATEDOCUMENT_DEFAULT_MODEL', 'chaine', 'certificatedocument_odt', '', 0, 'current'),
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

		/* CERTIFICATE PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->trans('ReadCertificate');
		$this->rights[$r][4] = 'certificate';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->transnoentities('CreateCertificate');
		$this->rights[$r][4] = 'certificate';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = $langs->trans('DeleteCertificate');
		$this->rights[$r][4] = 'certificate';
		$this->rights[$r][5] = 'delete';
		$r++;

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

		// Document models
		delDocumentModel('timesheetdocument_odt', 'timesheetdocument');
		delDocumentModel('certificatedocument_odt', 'certificatedocument');
		addDocumentModel('timesheetdocument_odt', 'timesheetdocument', 'ODT templates', 'DOLISIRH_TIMESHEETDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('certificatedocument_odt', 'certificatedocument', 'ODT templates', 'DOLISIRH_CERTIFICATEDOCUMENT_ADDON_ODT_PATH');

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
