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
 * \file    lib/documents.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne Documents
 */

/**
 * Return a string to show the box with list of available documents for object.
 * This also set the property $this->numoffiles
 *
 * @param  string            $modulepart       Module the files are related to ('mymodule', 'mymodule:nameofsubmodule', 'mymodule_temp')
 * @param  string|array      $modulesubdir     Existing (so sanitized) sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
 * @param  string|array      $filedir          Directory to scan
 * @param  string            $urlsource        Url of origin page (for return)
 * @param  int|string[]      $genallowed       Generation is allowed (1/0 or array list of templates)
 * @param  int               $delallowed       Remove is allowed (1/0)
 * @param  string            $modelselected    Model to preselect by default
 * @param  int               $allowgenifempty  Allow generation even if list of template ($genallowed) is empty (show however a warning)
 * @param  int               $forcenomultilang Do not show language option (even if MAIN_MULTILANGS defined)
 * @param  int               $notused          Not used
 * @param  int               $noform           Do not output html form tags
 * @param  string            $param            More param on http links
 * @param  string            $title            Title to show on top of form. Example: '' (Default to "Documents") or 'none'
 * @param  string            $buttonlabel      Label on submit button
 * @param  string            $codelang         Default language code to use on lang combo box if multilang is enabled
 * @param  string            $morepicto        Add more HTML content into cell with picto
 * @param  CommonObject|null $object           Object when method is called from an object card.
 * @param  int               $hideifempty      Hide section of generated files if there is no file
 * @param  string            $removeaction     (optional) The action to remove a file
 * @param  int               $active           (optional) To show gen button disabled
 * @param  string            $tooltiptext      (optional) Tooltip text when gen button disabled
 * @return string                              Output string with HTML array of documents (might be empty string)
 */
