<?php

/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

require_once __DIR__ . '/medias.lib.php';
require_once __DIR__ . '/pagination.lib.php';
require_once __DIR__ . '/documents.lib.php';

/**
 *      Print llxHeader with Saturne custom enhancements
 *
 *      @param      integer				$load_media_gallery		Load media gallery on page
 *      @param      string				$head					Show header
 *      @param      string				$title					Page title
 *      @param      string				$help_url				Help url shown in "?" tooltip
 *      @param      string				$target					Target to use on links
 *      @param      integer				$disablejs				More content into html header
 *      @param      integer				$disablehead			More content into html header
 * 		@param		array				$arrayofjs				Array of complementary js files
 * 		@param		array				$arrayofcss				Array of complementary css files
 * 		@param		string				$morequerystring		Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * 		@param		string				$morecssonbody			More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * 		@param		string				$replacemainareaby		Replace call to main_area() by a print of this string
 * 		@param		integer				$disablenofollow		Disable the "nofollow" on meta robot header
 * 		@param		integer				$disablenoindex			Disable the "noindex" on meta robot header
 */
function saturne_header($load_media_gallery = 0, $head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0) {

	global $langs, $moduleNameLowerCase;

	//CSS
	if (is_array($arrayofcss)) {
		$arrayofcss[] = '/saturne/css/saturne.css';
	} else {
		$arrayofcss = '/saturne/css/saturne.css';
	}
	$arrayofcss[] = '/' . $moduleNameLowerCase . '/css/' . $moduleNameLowerCase . '.css';

	//JS
	if (is_array($arrayofjs)) {
		$arrayofjs[]  = '/saturne/js/saturne.min.js';
	} else {
		$arrayofjs  = '/saturne/js/saturne.min.js';
	}
	$arrayofjs[] = '/' . $moduleNameLowerCase . '/js/' . $moduleNameLowerCase . '.min.js';

	//Langs
	$langs->loadLangs(['saturne@saturne', $moduleNameLowerCase.'@'.$moduleNameLowerCase]);

	llxHeader($head, $title, $help_url, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody, $replacemainareaby, $disablenofollow, $disablenoindex);

	if ($load_media_gallery) {
		//Media gallery
		include __DIR__ . '/../core/tpl/medias/medias_gallery_modal.tpl.php';
	}
}

/**
 *      Show pages based on loaded pages array
 *
 *      @param      integer				$moduleName			Module name
 *      @param      array				$object			Object in current page
 *      @param      integer				$permission		Permission to access to current page
 *      @return     string				Pages html content
 *
 */
function saturne_check_access($moduleName, $object, $permission) {

	global $conf, $langs, $user;

	if (!isModEnabled($moduleName) || !isModEnabled('saturne')) {
		if (!isModEnabled($moduleName)){
			setEventMessage($langs->transnoentitiesnoconv('Enable' . ucfirst($moduleName)), 'warnings');
		}
		if (!isModEnabled('saturne')) {
			setEventMessage($langs->trans('EnableSaturne'), 'warnings');
		}
		$urltogo = dol_buildpath('/admin/modules.php?search_nature=external_Evarisk', 1);
		header("Location: " . $urltogo);
		exit;
	}

	if (!$permission){
		accessforbidden();
	}

	if ($user->socid > 0){
		accessforbidden();
	}

	if ($conf->multicompany->enabled) {
		if ($object->id > 0) {
			if ($object->entity != $conf->entity) {
				setEventMessage($langs->trans('ChangeEntityRedirection'), 'warnings');
				$urltogo = dol_buildpath('/custom/' . $moduleName . '/' . $moduleName . 'index.php?mainmenu=' . $moduleName, 1);
				header("Location: " . $urltogo);
				exit;
			}
		}
	}
}

/**
 * Print dol_banner_tab with Saturne custom enhancements
 *
 * @param CommonObject $object      Current object
 * @param string       $tabactive   Tab active in navbar
 * @param string       $title       Title navbar
 * @param int          $shownav     Show Condition (navigation is shown if value is 1)
 * @param string       $fieldid     Field name for select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 * @param string       $fieldref    Field name objet ref (object->ref) for select next and previous
 */
function saturne_banner_tab(CommonObject $object, string $tabactive, string $title, string $paramid = 'ref', int $shownav = 1, string $fieldid = 'ref', string $fieldref = 'ref')
{
    global $db, $langs, $hookmanager, $moduleName, $moduleNameLowerCase, $objectParentType, $objectType;

    if (isModEnabled('project')) {
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    }

    // Configuration header
    $prepareHead = $objectParentType . 'PrepareHead';
    $head = $prepareHead($object);
    print dol_get_fiche_head($head, $tabactive, $title, -1, $object->picto);

    $linkback = '<a href="' . dol_buildpath('/' . $moduleNameLowerCase . '/view/' . ($objectParentType != $objectType ? $objectParentType : $objectType) . '/' . ($objectParentType != $objectType ? $objectParentType : $objectType) . '_list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->type . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = '<div class="refidno">';
    // Thirdparty
    if (isModEnabled('societe')) {
        if (!empty($object->fk_soc)) {
            $object->fetch_thirdparty();
            $morehtmlref .= $langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '') . '<br>';
        } else {
            $morehtmlref .= $langs->trans('ThirdParty') . ' : ' . '<br>';
        }
    }

    // Project
    if (isModEnabled('project')) {
        if (!empty($object->fk_project)) {
            $project = new Project($db);
            $project->fetch($object->fk_project);
            $morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1) . '<br>';
        } else {
            $morehtmlref .= $langs->trans('Project') . ' : ' . '<br>';
        }
    }

    $parameters = [];
    $reshook = $hookmanager->executeHooks('saturneBannerTab', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    } else {
        $morehtmlref .= $hookmanager->resPrint;
    }
    $morehtmlref .= '</div>';

    $moreparam = '&module_name=' . $moduleName . '&object_parent_type=' . $objectParentType . '&object_type=' . $object->type;

    dol_banner_tab($object, $paramid, $linkback, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam);

    print '<div class="underbanner clearboth"></div>';
}
