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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

require_once __DIR__ . '/medias.lib.php';
require_once __DIR__ . '/pagination.lib.php';
require_once __DIR__ . '/documents.lib.php';
require_once __DIR__ . '/object.lib.php';

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
 * Check if saturne and current module are enabled
 *
 */
function saturne_check_modules_enabled()
{
	global $langs, $moduleNameLowerCase;

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
		$element = $object->element;

		if ($object->element == 'contrat') {
			$element = 'contract';
		} else if ($object->element == 'project_task') {
			$element = 'task';
		}

        $prepareHead = $element . '_prepare_head';
        $head = $prepareHead($object);
    }
	if (property_exists($object, 'picto')) {
		$picto = $object->picto;
	}
    print dol_get_fiche_head($head, $tabactive, $title, -1, $picto, 0, '', '', 0, 'saturne');
}

/**
 * Print dol_banner_tab with Saturne custom enhancements
 *
 * @param  Object $object      Object to show
 * @param  string $paramId     Name of parameter to use to name the id into the URL next/previous link
 * @param  string $moreHtml    More html content to output just before the nav bar
 * @param  int    $showNav     Show Condition (navigation is shown if value is 1)
 * @param  string $fieldId     Field name for select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 * @param  string $fieldRef    Field name objet ref (object->ref) for select next and previous
 * @param  string $moreHtmlRef More html to show after the ref (see $morehtmlleft for before)
 * @param  bool   $handlePhoto Manage photo
 * @return void
 */
