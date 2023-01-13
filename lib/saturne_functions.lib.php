<?php

require_once __DIR__ . '/medias.php';
require_once __DIR__ . '/pagination.php';
require_once __DIR__ . '/documents.php';

/**
 *      Print llxHeader with Saturne custom enhancements
 *
 *      @param      string				$module
 *      @param      string				$action
 *      @param      string				$subaction
 *      @param      integer				$load_media_gallery
 *      @param      string				$head
 *      @param      string				$title
 *      @param      string				$help_url
 *      @param      string				$target
 *      @param      integer				$disablejs
 *      @param      integer				$disablehead
 * 		@param		array				$arrayofjs
 * 		@param		array				$arrayofcss
 * 		@param		string				$morequerystring
 * 		@param		string				$morecssonbody
 * 		@param		string				$replacemainareaby
 * 		@param		integer				$disablenofollow
 * 		@param		integer				$disablenoindex
 */
function saturneHeader($module, $action, $subaction, $load_media_gallery, $head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0) {

	global $langs, $conf, $db;

	//CSS
	$arrayofcss[] = '/saturne/css/saturne.css';

	//JS
	$arrayofjs[]  = '/saturne/js/saturne.js.php';

	llxHeader($head, $title, $help_url, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody, $replacemainareaby, $disablenofollow, $disablenoindex);

	if ($load_media_gallery) {
		//Media gallery
		include __DIR__ . '/../core/tpl/medias_gallery_modal.tpl.php';
	}
}

/**
 *      Show pages based on loaded pages array
 *
 *      @param      integer				$pagesCounter
 *      @param      array				$page_array
 *      @param      integer				$offset
 *      @return     string				Pages html content
 *
 */
function saturne_check_access($module, $object, $permission) {

	global $conf, $langs, $user;

	if (!$permission)     accessforbidden();
	if ($user->socid > 0) accessforbidden();

	$langs->loadLangs(['saturne@saturne']);

	if ($conf->multicompany->enabled) {
		if ($conf->$module->enabled) {
			if ($object->id > 0) {
				if ($object->entity != $conf->entity) {
					setEventMessage($langs->trans('ChangeEntityRedirection'), 'warnings');
					$urltogo = dol_buildpath('/custom/' . $module . '/' . $module . 'index.php?mainmenu=' . $module, 1);
					header("Location: " . $urltogo);
					exit;
				}
			}
		} else {
			setEventMessage($langs->trans('EnableModule', ucfirst($module)), 'warnings');
			$urltogo = dol_buildpath('/admin/modules.php?search_nature=external_Evarisk', 1);
			header("Location: " . $urltogo);
			exit;
		}
	}
}

