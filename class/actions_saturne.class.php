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
     * @var string|null String displayed by executeHook() immediately after return
     */
    public ?string $resprints;

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
        if (strpos($parameters['context'], 'saturnepublicinterface') !== false) {
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
                    print '<div class="center signature-logo maxwidth300">';
                    print '<img src="' . $urllogo . '" height="96px" alt="">';
                    print '</div>';
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the emailElementlist function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function emailElementlist(array $parameters): int
    {
        global $user, $langs;
        if (strpos($parameters['context'], 'emailtemplates') !== false) {
            if (isModEnabled('saturne') && $user->hasRight('saturne', 'adminpage', 'read')) {
                $pictopath = dol_buildpath('custom/saturne/img/saturne_color.png', 1);
                $picto     = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');

                foreach (['saturne', 'saturne_document', 'saturne_signature'] as $key) {
                    $value[$key] = $picto . dol_escape_htmltag($langs->trans('Saturne'));
                }
                $this->results = $value;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (strpos($parameters['context'], 'usercard') !== false) {
            $resourcesRequired = [
                'css'       => '/custom/saturne/css/saturne.min.css',
                'js'        => '/custom/saturne/js/saturne.min.js',
                'signature' => '/custom/saturne/js/includes/signature-pad.min.js'
            ];

            $out  = '<!-- Includes CSS added by module saturne -->';
            $out .= '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($resourcesRequired['css'], 1) . '">';
            $out .= '<!-- Includes JS added by module saturne -->';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['js'], 1) . '"></script>';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['signature'], 1) . '"></script>';

            $this->resprints = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the llxHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadatas (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function llxHeader(array $parameters): int
    {
        if (strpos($parameters['context'], 'index') !== false) {
            require_once __DIR__ . '/saturneredirection.class.php';

            $saturneRedirection = new SaturneRedirection($this->db);

            $originalUrl = GETPOST('original_url', 'alpha');

            $redirections = $saturneRedirection->fetchAll();
            if (is_array($redirections) && !empty($redirections)) {
                foreach($redirections as $redirection) {
                    //check redirection from url, if not beginning with a / add it
                    $urlToCheck = $redirection->from_url;
                    if (strpos($redirection->from_url, '/') !== 0) {
                        $urlToCheck = '/' . $redirection->from_url;
                    }
                    if ($urlToCheck == '/' . $originalUrl) {
                        header('Location: ' . $redirection->to_url);
                        exit;
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printCommonFooter function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadatas (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printCommonFooter(array $parameters): int
    {
        global $conf, $form, $langs, $user;

        if (strpos($parameters['context'], 'usercard') !== false) {
            $id = GETPOST('id');

            require_once __DIR__ . '/saturnesignature.class.php';

            $signatory = new SaturneSignature($this->db);

            $signatory->fetch(0, '', ' AND fk_object = ' . $id . ' AND status > 0 AND object_type = "user" AND role = "UserSignature"');

            $pictoPath = dol_buildpath('/saturne/img/saturne_color.png', 1);

            $out  = '<div class="signature-container" data-public-interface="false">';
            $out .= '<div class="signature-user">';
            $out .= img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
            if (dol_strlen($signatory->signature) > 0) {
                $out .= '<div class="signature-image"><img src="' . $signatory->signature . '" width="200px" height="100px" style="border: #0b419b solid 2px" alt=""></div>';
            }
            if ($user->id == $id) {
                $out .= '<div class="wpeo-button button-blue button-square-50 modal-open signature-button" value="' . $signatory->id . '">';
                $out .= '<input type="hidden" class="modal-options" data-modal-to-open="modal-signature' . $signatory->id . '" data-from-test="' . $signatory->id . '">';
                $out .= img_picto('', 'signature', 'class="paddingright"') . $langs->trans("Sign");
                $out .= '</div>'; ?>

                <div class="modal-signature">
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <div class="wpeo-modal modal-signature" id="modal-signature<?php echo $signatory->id; ?>">
                        <div class="modal-container wpeo-modal-event">
                            <!-- Modal-Header-->
                            <div class="modal-header">
                                <h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
                                <div class="modal-close"><i class="fas fa-times"></i></div>
                            </div>
                            <!-- Modal-ADD Signature Content-->
                            <div class="modal-content" id="#modalContent">
                                <canvas class="canvas-container canvas-signature" style="height: 95%; width: 98%; border: #0b419b solid 2px"></canvas>
                            </div>
                            <!-- Modal-Footer-->
                            <div class="modal-footer">
                                <div class="signature-erase wpeo-button button-square-50 button-grey"><span><i class="fas fa-eraser"></i></span></div>
                                <div class="signature-validate wpeo-button button-square-50 button-disable"><span><i class="fas fa-file-signature"></i></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            $out .= '</div></div>'; ?>

            <script>
                $('.user_extras_electronic_signature').html(<?php echo json_encode($out); ?>);
            </script>
            <?php
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadata (context, etc...)
     * @param  object    $object    The object to process
     * @param  string    $action    Current action (if set). Generally create or edit or null
     * @return int                  0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function doActions(array $parameters, $object, string $action): int
    {
        global $user;

        if (strpos($parameters['context'], 'usercard') !== false && $action == 'add_signature') {
            $id = GETPOST('id');

            require_once __DIR__ . '/saturnesignature.class.php';

            $signatory = new SaturneSignature($this->db);
            $data      = json_decode(file_get_contents('php://input'), true);

            $result = $signatory->fetch(0, '', ' AND fk_object = ' . $id . ' AND status > 0 AND object_type = "user" AND role = "UserSignature"');
            if ($result <= 0) {
                $signatory->setSignatory($id, $user->element, 'user', [$id], 'UserSignature');
            }

            $signatory->signature      = $data['signature'];
            $signatory->signature_date = dol_now();

            $result = $signatory->update($user, true);
            if ($result > 0) {
                // Creation signature OK
                $signatory->setSigned($user, false);
                exit;
            } elseif (!empty($signatory->errors)) { // Creation signature KO
                setEventMessages('', $signatory->errors, 'errors');
            } else {
                setEventMessages($signatory->error, [], 'errors');
            }
        } elseif (strpos($parameters['context'], 'categorycard') !== false) {
            global $langs;

            $elementId = GETPOST('element_id');
            $type      = GETPOST('type');

            // Temporary exclude DoliMeet and native Dolibarr objects
            if ($type == 'meeting' || $type == 'audit' || $type == 'trainingsession' || !empty(saturne_get_objects_metadata($type))) {
                return 0;
            }

            $objects = saturne_fetch_all_object_type($type);
            if (is_array($objects) && !empty($objects)) {
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
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }
}
