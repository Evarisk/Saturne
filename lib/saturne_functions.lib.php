<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/saturne_functions.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne
 */

require_once __DIR__ . '/medias.lib.php';
require_once __DIR__ . '/pagination.lib.php';
require_once __DIR__ . '/documents.lib.php';

/**
 * Print llxHeader with Saturne custom enhancements
 *
 * @param int    $load_media_gallery Load media gallery on page
 * @param string $head               Show header
 * @param string $title              Page title
 * @param string $help_url           Help url shown in "?" tooltip
 * @param string $target       	     Target to use on links
 * @param int    $disablejs          More content into html header
 * @param int    $disablehead        More content into html header
 * @param array  $arrayofjs          Array of complementary js files
 * @param array  $arrayofcss         Array of complementary css files
 * @param string $morequerystring    Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param string $morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * @param string $replacemainareaby  Replace call to main_area() by a print of this string
 * @param int    $disablenofollow    Disable the "nofollow" on meta robot header
 * @param int    $disablenoindex     Disable the "noindex" on meta robot header
 */
function saturne_header(int $load_media_gallery = 0, string $head = '', string $title = '', string $help_url = '', string $target = '', int $disablejs = 0, int $disablehead = 0, array $arrayofjs = [], array $arrayofcss = [], string $morequerystring = '', string $morecssonbody = '', string $replacemainareaby = '', int $disablenofollow = 0, int $disablenoindex = 0)
{
	global $moduleNameLowerCase;

	//CSS
	$arrayofcss[] = '/saturne/css/saturne.min.css';
    if (file_exists(__DIR__ . '/../../' . $moduleNameLowerCase . '/css/' . $moduleNameLowerCase . '.min.css')) {
        $arrayofcss[] = '/' . $moduleNameLowerCase . '/css/' . $moduleNameLowerCase . '.min.css';
    }

	//JS
	$arrayofjs[] = '/saturne/js/saturne.min.js';
    if (file_exists(__DIR__ . '/../../' . $moduleNameLowerCase . '/js/' . $moduleNameLowerCase . '.min.js')) {
        $arrayofjs[] = '/' . $moduleNameLowerCase . '/js/' . $moduleNameLowerCase . '.min.js';
    }

	llxHeader($head, $title, $help_url, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody, $replacemainareaby, $disablenofollow, $disablenoindex);

	if ($load_media_gallery) {
		//Media gallery
		include __DIR__ . '/../core/tpl/medias/medias_gallery_modal.tpl.php';
	}
}

/**
 * Check user access on current page
 *
 * @param object|bool $permission        Permission to access to current page
 * @param object|null $object            Object in current page
 * @param bool        $allowExternalUser Allow external user to have access at current page
 */
function saturne_check_access($permission, object $object = null, bool $allowExternalUser = false)
{
    global $conf, $langs, $user, $moduleNameLowerCase;

    if (empty($moduleNameLowerCase)) {
        $moduleNameLowerCase = 'saturne';
    }

    if (!isModEnabled($moduleNameLowerCase) || !isModEnabled('saturne')) {
        if (!isModEnabled($moduleNameLowerCase)) {
            setEventMessage($langs->transnoentitiesnoconv('Enable' . ucfirst($moduleNameLowerCase)), 'warnings');
        }
        if (!isModEnabled('saturne')) {
            setEventMessage($langs->trans('EnableSaturne'), 'warnings');
        }
        $urltogo = dol_buildpath('/admin/modules.php?search_nature=external_Evarisk', 1);
        header('Location: ' . $urltogo);
        exit;
    }

    if (!$permission) {
        accessforbidden();
    }

    if (!$allowExternalUser) {
        if ($user->socid > 0) {
            accessforbidden();
        }
    }

	if (isModEnabled('multicompany')) {
		if ($object->id > 0) {
			if ($object->entity != $conf->entity) {
				setEventMessage($langs->trans('ChangeEntityRedirection'), 'warnings');
				$urltogo = dol_buildpath('/custom/' . $moduleNameLowerCase . '/' . $moduleNameLowerCase . 'index.php?mainmenu=' . $moduleNameLowerCase, 1);
				header('Location: ' . $urltogo);
				exit;
			}
		}
	}
}

/**
 * Print dol_get_fiche_head with Saturne custom enhancements
 *
 * @param CommonObject $object    Current object
 * @param string       $tabactive Tab active in navbar
 * @param string       $title     Title navbar
 */
function saturne_get_fiche_head(CommonObject $object, string $tabactive = '', string $title = '')
{
    // Configuration header
    if (property_exists($object, 'element')) {
        $prepareHead = $object->element . '_prepare_head';
        $head = $prepareHead($object);
    }
    if (property_exists($object, 'picto')) {
        $picto = $object->picto;
    }
    print dol_get_fiche_head($head, $tabactive, $title, -1, $picto);
}

/**
 * Print dol_banner_tab with Saturne custom enhancements
 *
 *  @param  Object $object      Object to show
 *  @param  string $paramid     Name of parameter to use to name the id into the URL next/previous link
 *  @param  string $morehtml    More html content to output just before the nav bar
 *  @param  int    $shownav     Show Condition (navigation is shown if value is 1)
 *  @param  string $fieldid     Field name for select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 *  @param  string $fieldref    Field name objet ref (object->ref) for select next and previous
 *  @param  string $morehtmlref More html to show after the ref (see $morehtmlleft for before)
 *  @return void
 */