function saturne_banner_tab(object $object, string $paramId = 'ref', string $moreHtml = '', int $showNav = 1, string $fieldId = 'ref', string $fieldRef = 'ref', string $moreHtmlRef = '', bool $handlePhoto = false): void
{
    global $db, $langs, $hookmanager, $moduleName, $moduleNameLowerCase;

    if (isModEnabled('project')) {
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    }

    if (empty($moreHtml)) {
        $moreHtml = '<a href="' . dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $object->element . '/' . $object->element . '_list.php', 1) . '?restore_lastsearch_values=1&object_type=' . $object->element . '">' . $langs->trans('BackToList') . '</a>';
    }

    $saturneMoreHtmlRef = '';
    if (array_key_exists('label', $object->fields) && dol_strlen($object->label)) {
        $saturneMoreHtmlRef .= ' - ' . $object->label . '<br>';
    }

    $saturneMoreHtmlRef .= '<div class="refidno">';

    $saturneMoreHtmlRef .= $moreHtmlRef;

    // Banner
    $objectKey      = '';
    $possibleKeys   = [];
    $bannerElements = ['societe', 'project'];
    foreach ($bannerElements as $bannerElement) {
        if (isModEnabled($bannerElement)) {
            if ($bannerElement == 'societe') {
                $possibleKeys = ['socid', 'fk_soc'];
            } elseif ($bannerElement == 'project') {
                $possibleKeys = ['projectid', 'fk_project'];
            }

            foreach ($possibleKeys as $key) {
                if (property_exists($object, $key)) {
                    $objectKey = $key;
                    break;
                }
            }
            if (dol_strlen($objectKey)) {
                $className           = ucfirst($bannerElement);
                $BannerElementObject = new $className($db);
                $constName           = get_class($object) . '::STATUS_LOCKED';
                if (defined($constName) && $object->status < $object::STATUS_LOCKED) {
                    if (GETPOST('action') == 'edit_' . $bannerElement) {
                        $saturneMoreHtmlRef .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . GETPOST('object_type') . '">';
                        $saturneMoreHtmlRef .= '<input type="hidden" name="action" value="set_' . $bannerElement . '">';
                        $saturneMoreHtmlRef .= '<input type="hidden" name="' . $bannerElement . '_key" value="' . $objectKey . '">';
                        $saturneMoreHtmlRef .= '<input type="hidden" name="token" value="' . newToken() . '">';
                        if ($bannerElement == 'societe') {
                            $form = new Form($db);
                            $saturneMoreHtmlRef .= $form->select_company($object->$objectKey, $objectKey, '', 1, 0, 0, [], 0, 'minwidth300');
                        } elseif ($bannerElement == 'project') {
                            $formProject = new FormProjets($db);
                            $saturneMoreHtmlRef .= $formProject->select_projects(-1, $object->$objectKey, $objectKey, 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'minwidth300');
                        }
                        $saturneMoreHtmlRef .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans('Modify') . '">';
                        $saturneMoreHtmlRef .= '</form>';
                    } else {
                        $BannerElementObject->fetch($object->$objectKey);
                        if ($bannerElement == 'societe') {
                            $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1) : img_picto($langs->trans('ThirdParty'), 'company');
                        } elseif ($bannerElement == 'project') {
                            $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1, '', 1) : img_picto($langs->trans('Project'), 'project');
                        }
                        $saturneMoreHtmlRef .= ' <a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=edit_' . $bannerElement . '&id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . GETPOST('object_type') . '&token=' . newToken() . '">' . img_edit($langs->transnoentitiesnoconv($bannerElement == 'societe' ? 'SetThirdParty' : 'SetProject')) . '</a>';
                    }
                } else {
                    $BannerElementObject->fetch($object->$objectKey);
                    if ($bannerElement == 'societe') {
                        $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1) : '';
                    } elseif ($bannerElement == 'project') {
                        $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1, '', 1) : '';
                    }
                }
                $saturneMoreHtmlRef .= '<br>';
            }
        }
    }

    $parameters = [];
    $resHook    = $hookmanager->executeHooks('saturneBannerTab', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($resHook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    } else {
        $saturneMoreHtmlRef .= $hookmanager->resPrint;
    }
    $saturneMoreHtmlRef .= '</div>';

    $moreParams = '&module_name=' . $moduleName . '&object_type=' . $object->element;

    if (!$handlePhoto) {
        dol_banner_tab($object, $paramId, $moreHtml, $showNav, $fieldId, $fieldRef, $saturneMoreHtmlRef, $moreParams);
    } else {
        global $conf, $form;

        print '<div class="arearef heightref valignmiddle centpercent">';
        $moreHtmlLeft = '<div class="floatleft inline-block valignmiddle divphotoref">' . saturne_show_medias_linked($moduleNameLowerCase, $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 88, 88, 0, 0, 0, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1) . '</div>';
        print $form->showrefnav($object, $paramId, $moreHtml, $showNav, $fieldId, $fieldRef, $saturneMoreHtmlRef, $moreParams, 0, $moreHtmlLeft, $object->getLibStatut(6));
        print '</div>';
    }

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

/**
 *  Return HTML select list of a dictionary.
 *
 * @param  string $htmlName        Name of select zone.
 * @param  string $dictionaryTable Dictionary table.
 * @param  string $keyField        Field for key.
 * @param  string $labelField      Label field.
 * @param  string $selected        Selected value.
 * @param  int    $useEmpty        1 = Add an empty value in list, 2 = Add an empty value in list only if there is more than 2 entries.
 * @param  string $moreAttrib      More attributes on HTML select tag.
 * @param  string $placeHolder     Placeholder.
 * @param  string $moreCSS         More css.
 * @return string
 */
function saturne_select_dictionary(string $htmlName, string $dictionaryTable, string $keyField = 'code', string $labelField = 'label', string $selected = '', int $useEmpty = 0, string $moreAttrib = '', string $placeHolder = '', string $moreCSS = 'minwidth150'): string
{
	global $langs, $db;

	$langs->load('admin');

    $out = '';
	$sql = 'SELECT rowid, ' . $keyField . ', ' . $labelField;
	$sql .= ' FROM ' . MAIN_DB_PREFIX . $dictionaryTable;
    $sql .= $db->order('position', 'ASC');

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i   = 0;
		if ($num) {
			$out = '<select id="select' . $htmlName . '" class="flat selectdictionary' . ($moreCSS ? ' ' . $moreCSS : '') . '" name="' . $htmlName . '"' . ($moreAttrib ? ' ' . $moreAttrib : '') . '>';
			if ($useEmpty == 1 || ($useEmpty == 2 && $num > 1)) {
				$out .= '<option value="-1">'. (dol_strlen($placeHolder) > 0 ? $langs->transnoentities($placeHolder) : '') .'</option>';
			}

			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if ($selected == $obj->rowid || $selected == $obj->$keyField) {
					$out .= '<option value="' . $obj->$keyField . '" selected>';
				} else {
					$out .= '<option value="' . $obj->$keyField . '">';
				}
				$out .= $langs->transnoentities($obj->$labelField);
				$out .= '</option>';
				$i++;
			}
			$out .= '</select>';
			$out .= ajax_combobox('select' . $htmlName);

		} else {
			$out = $langs->trans('DictionaryEmpty');
		}
	} else {
		dol_print_error($db);
	}
	return $out;
}

/**
 * Load dictionary from database.
 *
 * @param  string    $tableName SQL table name.
 * @param  string    $sortOrder Sort Order.
 * @param  string    $sortField Sort field.
 * @return array|int            0< if KO, >0 if OK.
 */
function saturne_fetch_dictionary(string $tableName, string $sortOrder = 'ASC', string $sortField = 't.position')
{
	global $db;

	$sql  = 'SELECT t.rowid, t.entity, t.ref, t.label, t.description, t.active, t.position';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . $tableName . ' as t';
	$sql .= ' WHERE 1 = 1';
	$sql .= ' AND entity IN (0, ' . getEntity($tableName) . ')';

    if (!empty($sortField)) {
        $sql .= $db->order($sortField, $sortOrder);
    }

	$resql = $db->query($sql);
	if ($resql) {
		$num     = $db->num_rows($resql);
		$i       = 0;
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
            $record->position    = $obj->position;

			$records[$record->id] = $record;

			$i++;
		}

		$db->free($resql);

		return $records;
	} else {
		return -1;
	}
}
