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
 * \file    class/actions_saturne.class.php
 * \ingroup saturne
 * \brief   Saturne hook overload.
 */

// Load Saturne Libraries
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Class ActionsSaturne
 */
class ActionsSaturne
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public string $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     *  Overloading the printMainArea function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function printMainArea(array $parameters): int
    {
        global $conf, $mysoc;

        // Do something only for the current context
        if ($parameters['currentcontext'] == 'saturnepublicinterface') {
            if (!empty($conf->global->SATURNE_SHOW_COMPANY_LOGO)) {
                // Define logo and logosmall
                $logosmall = $mysoc->logo_small;
                $logo      = $mysoc->logo;
                // Define urllogo
                $urllogo = '';
                if (!empty($logosmall) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $logosmall)) {
                    $urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/thumbs/' . $logosmall);
                } elseif (!empty($logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $logo)) {
                    $urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/' . $logo);
                }
                // Output html code for logo
                if ($urllogo) {
                    print '<div class="center signature-logo">';
                    print '<img src="' . $urllogo . '">';
                    print '</div>';
                }
                print '<div class="underbanner clearboth"></div>';
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the emailElementlist function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function emailElementlist(array $parameters): int
    {
        global $user, $langs;

        // do something only for the context 'somecontext1' or 'somecontext2'
        if ($parameters['currentcontext'] == 'emailtemplates') {
            if (isModEnabled('saturne') && $user->hasRight('saturne', 'adminpage', 'read')) {
                $pictopath = dol_buildpath('/custom/saturne/img/saturne_color.png', 1);
                $picto     = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

                $value['saturne'] = $picto . dol_escape_htmltag($langs->trans('Saturne'));

                $this->results = $value;
            }
        }

        return 0; // or return 1 to replace standard code
    }

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $conf, $form, $langs, $user, $db;

		$resourcesRequired = [
			'css' => '/custom/saturne/css/saturne.min.css',
			'js' => '/custom/saturne/js/saturne.min.js',
			'signature' => '/custom/saturne/js/includes/signature-pad.min.js',
		];

		$error = 0; // Error counter

		if ($parameters['currentcontext'] == 'usercard') {
			$id = GETPOSTISSET('id') ? GETPOST('id') : 0;

			print '<script src="'.dol_buildpath($resourcesRequired['js'], 1).((strpos($resourcesRequired['js'], '?') === false) ? '?' : '&amp;').'lang='.$langs->defaultlang.'"></script>'."\n";
			print '<script src="'.dol_buildpath($resourcesRequired['signature'], 1).((strpos($resourcesRequired['signature'], '?') === false) ? '?' : '&amp;').'lang='.$langs->defaultlang.'"></script>'."\n";
			$urltofile = dol_buildpath($resourcesRequired['css'], 1);

			print '<!-- Includes CSS added by page -->'."\n".'<link rel="stylesheet" type="text/css" title="default" href="'.$urltofile;
			print '">'."\n";

			require_once __DIR__ . '/saturnesignature.class.php';
			$signatory = new SaturneSignature($db);
			$result = $signatory->fetchSignatory('UserSignature', $id, 'user');
			if (!is_array($result) || empty($result)) {
				$userSignatory = $signatory->setSignatory($id, $user->element, 'user', [$id], 'UserSignature');
			} else {
				$userSignatory = array_shift($result);
			}

			if (dol_strlen($userSignatory->signature) > 0) {
				$out = '<div class="signatures-container">';
				$out .= '<input type="hidden" class="modal-options" data-modal-to-open="modal-signature'. $userSignatory->id .'">';
				$out .= '<img class="wpeo-modal-event modal-signature-open modal-open" value="'. $userSignatory->id .'" src="'. $userSignatory->signature .'" width="100px" height="100px" style="border: #0b419b solid 2px">';
				$out .= '</div>';
			}
			if ($user->id == $id) {

				$out .= '<div class="wpeo-button button-blue wpeo-modal-event modal-signature-open modal-open" value="'. $userSignatory->id .'">';
				$out .= '<input type="hidden" class="modal-options" data-modal-to-open="modal-signature'. $userSignatory->id .'" data-from-id="'. $userSignatory->id .'">';
				$out .= '<span><i class="fas fa-signature"></i>'. $langs->trans('Sign') .'</span>';
				$out .= '</div>';

				?>
				<div class="modal-signature" value="<?php echo $userSignatory->id ?>">
					<input type="hidden" name="token" value="<?php echo newToken(); ?>">
					<div class="wpeo-modal modal-signature" id="modal-signature<?php echo $userSignatory->id ?>">
						<div class="modal-container wpeo-modal-event">
							<!-- Modal-Header-->
							<div class="modal-header">
								<h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- Modal-ADD Signature Content-->
							<div class="modal-content" id="#modalContent">
								<input type="hidden" id="signature_data<?php echo $userSignatory->id ?>" value="<?php echo $userSignatory->signature ?>">
								<canvas style="height: 95%; width: 95%; border: #0b419b solid 2px"></canvas>
							</div>
							<!-- Modal-Footer-->
							<div class="modal-footer">
								<div class="signature-erase wpeo-button button-grey">
									<span><i class="fas fa-eraser"></i> <?php echo $langs->trans('Erase'); ?></span>
								</div>
								<div class="wpeo-button button-grey modal-close">
									<span><?php echo $langs->trans('Cancel'); ?></span>
								</div>
								<div class="signature-validate wpeo-button button-primary" value="<?php echo $userSignatory->id ?>">
									<input type="hidden" id="zone<?php echo $userSignatory->id ?>" value="<?php echo 'public' ?>">
									<span><?php echo $langs->trans('Validate'); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<script>
					$('.user_extras_electronic_signature').html(<?php echo json_encode($out) ?>);
				</script>

				<?php
			}
        } elseif (preg_match('/categorycard/', $parameters['context']) && preg_match('/viewcat.php/', $_SERVER['PHP_SELF'])) {
            $id   = GETPOST('id');
            $type = GETPOST('type');

            // Temporary exclude DoliMeet and native Dolibarr objects
            if ($type == 'meeting' || $type == 'audit' || $type == 'trainingsession' || !empty(saturne_get_objects_metadata($type))) {
                return 0;
            }

            // Load variable for pagination
            $limit     = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
            $sortfield = GETPOST('sortfield', 'aZ09comma');
            $sortorder = GETPOST('sortorder', 'aZ09comma');
            $page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
            if (empty($page) || $page == -1) {
                $page = 0;
            }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
            $offset = $limit * $page;

            $objects      = saturne_fetch_all_object_type($type);
            $objectArrays = [];
            if (is_array($objects) && !empty($objects)) {
                foreach ($objects as $object) {
                    $objectArrays[$object->id] = $object->ref;
                }
            }

            $category = new Categorie($this->db);
            $category->fetch($id);
            $objectCategories = $category->getObjectsInCateg($type, 0, $limit, $offset);

            $out = '<br>';

            $out .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&type=' . $type . '">';
            $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
            $out .= '<input type="hidden" name="action" value="addintocategory">';

            $out .= '<table class="noborder centpercent">';
            $out .= '<tr class="liste_titre"><td>';
            $out .= $langs->trans('AddObjectIntoCategory') . ' ';
            $out .= $form::selectarray('element_id', $objectArrays, '', 1);
            $out .= '<input type="submit" class="button buttongen" value="' . $langs->trans('ClassifyInCategory') . '"></td>';
            $out .= '</tr>';
            $out .= '</table>';
            $out .= '</form>';

            $out .= '<br>';

            if (is_array($objects) && !empty($objects)) {
                $object = array_shift($objects);
                $picto = $object->picto;
            }

            $out .= load_fiche_titre($langs->transnoentities(ucfirst($type)), '', 'object_' . $picto);
            $out .= '<table class="noborder centpercent">';
            $out .= '<tr class="liste_titre"><td colspan="3">' . $langs->trans('Ref') . '</td></tr>';

            if (is_array($objectCategories) && !empty($objectCategories)) {
                // Form to add record into a category
                if (count($objectCategories) > 0) {
                    $i = 0;
                    foreach ($objectCategories as $object) {
                        $i++;
                        if ($i > $limit) break;

                        $out .= '<tr class="oddeven">';
                        $out .= '<td class="nowrap">';
                        $object->picto   = $picto;
                        $object->element = $type;
                        $out .= $object->getNomUrl(1);
                        $out .= '</td>';
                        // Link to delete from category
                        $out .= '<td class="right">';
                        if ($user->rights->categorie->creer) {
                            $out .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=delintocategory&id=' . $id . '&type=' . $type . '&element_id=' . $object->id . '&token=' . newToken() . '">';
                            $out .= $langs->trans('DeleteFromCat');
                            $out .= img_picto($langs->trans('DeleteFromCat'), 'unlink', '', false, 0, 0, '', 'paddingleft');
                            $out .= '</a>';
                        }
                        $out .= '</td>';
                        $out .= '</tr>';
                    }
                } else {
                    $out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">' . $langs->trans('ThisCategoryHasNoItems') . '</td></tr>';
                }
            } else {
                $out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">' . $langs->trans('ThisCategoryHasNoItems') . '</td></tr>';
            }

            $out .= '</table>'; ?>

            <script>
                jQuery('.fichecenter').last().after(<?php echo json_encode($out); ?>)
            </script>
            <?php
        }

		if (!$error) {
			$this->results   = array('myreturn' => 999);
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param  array  $parameters Hook metadata (context, etc...)
	 * @param  object $object     The object to process
	 * @param  string $action     Current action (if set). Generally create or edit or null
	 * @return int                0 < on error, 0 on success, 1 to replace standard code
	 */
	public function doActions(array $parameters, $object, string $action): int
	{
		global $db, $user;

		if ($parameters['currentcontext'] == 'usercard' && GETPOST('action') == 'add_signature') {
			require_once __DIR__ . '/saturnesignature.class.php';
			$signatory = new SaturneSignature($db);
			$data        = json_decode(file_get_contents('php://input'), true);

			$signatoryID = GETPOST('signatoryID');

			if ($signatoryID > 0) {
				$signatory->fetch($signatoryID);

				$signatory->signature      = $data['signature'];
				$signatory->signature_date = dol_now();

				$error = 0;

				if (!$error) {
					$result = $signatory->update($user, true);
					if ($result > 0) {
						// Creation signature OK.
						$signatory->setSigned($user, false, 'public');
						exit;
					} elseif (!empty($signatory->errors)) { // Creation signature KO.
						setEventMessages('', $signatory->errors, 'errors');
					} else {
						setEventMessages($signatory->error, [], 'errors');
					}
				}
			}
        } elseif (preg_match('/categorycard/', $parameters['context'])) {
            global $langs;

            $elementId = GETPOST('element_id');
            $type      = GETPOST('type');

            // Temporary exclude DoliMeet and native Dolibarr objects
            if ($type == 'meeting' || $type == 'audit' || $type == 'trainingsession' || !empty(saturne_get_objects_metadata($type))) {
                return 0;
            }

            $objects   = saturne_fetch_all_object_type($type);
            $newObject = $objects[$elementId];

            if (GETPOST('action') == 'addintocategory') {
                $result = $object->add_type($newObject, $type);
                if ($result >= 0) {
                    setEventMessages($langs->trans("WasAddedSuccessfully", $newObject->ref), array());
                } else {
                    if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        setEventMessages($langs->trans("ObjectAlreadyLinkedToCategory"), array(), 'warnings');
                    } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                }
            } elseif (GETPOST('action') == 'delintocategory') {
                $result = $object->del_type($newObject, $type);
                if ($result < 0) {
                    dol_print_error('', $object->error);
                }
                $action = '';
            }
        }
		return 0;
	}
}
