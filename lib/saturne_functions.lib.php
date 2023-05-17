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
function saturne_banner_tab(object $object, string $paramid = 'ref', string $morehtml = '', int $shownav = 1, string $fieldid = 'ref', string $fieldref = 'ref', string $morehtmlref = '', bool $handlePhoto = false): void
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
		$saturneMorehtmlref .= $langs->trans('ThirdParty') . ' : ';
        if (!empty($object->fk_soc)) {
            $object->fetch_thirdparty();
			if (is_object($object->thirdparty)) {
				$saturneMorehtmlref .= $object->thirdparty->getNomUrl(1);
			}
        }
		$saturneMorehtmlref .= '<br>';
    }

    // Project
    if (isModEnabled('project')) {
        if (array_key_exists('fk_project', $object->fields)) {
            $key = 'fk_project';
        } elseif (array_key_exists('projectid', $object->fields)) {
            $key = 'projectid';
        }
		if (dol_strlen($key)) {
			$saturneMorehtmlref .= $langs->trans('Project') . ' : ';
			if (array_key_exists('status', $object->fields)) {
				$formproject = new FormProjets($db);
				$form        = new Form($db);
				if ($object->status < $object::STATUS_LOCKED) {
					$objectTypePost = GETPOST('object_type') ? '&object_type=' . GETPOST('object_type') : '';
					$saturneMorehtmlref .= ' ';
					if (GETPOST('action') == 'edit_project') {
						$saturneMorehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . $objectTypePost .'">';
						$saturneMorehtmlref .= '<input type="hidden" name="action" value="save_project">';
						$saturneMorehtmlref .= '<input type="hidden" name="key" value="'. $key .'">';
						$saturneMorehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
						$saturneMorehtmlref .= $formproject->select_projects(0, $object->$key, $key, 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
						$saturneMorehtmlref .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans("Modify") . '">';
						$saturneMorehtmlref .= '</form>';
					} else {
						$saturneMorehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' .$object->id, 0, $object->$key, 'none', 0, 0, 0, 1);
						$saturneMorehtmlref .= '<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=edit_project&token=' . newToken() . '&id=' . $object->id . $objectTypePost .'">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
					}
				} else {
					$saturneMorehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' .$object->id, 0, $object->$key, 'none', 0, 0, 0, 1);
				}
			}
			$saturneMorehtmlref .= '<br>';
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

	if (!$handlePhoto) {
		dol_banner_tab($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $saturneMorehtmlref, $moreparam);
	} else {
		global $conf, $form;

		print '<div class="arearef heightref valignmiddle centpercent">';
		$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">' . saturne_show_medias_linked($moduleNameLowerCase, $conf->$moduleNameLowerCase->multidir_output[$conf->entity] . '/' . $object->element . '/'. $object->ref . '/photos/', 'small', '', 0, 0, 0, 88, 88, 0, 0, 0, $object->element . '/'. $object->ref . '/photos/', $object, 'photo', 0, 0,0, 1) . '</div>';
		print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $saturneMorehtmlref, $moreparam, 0, $morehtmlleft, $object->getLibStatut(6));
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
 * @param  int    $showLabel       Show label.
 * @param  int    $useEmpty        1 = Add an empty value in list, 2 = Add an empty value in list only if there is more than 2 entries.
 * @param  string $moreAttrib      More attributes on HTML select tag.
 * @param  string $placeHolder     Placeholder.
 * @param  string $moreCSS         More css.
 * @return string
 */
function saturne_select_dictionary(string $htmlName, string $dictionaryTable, string $keyField = 'code', string $labelField = 'label', string $selected = '', int $showLabel = 0, int $useEmpty = 0, string $moreAttrib = '', string $placeHolder = '', string $moreCSS = 'minwidth150'): string
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
				if ($selected == $obj->rowid || $selected == $langs->transnoentities($obj->$keyField)) {
					$out .= '<option value="' . $obj->$keyField . '" selected>';
				} else {
					$out .= '<option value="' . $obj->$keyField . '">';
				}
				$out .= $langs->transnoentities($obj->$keyField) . (($showLabel > 0) ? ' - ' .  $langs->transnoentities($obj->$labelField) : '');
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
 * Get all links for an object type.
 *
 * @param  string    $tableName SQL table name.
 * @param  string    $sortOrder Sort Order.
 * @param  string    $sortField Sort field.
 * @return array|int            0< if KO, >0 if OK.
 */
function saturne_fetch_all_links_for_object_type($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
{
	global $conf, $db;

	$linkedObjectsIds = array();
	$linkedObjects    = array();

	$justsource     = false;
	$justtarget     = false;
	$withtargettype = false;
	$withsourcetype = false;

//		$parameters = array('sourcetype'=>$sourcetype, 'sourceid'=>$sourceid, 'targettype'=>$targettype, 'targetid'=>$targetid);

	if (!empty($sourceid) && !empty($sourcetype) && empty($targetid)) {
		$justsource = true; // the source (id and type) is a search criteria
		if (!empty($targettype)) {
			$withtargettype = true;
		}
	}
	if (!empty($targetid) && !empty($targettype) && empty($sourceid)) {
		$justtarget = true; // the target (id and type) is a search criteria
		if (!empty($sourcetype)) {
			$withsourcetype = true;
		}
	}

	// Links between objects are stored in table element_element
	$sql = "SELECT rowid, fk_source, sourcetype, fk_target, targettype";
	$sql .= " FROM ".$db->prefix()."element_element";
	$sql .= " WHERE ";
	if ($justsource || $justtarget) {
		if ($justsource) {
			$sql .= "fk_source = ".((int) $sourceid)." AND sourcetype = '".$db->escape($sourcetype)."'";
			if ($withtargettype) {
				$sql .= " AND targettype = '".$db->escape($targettype)."'";
			}
		} elseif ($justtarget) {
			$sql .= "targettype = '".$db->escape($targettype)."'";
			if ($withsourcetype) {
				$sql .= " AND sourcetype = '".$db->escape($sourcetype)."'";
			}
		}
	} else {
		$sql .= "(fk_source = ".((int) $sourceid)." AND sourcetype = '".$db->escape($sourcetype)."')";
		$sql .= " ".$clause." (targettype = '".$db->escape($targettype)."')";
	}
	$sql .= " ORDER BY ".$orderby;

//		dol_syslog(get_class($this)."::fetchObjectLink", LOG_DEBUG);
	$resql = $db->query($sql);
	$objectsFetched = [];

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {

			$obj = $db->fetch_object($resql);

			$linkedObjectsByObject[$targettype][$obj->fk_target] = [
				$obj->sourcetype => $obj->fk_source
			];
			$i++;
		}

		if (!empty($linkedObjectsIds)) {
			$tmparray = $linkedObjectsIds;

			foreach ($tmparray as $objecttype => $objectids ) {       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
				// Parse element/subelement (ex: project_task, cabinetmed_consultation, ...)
				$module = $element = $subelement = $objecttype;

				$regs = array();
				if ($objecttype != 'supplier_proposal' && $objecttype != 'order_supplier' && $objecttype != 'invoice_supplier'
					&& preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
					$module = $element = $regs[1];
					$subelement = $regs[2];
				}

				$classpath = $element.'/class';
				// To work with non standard classpath or module name
				if ($objecttype == 'facture') {
					$classpath = 'compta/facture/class';
				} elseif ($objecttype == 'facturerec') {
					$classpath = 'compta/facture/class';
					$module = 'facture';
				} elseif ($objecttype == 'propal') {
					$classpath = 'comm/propal/class';
				} elseif ($objecttype == 'supplier_proposal') {
					$classpath = 'supplier_proposal/class';
				} elseif ($objecttype == 'shipping') {
					$classpath = 'expedition/class';
					$subelement = 'expedition';
					$module = 'expedition_bon';
				} elseif ($objecttype == 'delivery') {
					$classpath = 'delivery/class';
					$subelement = 'delivery';
					$module = 'delivery_note';
				} elseif ($objecttype == 'invoice_supplier' || $objecttype == 'order_supplier') {
					$classpath = 'fourn/class';
					$module = 'fournisseur';
				} elseif ($objecttype == 'fichinter') {
					$classpath = 'fichinter/class';
					$subelement = 'fichinter';
					$module = 'ficheinter';
				} elseif ($objecttype == 'subscription') {
					$classpath = 'adherents/class';
					$module = 'adherent';
				} elseif ($objecttype == 'contact') {
					$module = 'societe';
				}
				// Set classfile
				$classfile = strtolower($subelement);
				$classname = ucfirst($subelement);

				if ($objecttype == 'order') {
					$classfile = 'commande';
					$classname = 'Commande';
				} elseif ($objecttype == 'invoice_supplier') {
					$classfile = 'fournisseur.facture';
					$classname = 'FactureFournisseur';
				} elseif ($objecttype == 'order_supplier') {
					$classfile = 'fournisseur.commande';
					$classname = 'CommandeFournisseur';
				} elseif ($objecttype == 'supplier_proposal') {
					$classfile = 'supplier_proposal';
					$classname = 'SupplierProposal';
				} elseif ($objecttype == 'facturerec') {
					$classfile = 'facture-rec';
					$classname = 'FactureRec';
				} elseif ($objecttype == 'subscription') {
					$classfile = 'subscription';
					$classname = 'Subscription';
				} elseif ($objecttype == 'project' || $objecttype == 'projet') {
					$classpath = 'projet/class';
					$classfile = 'project';
					$classname = 'Project';
				} elseif ($objecttype == 'conferenceorboothattendee') {
					$classpath = 'eventorganization/class';
					$classfile = 'conferenceorboothattendee';
					$classname = 'ConferenceOrBoothAttendee';
					$module = 'eventorganization';
				} elseif ($objecttype == 'conferenceorbooth') {
					$classpath = 'eventorganization/class';
					$classfile = 'conferenceorbooth';
					$classname = 'ConferenceOrBooth';
					$module = 'eventorganization';
				} elseif ($objecttype == 'mo') {
					$classpath = 'mrp/class';
					$classfile = 'mo';
					$classname = 'Mo';
					$module = 'mrp';
				}

				// Here $module, $classfile and $classname are set, we can use them.
				if ($conf->$module->enabled) {
					if ($loadalsoobjects && (is_numeric($loadalsoobjects) || ($loadalsoobjects === $objecttype))) {
						dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
						//print '/'.$classpath.'/'.$classfile.'.class.php '.class_exists($classname);
						if (class_exists($classname)) {
							foreach ($objectids as $i => $objectid) {	// $i is rowid into llx_element_element
								$object = new $classname($db);
								if (is_object($objectsFetched[$objecttype][$objectid])) {
									$linkedObjects[$objecttype][$i] = $objectsFetched[$objecttype][$objectid];
								} else {
									$ret = $object->fetch($objectid);
									if ($ret >= 0) {
										$objectsFetched[$objecttype][$objectid] = $object;
										$linkedObjects[$objecttype][$i] = $object;
									}
								}
							}
						}
					}
				} else {
					unset($linkedObjectsIds[$objecttype]);
				}
			}
		}
		$linkedObjectsData = [
			'ids' => $linkedObjectsByObject,
//				'objects' => $linkedObjects
		];

		return $linkedObjectsData;
	} else {
		dol_print_error($db);
		return -1;
	}
}

