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
require_once __DIR__ . '/debug.lib.php';

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
    if ($load_media_gallery) {
        $arrayofjs[] = '/saturne/js/includes/signature-pad.min.js';
    }
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
    global $conf;

    // Configuration header
    if (property_exists($object, 'element')) {
		$element = $object->element;

		if ($object->element == 'contrat') {
			$element = 'contract';
		} else if ($object->element == 'project_task') {
            $element = 'task';
        }

        $prepareHead = $element . '_prepare_head';
        if (function_exists($prepareHead)) {
            $head = $prepareHead($object);
        } else {
            $prepareHead = $element . 'PrepareHead';
            $head = $prepareHead($object);
        }
    }
	if (property_exists($object, 'picto')) {
		$picto = $object->picto;
	}
    if ($conf->browser->layout == 'phone') {
        $conf->dol_optimize_smallscreen = 0;
    }

    print dol_get_fiche_head($head, $tabactive, $title, -1, $picto, 0, '', '', $conf->browser->layout != 'phone' ? 0 : 5, 'saturne');
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
 * @param  array  $moreParams  More params
 * @return void
 */
function saturne_banner_tab(object $object, string $paramId = 'ref', string $moreHtml = '', int $showNav = 1, string $fieldId = 'ref', string $fieldRef = 'ref', string $moreHtmlRef = '', bool $handlePhoto = false, array $moreParams = []): void
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

    $parameters = [];
    $resHook    = $hookmanager->executeHooks('saturneBannerTab', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($resHook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    } else {
        if (!empty($hookmanager->resArray)) {
            list($customMoreHtmlRef, $moreParams) = $hookmanager->resArray;
        } else if (!empty($hookmanager->resPrint)) {
            $customMoreHtmlRef = $hookmanager->resPrint;
        }

        $saturneMoreHtmlRef .= $customMoreHtmlRef;
    }

    // Banner
    $bannerElements = ['societe', 'project'];
    if (!empty($moreParams['bannerElement'])) {
        $bannerElements[] = $moreParams['bannerElement'];
    }
    foreach ($bannerElements as $bannerElement) {
        $objectKey    = '';
        $possibleKeys = [];
        if (isModEnabled($bannerElement)) {
            if ($bannerElement == 'societe') {
                $possibleKeys = ['socid', 'fk_soc'];
            } elseif ($bannerElement == 'project') {
                $possibleKeys = ['projectid', 'fk_project'];
            } elseif ($bannerElement == $moreParams['bannerElement']) {
                $possibleKeys = $moreParams['possibleKeys'];
            }

            foreach ($possibleKeys as $key) {
                if (isset($object->$key) || isset($object->fields[$key])) {
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
                            $saturneMoreHtmlRef .= img_picto($langs->trans('ThirdParty'), 'company', 'class="pictofixedwidth"') . $form->select_company($object->$objectKey, $objectKey, '', 1, 0, 0, [], 0, 'maxwidth500 widthcentpercentminusx');
                        } elseif ($bannerElement == 'project') {
                            $formProject = new FormProjets($db);
                            $saturneMoreHtmlRef .= img_picto($langs->trans('Project'), 'project', 'class="pictofixedwidth"') . $formProject->select_projects(-1, $object->$objectKey, $objectKey, 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusx');
                        } elseif ($bannerElement == $moreParams['bannerElement']) {
                            $form = new Form($db);
                            $objectLists = saturne_fetch_all_object_type($moreParams['className']);
                            if (is_array($objectLists) && !empty($objectLists)) {
                                $objectListArray = [];
                                foreach ($objectLists as $objectKeyList => $objectList) {
                                    $objectListArray[$objectKeyList] = $objectList->ref;
                                }
                                $saturneMoreHtmlRef .= img_picto($langs->trans($moreParams['title']), $moreParams['picto'], 'class="pictofixedwidth"') . $form::selectarray($objectKey, $objectListArray, $object->$objectKey, 1, 0, '', 1, 0, 0, '', 'maxwidth500 widthcentpercentminusx');
                            }
                        }
                        $saturneMoreHtmlRef .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans('Modify') . '">';
                        $saturneMoreHtmlRef .= '</form>';
                    } else {
                        $BannerElementObject->fetch($object->$objectKey);
                        if ($bannerElement == 'societe') {
                            $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1) : img_picto($langs->trans('ThirdParty'), 'company');
                        } elseif ($bannerElement == 'project') {
                            $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1, '', 1) : img_picto($langs->trans('Project'), 'project');
                        } elseif ($bannerElement == $moreParams['bannerElement']) {
                            $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1) : img_picto($langs->trans($moreParams['title']), $moreParams['picto']);
                        }
                        if(empty($moreParams[$bannerElement]['disable_edit'])) {
                            $saturneMoreHtmlRef .= ' <a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=edit_' . $bannerElement . '&id=' . $object->id . '&module_name=' . $moduleName . '&object_type=' . GETPOST('object_type') . '&token=' . newToken() . '">' . img_edit($langs->transnoentitiesnoconv($bannerElement == 'societe' ? 'SetThirdParty' : 'Set' . ucfirst($bannerElement))) . '</a>';
                        }
                    }
                } else {
                    $BannerElementObject->fetch($object->$objectKey);
                    if ($bannerElement == 'societe' || $bannerElement == $moreParams['bannerElement']) {
                        $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1) : '';
                    } elseif ($bannerElement == 'project') {
                        $saturneMoreHtmlRef .= $object->$objectKey > 0 ? $BannerElementObject->getNomUrl(1, '', 1) : '';
                    }
                }
                $saturneMoreHtmlRef .= '<br>';
            }
        }
    }
    $saturneMoreHtmlRef .= '</div>';

    $moreParamsBannerTab = (!empty($moreParams['bannerTab']) ? $moreParams['bannerTab'] : '');

    if (!$handlePhoto) {
        $moreParamsBannerTab = (empty($moreParamsBannerTab) ? '&module_name=' . $moduleName . '&object_type=' . $object->element : $moreParamsBannerTab);
        dol_banner_tab($object, $paramId, (($moreHtml != 'none' && $moreParams['moreHtml'] != 'none') ? $moreHtml : ''), $showNav, $fieldId, $fieldRef, $saturneMoreHtmlRef, $moreParamsBannerTab);
    } else {
        global $conf, $form;

        print '<div class="arearef heightref valignmiddle centpercent">';

        $modulePart = '';
        $baseDir    = $conf->$moduleNameLowerCase->multidir_output[$conf->entity];
        $subDir     = $object->element . '/'. $object->ref . '/photos/';

        $resHook = $hookmanager->executeHooks('saturneBannerTabCustomSubdir', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($resHook > 0) {
            if (!empty($hookmanager->resArray)) {
                if ($hookmanager->resArray['modulepart']) {
                    $modulePart = $hookmanager->resArray['modulepart'];
                }
                if ($hookmanager->resArray['dir']) {
                    $baseDir = $hookmanager->resArray['dir'];
                }
                if ($hookmanager->resArray['subdir']) {
                    $subDir = $hookmanager->resArray['subdir'];
                }
                if ($hookmanager->resArray['photoLimit']) {
                    $photoLimit = $hookmanager->resArray['photoLimit'];
                }
            }
        }

        $moreHtmlLeft = '<div class="floatleft inline-block valignmiddle divphotoref">' . saturne_show_medias_linked((dol_strlen($modulePart) > 0 ? $modulePart : $moduleNameLowerCase), $baseDir . '/' . $subDir, 'small', $photoLimit ?? 0, 0, 0, 0, 88, 88, 0, 0, 0, $subDir, $object, 'photo', 0, 0,0, 1) . '</div>';
        print $form->showrefnav($object, $paramId, (($moreHtml != 'none' && $moreParams['moreHtml'] != 'none') ? $moreHtml : ''), $showNav, $fieldId, $fieldRef, $saturneMoreHtmlRef, $moreParamsBannerTab, 0, $moreHtmlLeft, $object->getLibStatut(6));
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

/**
 * Show category image
 *
 * @param  Categorie   $category Category object
 * @param  int         $noPrint  0 = Print option, 1 = output in string
 * @param  string      $moreCSS  More css
 * @return string|void
 */
function saturne_show_category_image(Categorie $category, int $noPrint = 0, string $moreCSS = '')
{
    global $conf, $langs;

    $out       = '';
    $maxWidth  = 50;
    $maxHeight = 50;

    $categoryPhotoDir = get_exdir($category->id, 2, 0, 0, $category, 'category') . $category->id . '/photos/';
    $dir              = $conf->categorie->multidir_output[$category->entity ?? 1] . '/' . $categoryPhotoDir;

    $photos = $category->liste_photos($dir);
    if (is_array($photos) && count($photos)) {
        foreach ($photos as $photo) {
            if ($photo['photo_vignette']) {
                $filename = $photo['photo_vignette'];
            } else {
                $filename = $photo['photo'];
            }

            // Image size
            $category->get_image_size($dir . $filename);
            $imgWidth  = ($category->imgWidth < $maxWidth) ? $category->imgWidth : $maxWidth;
            $imgHeight = ($category->imgHeight < $maxHeight) ? $category->imgHeight : $maxHeight;

            if ($noPrint) {
                $out = '<div><img width="' . $imgWidth . '" height="' . $imgHeight . '" class="photo ' . $moreCSS . '" src="' . DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=category&entity=' . $category->entity . '&file=' . urlencode($categoryPhotoDir . $filename) . '" value="' . $category->id . '" title="' . $filename . '" alt=""></div>';
            } else {
                print '<div><img width="' . $imgWidth . '" height="' . $imgHeight . '" class="photo ' . $moreCSS . '" src="' . DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=category&entity=' . $category->entity . '&file=' . urlencode($categoryPhotoDir . $filename) . '" value="' . $category->id . '" title="' . $filename . '" alt=""></div>';
            }
        }
    } else {
        print '<div><img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo ' . $moreCSS . '" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . $langs->trans('NoPhotoYet') . '" value="' . $category->id . '" alt=""></div>';
    }

    if ($noPrint) {
        return $out;
    }
}

/**
 * Create category
 *
 * @param  string $label       Label
 * @param  string $type        Type
 * @param  int    $fkParent    FkParent
 * @param  string $photoName   Photo name
 * @param  string $color       Color
 * @param  string $description Description
 * @param  int    $visible     Visible
 * @return int                 0 < if KO, category ID if OK
 */
function saturne_create_category(string $label = '', string $type = '', int $fkParent = 0, string $photoName = '', string $color = '', string $description = '', int $visible = 1): int
{
    global $conf, $db, $moduleNameLowerCase, $user;
    global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    $category = new Categorie($db);

    $category->label       = $label;
    $category->type        = $type;
    $category->fk_parent   = $fkParent;
    $category->color       = $color;
    $category->description = $description;
    $category->visible     = $visible;

    $result = $category->create($user);

    if ($result < 0) {
        return -1;
    }

    if (dol_strlen($photoName) > 0) {
        $uploadDir = $conf->categorie->multidir_output[$conf->entity ?: 1];
        $dir       = $uploadDir . '/' . get_exdir($result, 2, 0, 0, $category, 'category') . $result . '/photos/';
        if (!is_dir($dir)) {
            dol_mkdir($dir);
        }

        $originFile = __DIR__ . '/../../' . $moduleNameLowerCase . '/img/pictos_' . $type . '/' . $photoName;
        dol_copy($originFile, $dir . $photoName);
        vignette($dir . $photoName, $maxwidthsmall, $maxheightsmall);
        vignette($dir . $photoName, $maxwidthmini, $maxheightmini, '_mini');
    }

    return $result;
}

/**
 * Show notice
 *
 * @param  string   $title        Title
 * @param  string   $message      Message
 * @param  string   $type         Type of the notice
 * @param  string   $id           HTML Id for the notice
 * @param  bool     $visible      Visibility of the notice
 * @param  bool     $closeButton  Button to close
 * @param  string   $moreCss      More css
 * @param  string[] $translations Array of translations to manipulate notice with JS
 * @param  string[] $moreAttr     More html attributes
 *
 * @return string
 */
function saturne_show_notice(string $title = '', string $message = '', string $type = 'error', string $id = 'notice-infos', bool $visible = false, bool $closeButton = true, string $moreCss = '', array $translations = [], array $moreAttr = []): string
{
    $out = '<div class="wpeo-notice notice-' . $type;
    if (!$visible) {
        $out .= ' hidden';
    }
    $out .= ' ' . $moreCss . '"';
    $out .= ' id="' . $id . '"';
    foreach ($moreAttr as $attr => $value) {
        $out .= ' ' . $attr . '="' . $value . '"';
    }
    $out .= '>';

    foreach ($translations as $name => $translation) {
        $out .= '<input type="hidden" name="' . $name . '" value="' . $translation . '">';
    }

    $out .= '<div class="notice-content">';
    $out .= '<div class="notice-title">' . $title . '</div>';
    $out .= '<div class="notice-message">' . $message . '</div>';
    $out .= '</div>';

    if ($closeButton) {
        $out .= '<div class="notice-close"><i class="fas fa-times"></i></div>';
    }
    $out .= '</div>';

    return $out;
}

/**
 * Manage extra fields for add and update
 *
 * @param  array     $extraFieldsArrays      Array of extra fields
 * @param  array     $commonExtraFieldsValue Array of common extra fields value
 * @throws Exception
 */
function saturne_manage_extrafields(array $extraFieldsArrays, array $commonExtraFieldsValue = []): void
{
    global $db, $langs;

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    $extraFields = new ExtraFields($db);

    foreach ($extraFieldsArrays as $key => $extraField) {
        if (!isset($extraField['elementtype']) || !is_array($extraField['elementtype'])) {
            throw new Exception($langs->transnoentities('ExtrafieldsFieldMissing', 'elementtype', $key));
        }

        foreach ($extraField['elementtype'] as $extraFieldElementType) {
            // Add ExtraField
            $result = $extraFields->addExtraField(
                $key, $extraField['Label'], $extraField['type'], $extraField['position'],
                $extraField['length']   ?? '', $extraFieldElementType,
                $extraField['unique']   ?? $commonExtraFieldsValue['unique']   ?? 0,
                $extraField['required'] ?? $commonExtraFieldsValue['required'] ?? 0,
                $extraField['default']  ?? $commonExtraFieldsValue['default']  ?? '',
                $extraField['params'] ? ['options' => $extraField['params']] : '',
                $extraField['alwayseditable'] ?? $commonExtraFieldsValue['alwayseditable'] ?? 0,
                $extraField['perms']          ?? $commonExtraFieldsValue['perms']          ?? '',
                $extraField['list']           ?? $commonExtraFieldsValue['list']           ?? '',
                $extraField['help'][$extraFieldElementType] ?? $extraField['help'] ?? $commonExtraFieldsValue['help'] ?? '',
                $extraField['computed']    ?? $commonExtraFieldsValue['computed']    ?? '',
                $extraField['entity']      ?? $commonExtraFieldsValue['entity']      ?? '',
                $extraField['langfile']    ?? $commonExtraFieldsValue['langfile']    ?? '',
                $extraField['enabled']     ?? $commonExtraFieldsValue['enabled']     ?? '1',
                $extraField['totalizable'] ?? $commonExtraFieldsValue['totalizable'] ?? 0,
                $extraField['printable']   ?? $commonExtraFieldsValue['printable']   ?? 0,
                $extraField['moreparams']  ?? $commonExtraFieldsValue['moreparams']  ?? []
            );

            if ($result < 0) {
                throw new Exception($langs->transnoentities('ExtrafieldsFieldAddFailed', $key));
            }

            // Update ExtraField
            $result = $extraFields->update(
                $key, $extraField['Label'], $extraField['type'], $extraField['length'] ?? '',
                $extraFieldElementType,
                $extraField['unique']   ?? $commonExtraFieldsValue['unique']   ?? 0,
                $extraField['required'] ?? $commonExtraFieldsValue['required'] ?? 0,
                $extraField['position'],
                $extraField['params'] ? ['options' => $extraField['params']] : '',
                $extraField['alwayseditable'] ?? $commonExtraFieldsValue['alwayseditable'] ?? 0,
                $extraField['perms']          ?? $commonExtraFieldsValue['perms']          ?? '',
                $extraField['list']           ?? $commonExtraFieldsValue['list']           ?? '',
                $extraField['help'][$extraFieldElementType] ?? $extraField['help'] ?? $commonExtraFieldsValue['help'] ?? '',
                $extraField['default']     ?? $commonExtraFieldsValue['default']     ?? '',
                $extraField['computed']    ?? $commonExtraFieldsValue['computed']    ?? '',
                $extraField['entity']      ?? $commonExtraFieldsValue['entity']      ?? '',
                $extraField['langfile']    ?? $commonExtraFieldsValue['langfile']    ?? '',
                $extraField['enabled']     ?? $commonExtraFieldsValue['enabled']     ?? '1',
                $extraField['totalizable'] ?? $commonExtraFieldsValue['totalizable'] ?? 0,
                $extraField['printable']   ?? $commonExtraFieldsValue['printable']   ?? 0,
                $extraField['moreparams']  ?? $commonExtraFieldsValue['moreparams']  ?? []
            );

            if ($result < 0) {
                throw new Exception($langs->transnoentities('ExtrafieldsFieldUpdateFailed', $key));
            }
        }
    }
}

/**
 * Load list parameters
 *
 * @param string $contexName     Context name
 * @return array $listParameters Array of list parameters
 */
function saturne_load_list_parameters(string $contexName): array
{
    $listParameters = [];

    $listParameters['confirm']     = GETPOST('confirm', 'alpha');      // Result of a confirmation
    $listParameters['toselect']    = GETPOST('toselect', 'array:int'); // Array of ids of elements selected into a list
    $listParameters['contextpage'] = GETPOSTISSET('contextpage') ? GETPOST('contextpage', 'aZ') : $contexName . 'list'; // To manage different context of search
    $listParameters['optioncss']   = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
    $listParameters['mode']        = GETPOST('mode', 'aZ');      // The display mode ('list', 'kanban', 'pwa', 'calendar', 'gantt', ...)
    //$listParameters['groupby']     = GETPOST('groupby', 'aZ09'); // Example: $groupby = 'p.fk_opp_status' or $groupby = 'p.fk_statut'

    return $listParameters;
}


/**
 * Load pagination parameters for list
 *
 * @return array $paginationParameters Array of pagination parameters
 */
function saturne_load_pagination_parameters(): array
{
    global $conf;

    $paginationParameters = [];

    $paginationParameters['limit']     = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
    $paginationParameters['sortfield'] = GETPOST('sortfield', 'aZ09comma');
    $paginationParameters['sortorder'] = GETPOST('sortorder', 'aZ09comma');
    $paginationParameters['page']      = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
    if (empty($paginationParameters['page']) || $paginationParameters['page'] < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
        // If $paginationParameters['page'] is not defined, or '' or -1 or if we click on clear filters
        $paginationParameters['page'] = 0;
    }
    $paginationParameters['offset'] = $paginationParameters['limit'] * $paginationParameters['page'];

    return $paginationParameters;
}

/**
 * CSS for field in list
 *
 * @param  array   $val         Array of field
 * @param  string  $key         Key of field
 * @return string  $cssForField CSS for field
 */
function saturne_css_for_field(array $val, string $key): string
{
    $cssForField = '';
    if ($key == 'status') {
        $cssForField = 'center';
    } elseif (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $cssForField = 'center';
    } elseif (isset($val['type']) && in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && !in_array($key, ['id', 'rowid', 'ref', 'status']) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
        $cssForField = 'right';
    } elseif (isset($val['type']) && $val['type'] == 'timestamp') {
        $cssForField = 'nowraponall';
    } elseif ($key == 'ref') {
        $cssForField = 'nowraponall';
    }
    $cssForField .= (empty($val['csslist']) ? (empty($val['css']) ? '' : ' ' . $val['css']) : ' ' . $val['csslist']);

    return $cssForField;
}