function saturne_show_documents(string $modulepart, $modulesubdir, $filedir, string $urlsource, $genallowed, int $delallowed = 0, string $modelselected = '', int $allowgenifempty = 1, int $forcenomultilang = 0, int $notused = 0, int $noform = 0, string $param = '', string $title = '', string $buttonlabel = '', string $codelang = '', string $morepicto = '', $object = null, int $hideifempty = 0, string $removeaction = 'remove_file', int $active = 1, string $tooltiptext = ''): string
{
	global $conf, $db, $form, $hookmanager, $langs;

	if (!is_object($form)) {
        $form = new Form($db);
    }

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	// Add entity in $param if not already exists
	if ( ! preg_match('/entity=[0-9]+/', $param)) {
		$param .= ($param ? '&' : '') . 'entity=' . (!empty($object->entity) ? $object->entity : $conf->entity);
	}

	$hookmanager->initHooks(['formfile']);

	// Get list of files
	$fileList = [];
	if (!empty($filedir)) {
        if (is_array($filedir)) {
            foreach ($filedir as $fileDirSingle) {
                $fileList = array_merge($fileList, dol_dir_list($fileDirSingle, 'files', 0, '(\.jpg|\.jpeg|\.png|\.odt|\.zip|\.pdf)', '', 'date', SORT_DESC, 1));
            }
        } else {
            $fileList = dol_dir_list($filedir, 'files', 0, '(\.jpg|\.jpeg|\.png|\.odt|\.zip|\.pdf)', '', 'date', SORT_DESC, 1);
        }
	}

	if ($hideifempty && empty($fileList)) {
        return '';
    }

	$out         = '';
	$forname     = 'builddoc';
	$headershown = 0;
	$showempty   = 0;

	$out .= "\n" . '<!-- Start saturne_show_documents -->' . "\n";

	$titletoshow = $langs->trans('Documents');
	if (!empty($title)) {
        $titletoshow = ($title == 'none' ? '' : $title);
    }

	// Show table
	if ($genallowed) {
		$submodulepart = $modulepart;
		// modulepart = 'nameofmodule' or 'nameofmodule:NameOfObject'
		$tmp = explode(':', $modulepart);

		if (!empty($tmp[1])) {
			$modulepart    = $tmp[0];
			$submodulepart = $tmp[1];
			$moduleNameUpperCase = dol_strtoupper($modulepart);
		}

		// For normalized external modules.
		$file = dol_buildpath('/' . $modulepart . '/core/modules/' . $modulepart . '/'. $modulepart .'documents/' . strtolower($submodulepart) . '/modules_' . strtolower($submodulepart) . '.php');
		if (file_exists($file)) {
            include_once $file;
            $class = 'ModeleODT' . $submodulepart;

            if (class_exists($class)) {
                if (preg_match('/specimen/', $param)) {
                    $type      = strtolower($class) . 'specimen';
                    include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                    $modellist = getListOfModels($db, $type);
                } else {
                    $modellist = call_user_func($class . '::liste_modeles', $db, 100);
                }
            } else {
                dol_print_error($db, "Bad value for modulepart '" . $modulepart . "' in saturne_show_documents");
                return -1;
            }
        } else {
            if (preg_match('/specimen/', $param)) {
                $type      = 'SaturneDocumentModelspecimen';
                include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                $modellist = getListOfModels($db, $type);
            } else {
                require_once __DIR__ . '/../core/modules/saturne/modules_saturne.php';
                $saturneDocumentModel = new SaturneDocumentModel($db, $modulepart, $submodulepart);
                $documentType = strtolower($submodulepart);
                $modellist = $saturneDocumentModel->liste_modeles($db, $documentType);
            }
        }

		// Set headershown to avoid to have table opened a second time later
		$headershown = 1;

		if (empty($buttonlabel)) {
            $buttonlabel = $langs->trans('Generate');
        }

		if ($conf->browser->layout == 'phone') {
            // So we switch to form after a generation
            $urlsource .= '#' . $forname . '_form';
        }
		if (empty($noform)) {
            $out .= '<form action="' . $urlsource . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc') . '" id="' . $forname . '_form" method="post">';
        }

		$out .= '<input type="hidden" name="action" value="builddoc">';
		$out .= '<input type="hidden" name="token" value="' . newToken() . '">';
		$out .= load_fiche_titre($titletoshow, '', '', 0, 'builddoc');
		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="liste formdoc noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		$addcolumforpicto = ($delallowed || $morepicto);
		$colspan          = (3 + ($addcolumforpicto ? 1 : 0));
        $colspanmore      = 0;

		$out .= '<th colspan="' . $colspan . '" class="formdoc liste_titre maxwidthonsmartphone center">';

		// Model
		if (!empty($modellist)) {
			asort($modellist);
			$out .= '<span class="hideonsmartphone"> <i class="fas fa-file-word"></i> </span>';
			$modellist = array_filter($modellist, 'saturne_remove_index');
			if (is_array($modellist)) {
				foreach ($modellist as $key => $modellistsingle) {
					$arrayvalues         = preg_replace('/template_/', '', $modellistsingle);
                    $newKey              = str_replace($object->element . 'document_custom_odt', $object->element . 'document_odt', $key);
                    $modellists[$newKey] = $langs->trans($arrayvalues);
                    $confName            = dol_strtoupper($modulepart . '_' . $submodulepart) . '_DEFAULT_MODEL';
                    if (dol_strlen(getDolGlobalString($confName)) > 0 && strpos($key, getDolGlobalString($confName)) !== false) {
                        $modelselected = $newKey;
                    }
                }
            }

			$morecss = 'maxwidth200';
			if ($conf->browser->layout == 'phone') {
                $morecss = 'maxwidth100';
            }

			$out .= $form::selectarray('model', $modellists, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);

			if ($conf->use_javascript_ajax) {
				$out .= ajax_combobox('model');
			}

            // Button
			if ($active) {
				$genbutton = '<button class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" id="' . $forname . '_generatebutton" name="' . $forname . '_generatebutton" type="submit" aria-label="' . $langs->trans('Generate') . '"><i class="fas fa-print button-icon"></i></button>';
			} else {
				$genbutton = '<i class="fas fa-exclamation-triangle pictowarning wpeo-tooltip-event" aria-label="' . $langs->trans($tooltiptext) . '"></i>';
				$genbutton .= '<button class="wpeo-button button-square-40 button-disable" name="' . $forname . '_generatebutton"><i class="fas fa-print button-icon"></i></button>';
			}

            if (!$allowgenifempty && !is_array($modellists) && empty($modellists)) {
                $genbutton .= ' disabled';
            }
            if ($allowgenifempty && !is_array($modellists) && empty($modellists) && empty($conf->dol_no_mouse_hover)) {
                $langs->load('errors');
                $genbutton .= ' ' . img_warning($langs->transnoentitiesnoconv('WarningNoDocumentModelActivated'));
            }
            if (!$allowgenifempty && !is_array($modellists) && empty($modellists) && empty($conf->dol_no_mouse_hover)) {
                $genbutton = '';
            }
            if (empty($modellists) && !$showempty) {
                $genbutton = '';
            }
            $out .= $genbutton;
		} else {
			$out .= '<div class="float">' . $langs->trans('Files') . '</div>';
		}

        if (!$active) {
            $htmltooltip = $tooltiptext;
            $out .= '<span class="center">';
            $out .= $form->textwithpicto($langs->trans('Help'), $htmltooltip, 1, 0);
            $out .= '</span>';
        }
		$out .= '</th>';

		if (!empty($hookmanager->hooks['formfile'])) {
			foreach ($hookmanager->hooks['formfile'] as $moduleName) {
				if (method_exists($moduleName, 'formBuilddocLineOptions')) {
					$colspanmore++;
					$out .= '<th></th>';
				}
			}
		}
		$manualPdfGenerationConf = $moduleNameUpperCase . '_MANUAL_PDF_GENERATION';
		if ($conf->global->$manualPdfGenerationConf > 0) {
			$out .= '<td></td>';
		}
		$out .= '</tr>';

		// Execute hooks
		$parameters = ['colspan' => ($colspan + $colspanmore), 'socid' => ($GLOBALS['socid'] ?? ''), 'id' => ($GLOBALS['id'] ?? ''), 'modulepart' => $modulepart];
		if (is_object($hookmanager)) {
			$hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
			$out .= $hookmanager->resPrint;
		}
	}

	// Get list of files
	if (!empty($filedir)) {
		$link_list = [];
		$addcolumforpicto = ($delallowed || $morepicto);
		$colspan          = (3 + ($addcolumforpicto ? 1 : 0));
        $colspanmore      = 0;
		if (is_object($object) && $object->id > 0) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
			$link      = new Link($db);
			$sortfield = $sortorder = null;
			$link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
		}

		$out .= '<!-- documents.lib::saturne_show_documents -->' . "\n";

		// Show title of array if not already shown
		if ((!empty($fileList) || !empty($link_list) || preg_match('/^massfilesarea/', $modulepart)) && !$headershown) {
			$headershown = 1;
			$out        .= load_fiche_titre($titletoshow, '', '', 0, 'builddoc');
			$out        .= '<div class="div-table-responsive-no-min">';
			$out        .= '<table class="noborder centpercent" id="' . $modulepart . '_table">' . "\n";
		}

		// Loop on each file found
		if (is_array($fileList)) {
			foreach ($fileList as $file) {
				// Define relative path for download link (depends on module)
				$relativepath = $file['name']; // Cas general
                if (is_array($modulesubdir)) {
                    foreach ($modulesubdir as $moduleSubDirSingle) {
                        if (strstr($file['path'], $moduleSubDirSingle)) {
                            $relativepath = $moduleSubDirSingle . '/' . $file['name'];
                        }
                    }
                } elseif ($modulesubdir) {
                    $relativepath = $modulesubdir . '/' . $file['name'];
                }

				$out .= '<tr class="oddeven">';

				$documenturl = DOL_URL_ROOT . '/document.php';
				if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
                    $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper
                }

				// Show file name with link to download
				$out .= '<td class="minwidth200">';
				$out .= '<a class="documentdownload paddingright" href="' . $documenturl . '?modulepart=' . $modulepart . '&amp;file=' . urlencode($relativepath) . ($param ? '&' . $param : '') . '"';

				$mime = dol_mimetype($relativepath, '', 0);
				if (preg_match('/text/', $mime)) {
                    $out .= ' target="_blank"';
                }
				$out .= '>';
				$out .= img_mime($file['name'], $langs->trans('File') . ': ' . $file['name']);
				$out .= dol_trunc($file['name'], 150);
				$out .= '</a>' . "\n";

                // Preview
                if (!empty($conf->use_javascript_ajax) && ($conf->browser->layout != 'phone')) {
                    $tmparray = getAdvancedPreviewUrl($modulepart, $relativepath, 1, '&entity=' . $entity);
                    if ($tmparray && $tmparray['url']) {
                        $out .= '<a href="'.$tmparray['url'].'"'.($tmparray['css'] ? ' class="'.$tmparray['css'].'"' : '').($tmparray['mime'] ? ' mime="'.$tmparray['mime'].'"' : '').($tmparray['target'] ? ' target="'.$tmparray['target'].'"' : '').'>';
                        //$out.= img_picto('','detail');
                        $out .= '<i class="fa fa-search-plus paddingright" style="color: gray"></i>';
                        $out .= '</a>';
                    }
                }
                $out .= '</td>';

				// Show file size
				$size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir . '/' . $file['name']));
				$out .= '<td class="nowrap right">' . dol_print_size($size, 1, 1) . '</td>';

				// Show file date
				$date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir . '/' . $file['name']));
				$out .= '<td class="nowrap right">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';

				// Show pdf generation icon
				if ($conf->global->$manualPdfGenerationConf > 0) {
					$extension = pathinfo($file['name'], PATHINFO_EXTENSION);

					$out .= '<td class="right">';

					if ($extension == 'odt') {
						$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
						$out .= '<a class="pdf-generation" href="' . $tmpurlsource . ((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;') . 'action=pdfGeneration&amp;file=' . urlencode($relativepath) . '&token=' . newToken();
						$out .= ($param ? '&amp;' . $param : '');
						$out .= '">' . img_picto($langs->trans("PDFGeneration"), 'fontawesome_fa-file-pdf_fas_red') . '</a>';
						$out .= ' ' . $form->textwithpicto('', $langs->trans('PDFGenerationTooltip'));
					}

					$out .= '</td>';
				}

				if ($delallowed || $morepicto) {
					$out .= '<td class="right nowraponall">';
					if ($delallowed) {
						$tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
						$out         .= '<a href="' . $tmpurlsource . ((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;') . 'action=' . $removeaction . '&amp;file=' . urlencode($relativepath) . '&token=' . newToken();
						$out         .= ($param ? '&amp;' . $param : '');
						$out         .= '">' . img_picto($langs->trans('Delete'), 'delete') . '</a>';
					}
					if ($morepicto) {
						$morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
						$out      .= $morepicto;
					}
					$out .= '</td>';
				}

				if (is_object($hookmanager)) {
					$parameters = ['colspan' => ($colspan + $colspanmore), 'socid' => ($GLOBALS['socid'] ?? ''), 'id' => ($GLOBALS['id'] ?? ''), 'modulepart' => $modulepart, 'relativepath' => $relativepath];
					$res        = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
					if (empty($res)) {
						$out .= $hookmanager->resPrint; // Complete line
						$out .= '</tr>';
					} else {
						$out = $hookmanager->resPrint; // Replace all $out
					}
				}
			}
		}

        // Loop on each link found
          if (is_array($link_list)) {
              $colspan = 2;
              foreach ($link_list as $file) {
                  $out .= '<tr class="oddeven">';
                  $out .= '<td colspan="' . $colspan . '" class="maxwidhtonsmartphone">';
                  $out .= '<a data-ajax="false" href="' . $file->url . '" target="_blank">';
                  $out .= $file->label;
                  $out .= '</a>';
                  $out .= '</td>';
                  $out .= '<td class="right">';
                  $out .= dol_print_date($file->datea, 'dayhour');
                  $out .= '</td>';
                  if ($delallowed || $printer || $morepicto) {
                      $out .= '<td></td>';
                  }
                  $out .= '</tr>' . "\n";
              }
          }

		if (count($fileList) == 0 && count($link_list) == 0 && $headershown) {
			$out .= '<tr><td colspan="' . (3 + ($addcolumforpicto ? 1 : 0)) . '" class="opacitymedium">' . $langs->trans('None') . '</td></tr>' . "\n";
		}
	}

	if ($headershown) {
		// Affiche pied du tableau
		$out .= "</table>\n";
		$out .= "</div>\n";
		if ($genallowed) {
			if (empty($noform)) {
                $out .= '</form>' . "\n";
            }
		}
	}
	$out .= '<!-- End show_document -->' . "\n";

	return $out;
}

