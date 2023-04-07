<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       public/signature/add_signature.php
 *       \ingroup    saturne
 *       \brief      Public page to add signature
 */

if (!defined('NOREQUIREUSER')) {
    define('NOREQUIREUSER', '1');
}
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOLOGIN')) { // This means this output page does not require to be logged.
    define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) { // We accept to go on this page from external website.
    define('NOCSRFCHECK', '1');
}
if (!defined('NOIPCHECK')) { // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}

// Load Saturne environment
if (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} elseif (file_exists('../../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Get module parameters
$moduleName = GETPOST('module_name', 'alpha');
$objectType = GETPOST('object_type', 'alpha');

$moduleNameLowerCase = strtolower($moduleName);

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

require_once __DIR__ . '/../../class/saturnesignature.class.php';
require_once __DIR__ . '/../../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action   = GETPOST('action', 'aZ09');
$source   = GETPOST('source', 'aZ09');

// Initialize technical objects
$classname       = ucfirst($objectType);
$object          = new $classname($db);
//$sessiondocument = new SessionDocument($db, $objectType);
$signatory       = new SaturneSignature($db);
$user            = new User($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$objectType . 'publicsignature', 'saturnepublicsignature', 'saturnepublicinterface', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

$signatory->fetch(0, '', ' AND signature_url =' . "'" . $track_id . "'");
$object->fetch($signatory->fk_object);

$upload_dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1];

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // Action to add signature
    if ($action == 'add_signature') {
        $data        = json_decode(file_get_contents('php://input'), true);
        $signatoryID = GETPOST('signatoryID');
        
        $signatory->fetch($signatoryID);

        $signatory->signature      = $data['signature'];
        $signatory->signature_date = dol_now('tzuser');

//        // Check Captcha code if is enabled
//        if (!empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
//            $sessionkey = 'dol_antispam_value';
//            $ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) === strtolower($data['code'])));
//
//            if (!$ok) {
//                $error++;
//                setEventMessage($langs->trans('ErrorBadValueForCode'), 'errors');
//                $action = '';
//            }
//        }

        $error = 0;

        if (!$error) {
            $result = $signatory->update($user, true);
            if ($result > 0) {
                // Creation signature OK
                $signatory->setSigned($user, false, 'public');
                exit;
            } elseif (!empty($signatory->errors)) { // Creation signature KO
                setEventMessages('', $signatory->errors, 'errors');
            } else {
                setEventMessages($signatory->error, [], 'errors');
            }
        } else {
            exit;
        }
    }

    // Action to build doc
//    if ($action == 'builddoc') {
//        $outputlangs = $langs;
//        $newlang     = '';
//
//        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
//            $newlang = GETPOST('lang_id', 'aZ09');
//        }
//        if (!empty($newlang)) {
//            $outputlangs = new Translate('', $conf);
//            $outputlangs->setDefaultLang($newlang);
//        }
//
//        // To be sure vars is defined
//        if (empty($hidedetails)){
//            $hidedetails = 0;
//        }
//        if (empty($hidedesc)) {
//            $hidedesc = 0;
//        }
//        if (empty($hideref)) {
//            $hideref = 0;
//        }
//        if (empty($moreparams)) {
//            $moreparams = null;
//        }
//
//        $constforval = 'DOLIMEET_' . strtoupper('attendancesheetdocument') . '_ADDON_ODT_PATH';
//        $template    = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $conf->global->$constforval);
//        $model       = 'attendancesheetdocument_odt:' . $template .'template_attendancesheetdocument.odt';
//
//        $moreparams['object']   = $object;
//        $moreparams['user']     = $user;
//        $moreparams['specimen'] = 1;
//        $moreparams['zone']     = 'public';
//
//        $result = $sessiondocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
//
//        if ($result <= 0) {
//            setEventMessages($sessiondocument->error, $sessiondocument->errors, 'errors');
//            $action = '';
//        } elseif (empty($donotredirect)) {
//            copy($upload_dir . '/' . $object->element . 'document' . '/' . $object->ref . '/specimen/' . $sessiondocument->last_main_doc, DOL_DOCUMENT_ROOT . '/custom/dolimeet/documents/temp/' . $object->element . '_specimen_' . $track_id . '.odt');
//            setEventMessages($langs->trans('FileGenerated') . ' - ' . $sessiondocument->last_main_doc, []);
//            $urltoredirect = $_SERVER['REQUEST_URI'];
//            $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
//            $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
//            header('Location: ' . $urltoredirect . '#builddoc');
//            exit;
//        }
//    }

    if ($action == 'remove_file') {
        $files = dol_dir_list(DOL_DOCUMENT_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/'); // get all file names

        foreach ($files as $file) {
            if (is_file($file['fullname'])) {
                dol_delete_file($file['fullname']);
            }
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('Signature');
$morejs  = ['/saturne/js/includes/signature-pad.min.js'];
$morecss = ['/saturne/css/saturne.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, $morejs, $morecss);

$element = $signatory; ?>

<div class="signature-container">
    <?php if (!empty($conf->global->SATURNE_ENABLE_PUBLIC_INTERFACE)) : ?>
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <div class="wpeo-gridlayout grid-2">
            <div class="informations">
    <!--			<input type="hidden" id="confCAPTCHA" value="--><?php //echo $conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA ?><!--"/>-->
                <div class="wpeo-gridlayout grid-2 file-generation">
                    <strong class="grid-align-middle"><?php echo $langs->trans('Document'); ?></strong>
                    <?php $path = DOL_MAIN_URL_ROOT . '/custom/' . $moduleNameLowerCase . '/documents/temp/'; ?>
                    <input type="hidden" class="specimen-name" value="<?php echo $object->element . '_specimen_' . $track_id . '.odt' ?>">
                    <input type="hidden" class="specimen-path" value="<?php echo $path ?>">
                    <span class="wpeo-button button-primary  button-radius-2 grid-align-right auto-download"><i class="button-icon fas fa-print"></i></span>
                </div>
                <br>
                <div class="wpeo-table table-flex table-2">
                    <div class="table-row">
                        <div class="table-cell"><?php echo $langs->trans('FullName'); ?></div>
                        <div class="table-cell table-end"><?php echo strtoupper($signatory->lastname) . ' ' . $signatory->firstname; ?></div>
                    </div>
                    <div class="table-row">
                        <div class="table-cell"><?php echo $langs->trans($langs->trans(ucfirst($object->element))); ?></div>
                        <div class="table-cell table-end"><?php echo $object->ref . ' ' . $object->label; ?></div>
                    </div>
                </div>
            </div>
            <div class="signature">
                <div class="wpeo-gridlayout grid-2">
                    <strong class="grid-align-middle"><?php echo $langs->trans('Signature'); ?></strong>
                    <?php if (!dol_strlen($element->signature)) : ?>
                        <div class="wpeo-button button-primary button-square-40 button-radius-2 grid-align-right wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>">
                            <input type="hidden" class="modal-to-open" value="modal-signature<?php echo $element->id ?>">
                            <input type="hidden" class="from-id" value="<?php echo $element->id ?>">
                            <span><i class="fas fa-pen-nib"></i> <?php echo $langs->trans('Sign'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <br>
                <div class="signature-element">
                    <?php require __DIR__ . '/../../core/tpl/signature/signature_view.tpl.php'; ?>
                </div>
            </div>
        </div>
<!--	--><?php
//	if ( ! empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
//		require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
//		print '<div class="center"><label for="email"><span class="fieldrequired">' . $langs->trans("SecurityCode") . '</span></label>';
//		print '<span class="span-icon-security inline-block">';
//		print '<input id="securitycode" placeholder="' . $langs->trans("SecurityCode") . '" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" />';
//		print '<input type="hidden" id="sessionCode" value="' . $_SESSION['dol_antispam_value'] . '"/>';
//		print '<input type="hidden" id="redirectSignatureError" value="' . $_SERVER['REQUEST_URI'] . '"/>';
//		print '</span>';
//		print '<span class="nowrap inline-block">';
//		print '<img class="inline-block valignmiddle" src="' . DOL_URL_ROOT . '/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
//		print '<a class="inline-block valignmiddle" href="" tabindex="4" data-role="button">' . img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"') . '</a>';
//		print '</span>';
//		print '</div>';
//	}?>
    <?php else :
        print '<div class="center">' . $langs->trans('SignaturePublicInterfaceForbidden') . '</div>';
    endif; ?>
</div>

<?php
llxFooter('', 'public');
$db->close();