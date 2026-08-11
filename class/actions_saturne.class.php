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

        return 0;
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

        return 0;
    }

    /**
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        $saturne_js_path = dol_buildpath('/custom/saturne/js/saturne.min.js', 0);
        $saturne_js_mtime = file_exists($saturne_js_path) ? filemtime($saturne_js_path) : 1;
        $saturne_js_url = DOL_URL_ROOT . '/custom/saturne/js/saturne.min.js?v=' . $saturne_js_mtime;

        if (strpos($parameters['context'], 'usercard') !== false) {
            $resourcesRequired = [
                'css'       => '/custom/saturne/css/saturne.min.css',
                'js'        => $saturne_js_url,
                'signature' => '/custom/saturne/js/includes/signature-pad.min.js'
            ];

            $out  = '<!-- Includes CSS added by module saturne -->';
            $out .= '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($resourcesRequired['css'], 1) . '">';
            $out .= '<!-- Includes JS added by module saturne -->';
            $out .= '<script src="' . $resourcesRequired['js'] . '"></script>';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['signature'], 1) . '"></script>';

            $this->resprints = $out;
        } elseif (strpos($parameters['context'], 'emailtemplates')) {
            $resourcesRequired = [
                'js'        => $saturne_js_url,
            ];

            $out  = '<!-- Includes JS added by module saturne -->';
            $out .= '<script src="' . $resourcesRequired['js'] . '"></script>';

            $this->resprints = $out;
        }
        
        $out = $this->resprints;
        $out .= "\n" . '<!-- Config Saturne injected by addHtmlHeader -->';
        $out .= "\n" . '<script>';
        $out .= "\n" . 'window.saturne = window.saturne || {};';
        $out .= "\n" . 'window.saturne.config = window.saturne.config || {};';
        $out .= "\n" . 'window.saturne.config.enableMaxlengthCounter = ' . getDolGlobalInt('SATURNE_ENABLE_MAXLENGTH_COUNTER', 0) . ';';
        $out .= "\n" . '</script>' . "\n";
        $this->resprints = $out;

        return 0;
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
                foreach ($redirections as $redirection) {
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

        return 0;
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
        global $langs, $user, $object, $db;


        if (strpos($parameters['context'], 'usercard') !== false) {
            $id = GETPOST('id');

            require_once __DIR__ . '/saturnesignature.class.php';

            $signatory = new SaturneSignature($this->db);

            $result = $signatory->fetch(0, '', ' AND fk_object = ' . $id . ' AND status > 0 AND object_type = "user" AND role = "UserSignature"');
            if ($result <= 0) {
                return 0;
            }

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
                    <?php
            }
            $out .= '</div></div>'; ?>

            <script>
                $('.user_extras_electronic_signature').html(<?php echo json_encode($out); ?>);
            </script>
        <?php
        } elseif (
            strpos($_SERVER['PHP_SELF'], '/document.php') !== false ||
                    strpos($_SERVER['PHP_SELF'], '/saturne_document.php') !== false
        ) {
            require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
            require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
            $file    = new EcmFiles($db);
            $tmpFile = new EcmFiles($db);
            $file->fetchAll('', '', 0, 0, '(t.src_object_type:=:\'' . $object->table_element . '\') AND (t.src_object_id:=:' . $object->id . ')');
            $favoritesFiles = [];
            foreach ($file->lines as $singleFile) {
                $tmpFile->id = $singleFile->id;
                $tmpFile->fetch_optionals();
                if (!empty($tmpFile->array_options['options_favorite'])) {
                    $favoritesFiles[] = (int) $tmpFile->id;
                }
            }

            $link = new Link($db);
            $links = [];
            $link->fetchAll($links, $object->element, $object->id);

            $favoritesLinks = [];
            foreach ($links as $singleLink) {
                $singleLink->fetch_optionals();
                if (!empty($singleLink->array_options['options_favorite'])) {
                    $favoritesLinks[] = $singleLink->id;
                }
            }

            print "
                <script>
                        var favoritesFiles = " . json_encode($favoritesFiles) . ";
                        var favoritesLinks = " . json_encode($favoritesLinks) . ";

                        $(document).ready(function() {
                            $('.liste_titre').closest('table').each(function() {
                                var isFile = $(this).attr('id') == 'tablelines';
                                $(this).find('tr.oddeven').each(function() {

                                    var fileId = -1;
                                    if ($(this).attr('id') != undefined) {
                                        fileId = parseInt($(this).attr('id').substring(4)); // Remove 'row-' prefix to get the ID
                                    } else {
                                        var href = $(this).find('.right').last().find('a').first().attr('href');
                                        if (href) {
                                            fileId = parseInt(new URLSearchParams(href).get('linkid'));
                                        }
                                    }

                                    var isFavorite = false;
                                    var starColor = '#6c757d'; // Default gray color

                                    if (fileId !== -1) {
                                        if (isFile && favoritesFiles.includes(fileId)) {
                                            isFavorite = true;
                                            starColor = '#ffc107'; // Yellow for favorites
                                        } else if (!isFile && favoritesLinks.includes(fileId)) {
                                            isFavorite = true;
                                            starColor = '#ffc107'; // Yellow for favorites
                                        }
                                    }

                                    var title = isFavorite ? '" . $langs->trans("RemoveFromFavorites") . "' : '" . $langs->trans("AddToFavorites") . "';
                                    $(this).find('.right').last().prepend('<span class=\"file-favorite file-action\" title=\"' + title + '\" data-favorite=\"' + isFavorite + '\" style=\"color: ' + starColor + '; cursor: pointer; margin-right: 5px;\"><i class=\"fas fa-star\"></i></span>');
                                });
                            });

                            // Handle star click to toggle favorite
                            $(document).on('click', '.file-favorite', function() {
                                var star = $(this);

                                var isFile = $(this).closest('table').attr('id') == 'tablelines';
                                var fileId = -1;
                                var isFavorite = $(this).data('favorite') ? 1 : 0;
                                if ($(this).closest('tr').attr('id') != undefined) {
                                    fileId = $(this).closest('tr').attr('id').substring(4); // Remove 'row-' prefix to get the ID
                                } else {
                                    fileId = new URLSearchParams($(this).parent().find('a').first().attr('href')).get('linkid');
                                }
                                let params = new URLSearchParams(window.location.search);
                                params.append('isFavorite', isFavorite ? 0 : 1);
                                params.append('isFile', isFile ? 1 : 0);
                                params.append('fileId', fileId);
                                params.append('action', 'toggle_favorite');

                                $.ajax({
                                    url: '" . DOL_URL_ROOT . "/custom/saturne/core/ajax/favorite.php?' + params.toString(),
                                    method: 'POST',
                                    contentType: 'application/json charset=utf-8',
                                    success: function(response) {
                                        var data = JSON.parse(response);
                                        if (data.error) {
                                            $.jnotify(data.message, 'error');
                                        } else {
                                            $.jnotify(data.message, 'success');
                                            if (isFavorite) {
                                                star.css('color', '#6c757d');
                                                star.data('favorite', false);
                                                star.attr('title', '" . $langs->trans("AddToFavorites") . "');
                                            } else {
                                                star.css('color', '#ffc107');
                                                star.data('favorite', true);
                                                star.attr('title', '" . $langs->trans("RemoveFromFavorites") . "');
                                            }
                                        }
                                    }
                                });
                            });

                        });
                </script>
            ";

        } elseif (strpos($parameters['context'], 'emailtemplates')) {
            ?>
            <script>
                window.saturne.emailTemplate.updateSub.call($('#type_template'))
                $('#type_template').on('change', window.saturne.emailTemplate.updateSub)
            </script>

            <?php
        }

        return 0;
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
            } elseif (!empty($signatory->errors)) {
                // Creation signature KO
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
                        dol_print_error(null, $object->error);
                    }
                }
            }
        } elseif (strpos($parameters['context'], 'emailtemplates') && $action == 'updateSub') {
            $templateType = GETPOST('type_template');
            if (str_ends_with($templateType, '_send')) {
                $templateType = substr($templateType, 0, -5);
            }

            $objectMeta = saturne_get_objects_metadata();
            $tmpObj = null;

            foreach ($objectMeta as $key => $value) {
                if (str_contains($key, $templateType) || $value['table_element'] == $templateType) {
                    $tmpObj = $value['object'];
                    break;
                }
            }

            $extrafields = [];
            if (!empty($tmpObj)) {
                $tmpObj->fetch_optionals();
                $extrafields = array_keys($tmpObj->array_options);
                $extrafields = array_map(fn($s) => '__EXTRAFIELD_' . dol_strtoupper(preg_replace('/^options_/', '', $s)) . '__', $extrafields);
            }

            print_r(json_encode($extrafields));
            exit;
        }

        return 0;
    }

    /**
     * Overloading the getElementProperties function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function getElementProperties(array $parameters): int
    {
        if (isModEnabled('stock') && $parameters['elementType'] == 'stockmouvement') {
            $parameters['elementProperties'] = [
                'module'        => 'stock',
                'element'       => 'stock',
                'table_element' => 'stock_mouvement',
                'subelement'    => 'mouvement',
                'classpath'     => 'product/stock/class',
                'classfile'     => 'mouvementstock',
                'classname'     => 'MouvementStock'
            ];

            $this->results = $parameters['elementProperties'];
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Add or modify fields definition for the list
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object &$object    The object to process
     * @param  string &$action    Current action
     * @param  HookManager $hookmanager
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneListAddCustomFields(array $parameters, &$object, &$action, $hookmanager): int
    {
        global $db, $langs;

        if (isset($object->element) && $object->element === 'project') {
            // Hide the custom "Opportunité" extrafield
            if (isset($object->fields['opportunity_details'])) {
                $object->fields['opportunity_details']['visible'] = 0;
            }

            // Unhide standard opportunity fields if they are present
            if (isset($object->fields['fk_opp_status'])) {
                $object->fields['fk_opp_status']['visible'] = 1;
                $object->fields['fk_opp_status']['searchall'] = 1;
                $object->fields['fk_opp_status']['csslist'] = 'center';
                
                // Add arrayofkeyval to fk_opp_status so it natively renders as an inline select in Saturne
                require_once DOL_DOCUMENT_ROOT . '/core/class/cleadstatus.class.php';
                $leadStatus = new CLeadStatus($db);
                $leadStatus->fetchAll();
                
                $arrayofkeyval = [];
                $langs->load('projects');
                
                $arrayofkeyval[''] = '';
                
                foreach ($leadStatus->records as $line) {
                    $transLabel = $langs->trans("OppStatus" . $line->code);
                    $label = ($transLabel !== "OppStatus" . $line->code) ? $transLabel : $line->label;
                    $arrayofkeyval[$line->id] = $label;
                }
                $object->fields['fk_opp_status']['arrayofkeyval'] = $arrayofkeyval;
            }
            if (isset($object->fields['opp_percent'])) {
                $object->fields['opp_percent']['visible'] = 1;
                $object->fields['opp_percent']['searchall'] = 1;
                $object->fields['opp_percent']['csslist'] = 'center';
            }
            if (isset($object->fields['opp_amount'])) {
                $object->fields['opp_amount']['visible'] = 1;
                $object->fields['opp_amount']['searchall'] = 1;
                $object->fields['opp_amount']['csslist'] = 'center';
            }
        }
        return 0;
    }

    /**
     * Custom print for field value in list
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object &$object    The object to process
     * @param  string &$action    Current action
     * @param  HookManager $hookmanager
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturnePrintFieldListLoopObject(array $parameters, &$object, &$action, $hookmanager): int
    {
        global $langs, $conf, $db;
        
        if (isset($object->element) && $object->element === 'project') {
            $key = $parameters['key'];
            $val = $parameters['val'];
            
            error_log("SATURNE_HOOK: " . $key);

            if ($key === 'fk_opp_status' || $key === 'p.fk_opp_status' || $key === 'project.fk_opp_status') {
                $ceElement = $object->element;
                $ceTable   = $object->table_element;
                
                require_once DOL_DOCUMENT_ROOT . '/core/class/cleadstatus.class.php';
                $leadStatus = new CLeadStatus($db);
                $leadStatus->fetchAll();
                
                $html = '';
                static $saturneOppJsAdded = false;
                if (!$saturneOppJsAdded) {
                    $saturneOppJsAdded = true;
                    $mapping = [];
                    foreach ($leadStatus->records as $line) {
                        $mapping[$line->id] = (float) $line->percent;
                    }
                    $html .= '<script>
                    window.saturneOppStatusMapping = ' . json_encode($mapping) . ';
                    $(document).off("change.oppSync").on("change.oppSync", ".saturne-inline-select[data-field=\'fk_opp_status\']", function() {
                        var statusId = $(this).val();
                        if (window.saturneOppStatusMapping[statusId] !== undefined) {
                            var newPct = window.saturneOppStatusMapping[statusId];
                            var $tr = $(this).closest("tr");
                            var $pctField = $tr.find(".contenteditable[data-field=\'opp_percent\']");
                            if ($pctField.length) {
                                $pctField.text(newPct).data("changed", true).trigger("blur");
                                
                                var $badge = $pctField.closest(".saturne-inline-percent");
                                if (newPct >= 50) {
                                    $badge.removeClass("badge-status3").addClass("badge-status4");
                                } else {
                                    $badge.removeClass("badge-status4").addClass("badge-status3");
                                }
                            }
                        }
                    });
                    </script>';
                }
                
                // Render the inline select for the row with ONLY the real statuses
                $html .= '<select class="saturne-inline-select" data-field="fk_opp_status" data-element="' . $ceElement . '" data-id="' . $object->id . '">';
                $html .= '<option value="0">&nbsp;</option>';
                foreach ($leadStatus->records as $line) {
                    $selected = ($object->fk_opp_status == $line->id) ? ' selected' : '';
                    $transLabel = $langs->trans("OppStatus" . $line->code);
                    $label = ($transLabel !== "OppStatus" . $line->code) ? $transLabel : $line->label;
                    $html .= '<option value="' . $line->id . '"' . $selected . '>' . dol_escape_htmltag($label) . '</option>';
                }
                $html .= '</select>';
                
                $this->results[$key] = $html;
                return 1;
                
            } elseif ($key === 'opp_percent' || $key === 'p.opp_percent' || $key === 'project.opp_percent') {
                $ceElement = $object->element;
                $ceTable   = $object->table_element;
                $ceLabel   = !empty($val['label']) ? dol_escape_htmltag($val['label']) : 'Pourcentage';
                
                $percent = (float) $object->opp_percent;
                if ($percent > 100) {
                    $percent = 100;
                }
                
                if ($percent >= 50) {
                    $badgeClass = 'badge-status4'; // Green
                } else {
                    $badgeClass = 'badge-status3'; // Orange
                }
                
                // Colored badge container for inline editing
                $html = '<div class="saturne-inline-percent badge ' . $badgeClass . '" style="display: inline-flex; align-items: center; justify-content: center; padding: 3px 6px;">';
                $html .= '<div class="contenteditable" contenteditable="true" role="textbox" aria-label="' . $ceLabel . '" data-field="opp_percent" data-id="' . $object->id . '" data-element="' . $ceElement . '" data-table="' . $ceTable . '" data-type="number" data-success="Enregistré" data-error="Maximum 100%" data-validate-pattern="^(100([.,]0+)?|\d{1,2}([.,]\d+)?)$" ondblclick="event.stopPropagation();" onblur="var v=parseFloat(this.innerText.replace(\',\',\'.\')); if(v>100) this.innerText=\'100\';">';
                $html .= price($percent, 0, '', 0, -1, -1, '');
                $html .= '</div><span class="saturne-inline-percent-suffix" style="margin-left: 2px;">%</span>';
                $html .= '</div>';
                
                $this->results[$key] = $html;
                return 1;
                
            } elseif ($key === 'opp_amount' || $key === 'p.opp_amount' || $key === 'project.opp_amount') {
                $ceElement = $object->element;
                $ceTable   = $object->table_element;
                $ceLabel   = !empty($val['label']) ? dol_escape_htmltag($val['label']) : 'Montant';
                
                $currencySymbol = $langs->getCurrencySymbol($conf->currency);
                
                $html = '<div class="saturne-inline-amount" style="display: flex; align-items: center; justify-content: center; gap: 4px;">';
                $html .= '<div class="contenteditable" contenteditable="true" role="textbox" aria-label="' . $ceLabel . '" data-field="opp_amount" data-id="' . $object->id . '" data-element="' . $ceElement . '" data-table="' . $ceTable . '" data-type="number" data-success="Enregistré" data-error="Format invalide" ondblclick="event.stopPropagation();">';
                $html .= price($object->opp_amount, 0, '', 0, -1, -1, '');
                $html .= '</div>';
                $html .= '<span class="saturne-inline-amount-suffix">' . $currencySymbol . '</span>';
                $html .= '</div>';
                
                $this->results[$key] = $html;
                return 1;
            }
        }
        return 0;
    }

    /**
     * Custom print for field search input in list
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object &$object    The object to process
     * @param  string &$action    Current action
     * @param  HookManager $hookmanager
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturnePrintFieldListSearch(array $parameters, &$object, &$action, $hookmanager): int
    {
        if (isset($object->element) && $object->element === 'project') {
            $key = $parameters['key'];
            $search = $parameters['search'];

            if ($key === 'opp_percent' || $key === 'p.opp_percent' || $key === 'project.opp_percent') {
                $searchVal = GETPOST('search_opp_percent', 'alpha');
                if (empty($searchVal) && isset($search['opp_percent'])) {
                    $searchVal = $search['opp_percent'];
                }
                $this->results[$key] = '<input type="text" class="flat maxwidth50" name="search_opp_percent" value="' . dol_escape_htmltag($searchVal) . '">';
                return 1;
            } elseif ($key === 'opp_amount' || $key === 'p.opp_amount' || $key === 'project.opp_amount') {
                $searchVal = GETPOST('search_opp_amount', 'alpha');
                if (empty($searchVal) && isset($search['opp_amount'])) {
                    $searchVal = $search['opp_amount'];
                }
                $this->results[$key] = '<input type="text" class="flat maxwidth50" name="search_opp_amount" value="' . dol_escape_htmltag($searchVal) . '">';
                return 1;
            }
        }
        
        return 0;
    }
}