/**
 * Exclude index.php files from list of models for document generation
 *
 * @param  string $model Model name
 * @return string        '' or $model
 */
function saturne_remove_index(string $model): string
{
	if (preg_match('/index.php/', $model)) {
		return '';
	} else {
		return $model;
	}
}

/**
 * Return list of activated modules usable for document generation
 *
 * @param  DoliDB     $db                Database handler
 * @param  string     $type              Type of models (object->type)
 * @param  int        $maxfilenamelength Max length of value to show
 * @return array|int                     0 if no module is activated, or array(key=>label). For modules that need directory scan, key is completed with ":filename".
 * @throws Exception
 */
function saturne_get_list_of_models(DoliDB $db, string $type, int $maxfilenamelength = 0)
{
	global $conf, $langs;
	$liste = [];
	$found = 0;
	$dirtoscan = '';

	$sql = 'SELECT nom as id, nom as doc_template_name, libelle as label, description as description';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'document_model';
	$sql .= " WHERE type = '" . $db->escape($type) . "'";
	$sql .= ' AND entity IN (0,' . $conf->entity . ')';
	$sql .= ' ORDER BY description DESC';

	dol_syslog('/saturne/lib/documents.lib.php::saturne_get_list_of_models', LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$found = 1;

			$obj = $db->fetch_object($resql);

			// If this generation module needs to scan a directory, then description field is filled
			// with the constant that contains list of directories to scan (COMPANY_ADDON_PDF_ODT_PATH, ...).
			if (!empty($obj->description)) {	// A list of directories to scan is defined
				include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				$const = $obj->description;
				//irtoscan.=($dirtoscan?',':'').preg_replace('/[\r\n]+/',',',trim($conf->global->$const));
				$dirtoscan = preg_replace('/[\r\n]+/', ',', trim($conf->global->$const));

				$listoffiles = [];

				// Now we add models found in directories scanned
				$listofdir = explode(',', $dirtoscan);
				foreach ($listofdir as $key => $tmpdir) {
					$tmpdir = trim($tmpdir);
					$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
					$tmpdir = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $tmpdir);
					if (!$tmpdir) {
						unset($listofdir[$key]);
						continue;
					}
					if (is_dir($tmpdir)) {
						// all type of template is allowed
						$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '', '');
						if (count($tmpfiles)) {
							$listoffiles = array_merge($listoffiles, $tmpfiles);
						}
					}
				}

				if (count($listoffiles)) {
					foreach ($listoffiles as $record) {
						$max = ($maxfilenamelength ?: 50);
						$liste[$obj->id.':'.$record['fullname']] = dol_trunc($record['name'], $max, 'middle');
					}
				} else {
					$liste[0] = $obj->label.': '.$langs->trans('None');
				}
			} else {
				// Common usage
				$liste[$obj->id] = $obj->label ?: $obj->doc_template_name;
			}
			$i++;
		}
	} else {
		dol_print_error($db);
		return -1;
	}

	if ($found) {
		return $liste;
	} else {
		return 0;
	}
}
