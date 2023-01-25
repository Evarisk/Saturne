<?php

require_once __DIR__ . '/medias.php';
require_once __DIR__ . '/pagination.php';
require_once __DIR__ . '/documents.php';

/**
 *      Print llxHeader with Saturne custom enhancements
 *
 *      @param      string				$module					Module name
 *      @param      string				$action					Post action
 *      @param      string				$subaction				Post sub action
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
function saturne_header($module, $action, $subaction, $load_media_gallery = 0, $head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0) {

	global $langs, $conf, $db;

	//CSS
	if (is_array($arrayofcss)) {
		$arrayofcss[] = '/saturne/css/saturne.css';
	} else {
		$arrayofcss = '/saturne/css/saturne.css';
	}

	//JS
	if (is_array($arrayofjs)) {
		$arrayofjs[]  = '/saturne/js/saturne.js.php';
	} else {
		$arrayofjs  = '/saturne/js/saturne.js.php';
	}

	//Langs
	$langs->load('saturne@saturne');

	llxHeader($head, $title, $help_url, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody, $replacemainareaby, $disablenofollow, $disablenoindex);

	if ($load_media_gallery) {
		//Media gallery
		include __DIR__ . '/../core/tpl/medias_gallery_modal.tpl.php';
	}
}

/**
 *      Show pages based on loaded pages array
 *
 *      @param      integer				$module			Module name
 *      @param      array				$object			Object in current page
 *      @param      integer				$permission		Permission to access to current page
 *      @return     string				Pages html content
 *
 */
function saturne_check_access($module, $object, $permission) {

	global $conf, $langs, $user;

	if (!$permission)     accessforbidden();
	if ($user->socid > 0) accessforbidden();

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