function saturne_banner_tab(object $object, string $paramid = 'ref', string $morehtml = '', int $shownav = 1, string $fieldid = 'ref', string $fieldref = 'ref', string $morehtmlref = ''): void
{
    global $db, $langs, $hookmanager, $moduleName, $moduleNameLowerCase;

    if (isModEnabled('project')) {
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    }

    if (empty($morehtml)) {
        $morehtml = '<a href="' . dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $object->element . '/' . $object->element . '_list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->element . '">' . $langs->trans('BackToList') . '</a>';
    }

	$saturneMorehtmlref = '';
	if (array_key_exists('label', $object->fields)) {
		$saturneMorehtmlref .= ' - ' . $object->label . '<br>';
	}

    $saturneMorehtmlref .= '<div class="refidno">';

	$saturneMorehtmlref .= $morehtmlref;
    // Thirdparty
    if (isModEnabled('societe') && array_key_exists('fk_soc', $object->fields)) {
        if (!empty($object->fk_soc)) {
            $object->fetch_thirdparty();
            $saturneMorehtmlref .= $langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '') . '<br>';
        } else {
            $saturneMorehtmlref .= $langs->trans('ThirdParty') . ' : ' . '<br>';
        }
    }

    // Project
    if (isModEnabled('project')) {
        if (array_key_exists('fk_project', $object->fields)) {
            $key = 'fk_project';
        } elseif (array_key_exists('projectid', $object->fields)) {
            $key = 'projectid';
        }
        if (!empty($object->$key)) {
            $project = new Project($db);
            $project->fetch($object->$key);
            $saturneMorehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1) . '<br>';
        } else {
            $saturneMorehtmlref .= $langs->trans('Project') . ' : ' . '<br>';
        }
    }

    $parameters = [];
    $reshook = $hookmanager->executeHooks('saturneBannerTab', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    } else {
        $saturneMorehtmlref .= $hookmanager->resPrint;
    }
    $saturneMorehtmlref .= '</div>';

    $moreparam = '&module_name=' . $moduleName . '&object_type=' . $object->element;

    dol_banner_tab($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $saturneMorehtmlref, $moreparam);

    print '<div class="underbanner clearboth"></div>';
}

/**
 *  Load saturne and module translation files.
 *
 * @param array $domains Array of lang files to load
 */
function saturne_load_langs(array $domains = [])
{
	global $langs, $moduleNameLowerCase;

	$langs->loadLangs(['saturne@saturne', 'object@saturne', 'signature@saturne', 'medias@saturne', $moduleNameLowerCase . '@' . $moduleNameLowerCase]);

	if (!empty($domains)) {
		foreach ($domains as $domain) {
			$langs->load($domain);
		}
	}
}

// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 *  Return a HTML select list of a dictionary
 *
 *  @param  string	$htmlname          	Name of select zone
 *  @param	string	$dictionarytable	Dictionary table
 *  @param	string	$keyfield			Field for key
 *  @param	string	$labelfield			Label field
 *  @param	string	$selected			Selected value
 *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
 *  @param  string  $moreattrib         More attributes on HTML select tag
 * 	@return	void
 */
function saturne_select_dictionary($htmlname, $dictionarytable, $keyfield = 'code', $labelfield = 'label', $selected = '', $showLabel = 0, $useempty = 0, $moreattrib = '', $placeholder = '', $morecss = '')
{
	// phpcs:enable
	global $langs, $db;

	$langs->load("admin");

	$out = '';
	$sql  = "SELECT rowid, " . $keyfield . ", " . $labelfield;
	$sql .= " FROM " . MAIN_DB_PREFIX . $dictionarytable;
	$sql .= " ORDER BY " . $labelfield;

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i   = 0;
		if ($num) {
			$out .= '<select id="select' . $htmlname . '" class="flat selectdictionary' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '"' . ($moreattrib ? ' ' . $moreattrib : '') . '>';
			if ($useempty == 1 || ($useempty == 2 && $num > 1)) {
				$out .= '<option value="-1">'. (dol_strlen($placeholder) > 0 ? $langs->transnoentities($placeholder) : '') .'&nbsp;</option>';
			}

			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if ($selected == $obj->rowid || $selected == $langs->transnoentities($obj->$keyfield)) {
					$out .= '<option value="' . $langs->transnoentities($obj->$keyfield) . '" selected>';
				} else {
					$out .= '<option value="' . $langs->transnoentities($obj->$keyfield) . '">';
				}
				$out .= $langs->transnoentities($obj->$keyfield) . (($showLabel > 0) ? ' - ' .  $langs->transnoentities($obj->$labelfield) : '');
				$out .= '</option>';
				$i++;
			}
			$out .= "</select>";
			$out .= ajax_combobox('select'.$htmlname);

		} else {
			$out .= $langs->trans("DictionaryEmpty");
		}
	} else {
		dol_print_error($db);
	}
	return $out;
}

/**
 *  Load dictionnary from database
 *
 * @param  string    $tablename SQL table name
 * @return array|int            0< if KO, >0 if OK
 */
function saturne_fetch_dictionary(string $tablename)
{
	global $db;

	$sql  = 'SELECT t.rowid, t.entity, t.ref, t.label, t.description, t.active';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . $tablename . ' as t';
	$sql .= ' WHERE 1 = 1';
	$sql .= ' AND entity IN (0, ' . getEntity($tablename) . ')';

	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$records = [];
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$record = new stdClass();

			$record->id          = $obj->rowid;
			$record->entity      = $obj->entity;
			$record->ref         = $obj->ref;
			$record->label       = $obj->label;
			$record->description = $obj->description;
			$record->active      = $obj->active;

			$records[$record->id] = $record;

			$i++;
		}

		$db->free($resql);

		return $records;
	} else {
		return -1;
	}
}
