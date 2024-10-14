<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 *  \file       view/saturne_attendants.php
 *  \ingroup    saturne
 *  \brief      Tab of attendants on generic element
 */

// Load Saturne environment
if (file_exists('../saturne.main.inc.php')) {
    require_once __DIR__ . '/../saturne.main.inc.php';
} elseif (file_exists('../../saturne.main.inc.php')) {
    require_once __DIR__ . '/../../saturne.main.inc.php';
} else {
    die('Include of saturne main fails');
}

// Get module parameters
$moduleName   = GETPOST('module_name', 'alpha');
$objectType   = GETPOST('object_type', 'alpha');
$documentType = GETPOST('document_type', 'alpha');

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
if (isModEnabled('societe')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
}

require_once __DIR__ . '/../class/saturnesignature.class.php';
require_once __DIR__ . '/../class/saturnemail.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                 = GETPOST('id', 'int');
$ref                = GETPOST('ref', 'alpha');
$action             = GETPOST('action', 'aZ09');
$contextpage        = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'signature'; // To manage different context of search
$cancel             = GETPOST('cancel', 'aZ09');
$backtopage         = GETPOST('backtopage', 'alpha');
$attendantTableMode = (GETPOSTISSET('attendant_table_mode') ? GETPOST('attendant_table_mode', 'alpha') : 'advanced');
$subaction          = GETPOST('subaction', 'alpha');

// Initialize technical objects
$className   = ucfirst($objectType);
$object      = new $className($db);
$signatory   = new SaturneSignature($db, $moduleNameLowerCase, $object->element);
$saturneMail = new SaturneMail($db, $moduleNameLowerCase, $object->element);
$usertmp     = new User($db);
if (isModEnabled('societe')) {
    $thirdparty = new Societe($db);
    $contact    = new Contact($db);
}

// Initialize view objects
$form        = new Form($db);
$formcompany = new FormCompany($db);

$hookmanager->initHooks([$objectType . 'signature', $object->element . 'signature', 'saturneglobal', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
$permissiontoread   = $user->rights->$moduleNameLowerCase->$objectType->read || $user->rights->$moduleNameLowerCase->assignedtome->$objectType;
$permissiontoadd    = $user->rights->$moduleNameLowerCase->$objectType->write;
$permissiontodelete = $user->rights->$moduleNameLowerCase->$objectType->delete;
saturne_check_access($permissiontoread, null, true);

/*
*  Actions
*/

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Cancel
    if ($cancel && !empty($backtopage)) {
        header('Location: ' . $backtopage);
        exit;
    }

    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../core/tpl/actions/banner_actions.tpl.php';

    // Action to add attendant
    if ($action == 'add_attendant') {
        $attendantRole = GETPOST('attendant_role');
        if ($attendantTableMode == 'advanced') {
            $attendantTypeUser    = GETPOST('attendant' . $attendantRole . 'user');
            $attendantTypeContact = GETPOST('attendant' . $attendantRole . 'contact');
        } else {
            $attendantTypeUser    = GETPOST('attendant_user');
            $attendantTypeContact = GETPOST('attendant_contact');
        }

        if ((empty($attendantTypeUser) || $attendantTypeUser < 0) && (empty($attendantTypeContact) || $attendantTypeContact <= 0)) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('Attendant')), [], 'errors');
        }

        $result = $signatory->setSignatory($object->id, $object->element, ($attendantTypeUser > 0 ? 'user' : 'socpeople'), [($attendantTypeUser > 0 ? $attendantTypeUser : $attendantTypeContact)], $attendantRole, 1);

        if ($result > 0) {
            // Creation attendant OK
            if ($attendantTypeUser > 0) {
                $usertmp = $user;
                $usertmp->fetch($attendantTypeUser);
                setEventMessages($langs->trans('AddAttendantMessage', $langs->transnoentities($attendantRole) . ' ' . $usertmp->getFullName($langs, 1)), []);
            } else {
                $contact->fetch($attendantTypeContact);
                setEventMessages($langs->trans('AddAttendantMessage', $langs->transnoentities($attendantRole) . ' ' . $contact->getFullName($langs, 1)), []);
            }
            // Prevent form reloading page
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode);
            exit;
        } elseif (!empty($signatory->errors)) {
            // Creation attendant KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }

    // Action to set attendance
    if ($action == 'set_attendance') {
        $data = json_decode(file_get_contents('php://input'), true);

        $signatoryID = $data['signatoryID'];
        $attendance  = $data['attendance'];

        $signatory->fetch($signatoryID);

        switch ($attendance) {
            case 1:
                $signatory->attendance = SaturneSignature::ATTENDANCE_DELAY;
                $triggerName = 'ATTENDANCE_DELAY';
                break;
            case 2:
                $signatory->attendance = SaturneSignature::ATTENDANCE_ABSENT;
                $triggerName = 'ATTENDANCE_ABSENT';
                break;
            default:
                $signatory->attendance = SaturneSignature::ATTENDANCE_PRESENT;
                $triggerName = 'ATTENDANCE_PRESENT';
                break;
        }

        $result = $signatory->update($user, true);

        if ($result > 0) {
            // Set attendance OK
            $signatory->call_trigger('SATURNE_SIGNATURE_' . $triggerName, $user);
        } elseif (!empty($signatory->errors)) {
            // Set attendance KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }

    // Action to send Email
    if ($action == 'send_email') {
        $signatoryID = GETPOST('signatoryID', 'int');
        $signatory->fetch($signatoryID);

        $langs->load('mails');
        if (!dol_strlen($signatory->email)) {
            if ($signatory->element_type == 'user') {
                $usertmp = $user;
                $usertmp->fetch($signatory->element_id);
                if (dol_strlen($usertmp->email)) {
                    $signatory->email = $usertmp->email;
                    $signatory->update($user, true);
                } else {
					setEventMessage($langs->trans('NoEmailSet', $langs->transnoentities($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), 'warnings');
				}
            } elseif ($signatory->element_type == 'socpeople') {
                $contact->fetch($signatory->element_id);
                if (dol_strlen($contact->email)) {
                    $signatory->email = $contact->email;
                    $signatory->update($user, true);
                } else {
					setEventMessage($langs->trans('NoEmailSet', $langs->transnoentities($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), 'warnings');
				}
            }
        }

        $sendto = $signatory->email;
        if (dol_strlen($sendto) && (!empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

            $from = $conf->global->MAIN_MAIL_EMAIL_FROM;

            // Make substitution in email content
            $substitutionarray                       = getCommonSubstitutionArray($langs, 0, null, $object);
            $substitutionarray['__OBJECT_ELEMENT__'] = dol_strtolower($langs->transnoentities(ucfirst($object->element)));
            complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

            $result  = $saturneMail->fetch(getDolGlobalInt('SATURNE_EMAIL_TEMPLATE_SIGNATURE'));
            $subject = $result > 0 ? $saturneMail->topic : $langs->transnoentities('EmailSignatureTopic');
            $message = $result > 0 ? $saturneMail->content : $langs->transnoentities('EmailSignatureContent');

            $subject = make_substitutions($subject, $substitutionarray);
            $message = make_substitutions($message, $substitutionarray);

            // Create form object
            // Send mail (substitutionarray must be done just before this)
            $mailfile = new CMailFile($subject, $sendto, $from, $message, [], [], [], '', '', 0, -1, '', '', '', '', 'mail');
            if ($mailfile->error) {
                setEventMessages($mailfile->error, $mailfile->errors, 'errors');
            } elseif (!empty($conf->global->MAIN_MAIL_SMTPS_ID) || $conf->global->SATURNE_USE_ALL_EMAIL_MODE > 0) {
                $result = $mailfile->sendfile();
                if ($result) {
                    $signatory->last_email_sent_date = dol_now();
                    $signatory->update($user, true);
                    $signatory->setPending($user, false);
                    setEventMessages($langs->trans('SendEmailAt', $signatory->email), []);
                    // Prevent form reloading page
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode);
                    exit;
                } else {
                    $langs->load('other');
                    $errorMessage = '<div class="error">';
                    $errorMessage .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                    if ($mailfile->error) {
                        $errorMessage .= '<br>' . $mailfile->error;
                    }
                    $errorMessage .= '</div>';
                    setEventMessages($errorMessage, [], 'warnings');
                }
            } else {
                $url = '<a href="' . dol_buildpath('/admin/mails.php', 1) . '" target="_blank">' . $langs->trans('ConfigEmail') . '</a>';
                setEventMessages($langs->trans('ErrorSetupEmail') . '<br>' . $url, [], 'warnings');
            }
        } else {
            $langs->load('errors');
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailTo')), [], 'warnings');
            dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
        }
    }

    $paramname2 = 'module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode';
    $paramval2  = $attendantTableMode;
    $trackid    = $object->element . '_' . $object->id;
    include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

    // Action to delete attendant
    if ($action == 'delete_attendant') {
        $signatoryToDeleteID = GETPOST('signatoryID', 'int');
        $signatory->fetch($signatoryToDeleteID);

        $result = $signatory->setDeleted($user);

        if ($result > 0) {
            setEventMessages($langs->trans('DeleteAttendantMessage', $langs->transnoentities($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), []);
            // Prevent form reloading page
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode);
            exit;
        } elseif (!empty($signatory->errors)) {
            // Deletion attendant KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
        $action = '';
    }
}

/*
*	View
*/

$title   = $langs->trans('Attendants') . ' - ' . $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0,'', $title, $helpUrl);

if ($id > 0 || !empty($ref) && empty($action)) {
    $object->fetch_optionals();

    saturne_get_fiche_head($object, 'attendants', $title);
    saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

    print '<div class="fichecenter">';

    $backtocard = dol_buildpath('/custom/' . $moduleNameLowerCase . '/view/' . $object->element . '/' . $object->element . '_card.php?id=' . $id, 1);

    $parameters = ['backtocard' => $backtocard];
    $reshook    = $hookmanager->executeHooks('saturneAttendantsBackToCard', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($reshook > 0) {
        $backtocard = $hookmanager->resPrint;
    }

    if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) : ?>
        <div class="wpeo-notice notice-warning">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('BeCareful') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) ?></div>
            </div>
            <a class="butAction" href="<?php echo $backtocard ?>"><i class="fas fa-check"></i> <?php echo $langs->trans('GoToValidate', $langs->transnoentities('The' . ucfirst($object->element))) ?></a>;
        </div>
    <?php endif;

    print '</div>';
    print '<div class="signatures-container">';

    if ($object->status == $object::STATUS_VALIDATED && $permissiontoadd) {
        print '<div class="tabsAction" style="margin-bottom: 0">';
        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode . '&action=presend&mode=init&token=' .newToken() . '#formmailbeforetitle' . '"><i class="fas fa-paper-plane"></i> ' . $langs->trans('SendGlobalSignatureMail') . '</a>';
        if ($signatory->checkSignatoriesSignatures($object->id, $object->element)) {
            print '<a class="butAction" href="' . $backtocard . '"><i class="fas fa-lock"></i> ' . $langs->trans('GoToLock', $langs->transnoentities('The' . ucfirst($object->element))) . '</a>';
        }
        print '</div>';
    }

    print '<div class="tabsAction" style="margin-bottom: 0">';
    print '<a class="btnTitle reposition ' . (($attendantTableMode == 'advanced') ? '' : 'btnTitleSelected') . '" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=simple" title="' . $langs->trans('AttendantTableModeSimple') . '"><span class="fa fa-minus imgforviewmode valignmiddle btnTitle-icon"></span></a>';
    print '<a class="btnTitle reposition ' . (($attendantTableMode == 'advanced') ? 'btnTitleSelected' : '') . '"  href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=advanced" title="' . $langs->trans('AttendantTableModeAdvanced') . '"><span class="fa fa-th-list imgforviewmode valignmiddle btnTitle-icon"></span></a>';
    print '</div>';

    $zone = 'private';

    $parameters = ['signatory' => $signatory];
    $reshook    = $hookmanager->executeHooks('saturneAttendantsRole', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($reshook > 0 && $attendantTableMode == 'advanced') {
        $signatoriesByRole = $hookmanager->resArray;
    } elseif ($attendantTableMode == 'advanced') {
        $signatoriesByRole = $signatory->fetchSignatory('', $object->id, $object->element);
        $signatoriesInDictionary = saturne_fetch_dictionary('c_' . $object->element . '_attendants_role');
        if ($signatoriesByRole == 0) {
            $signatoriesByRole       = [];
        }
        if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
            foreach ($signatoriesInDictionary as $signatoryInDictionary) {
                $signatoriesByRole[$signatoryInDictionary->ref] = $signatoriesByRole[$signatoryInDictionary->ref] ?? [];
            }
        } else {
            $signatoriesByRole = ['Attendant' => []];
        }
    } else {
        $signatoriesByRole['Attendant'] = $signatory->fetchSignatories($object->id, $object->element);
    }

    $alreadyAddedSignatories = [];
    if (is_array($signatoriesByRole) && !empty($signatoriesByRole)) {
        foreach ($signatoriesByRole as $signatoryRole => $signatories) {
            require __DIR__ . '/../core/tpl/attendants/attendants_table_view.tpl.php';
        }
    } else {
        print load_fiche_titre($langs->trans('Attendants') . ' - ' . $langs->trans('Attendant'), '', '');

        print '<div class="opacitymedium">' . $langs->trans('NoAttendants') . '</div>';
    }

    print '</div>';

    print dol_get_fiche_end();

    // Presend form
    if ($action == 'presend') {
        $langs->load('mails');

        // Define output language
        $outputlangs = $langs;
        $newlang = '';
        if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
            $newlang = $object->thirdparty->default_lang;
            if (GETPOST('lang_id', 'aZ09')) {
                $newlang = GETPOST('lang_id', 'aZ09');
            }
        }

        if (!empty($newlang)) {
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang($newlang);
        }

        print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
        print '<div class="clearboth"></div>';
        print '<br>';
        print load_fiche_titre($langs->trans('SendMail'), '', $object->picto);

        print dol_get_fiche_head();

        // Create form for email
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
        $formmail = new FormMail($db);

        $formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
        $formmail->fromtype = (GETPOST('fromtype') ?GETPOST('fromtype') : (!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));

        if ($formmail->fromtype === 'user') {
            $formmail->fromid = $user->id;
        }

        $formmail->withfrom = 1;

        // Define $liste, a list of recipients with email inside <>.
        $liste = [];
        if (!empty($object->socid) && $object->socid > 0 && !is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
            $object->fetch_thirdparty();
        }
        if (is_object($object->thirdparty)) {
            foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
                $liste[$key] = $value;
            }
        }
        if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
            $listeuser = [];
            $fuserdest = new User($db);

            $result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, ['customsql' => "t.statut = 1 AND t.employee = 1 AND t.email IS NOT NULL AND t.email <> ''"], 'AND', true);
            if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
                foreach ($fuserdest->users as $uuserdest) {
                    $listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
                }
            } elseif ($result < 0) {
                setEventMessages(null, $fuserdest->errors, 'errors');
            }
            if (count($listeuser) > 0) {
                $formmail->withtouser = $listeuser;
                $formmail->withtoccuser = $listeuser;
            }
        }

        //$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
        if (!isset($arrayoffamiliestoexclude)) {
            $arrayoffamiliestoexclude = null;
        }

        // Make substitution in email content
        if ($object) {
            // First we set ->substit (useless, it will be erased later) and ->substit_lines
            $formmail->setSubstitFromObject($object, $langs);
        }
        $substitutionarray                                           = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
        $substitutionarray['__OBJECT_ELEMENT__']                     = dol_strtolower($langs->transnoentities(ucfirst($object->element)));
        $substitutionarray['__OBJECT_THE_ELEMENT__']                 = $langs->transnoentities('The' . ucfirst($object->element));
        $substitutionarray['__OBJECT_LABEL_OR_REF__']                = $object->label ?: $object->ref;
        $substitutionarray['__OBJECT_DATE_START_OR_DATE_CREATION__'] = dol_print_date($object->date_start ?: $object->date_creation, 'dayhour', 'tzuser');
        $substitutionarray['__DOCUMENT_TYPE__']                      = dol_strtolower($outputlangs->trans($documentType));

        $parameters = ['mode' => 'formemail'];
        complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

        // Find all external contact addresses
        $tmpobject  = $object;
        $contactarr = [];
        $contactarr = $tmpobject->liste_contact(-1, 'external');

        if (is_array($contactarr) && count($contactarr) > 0) {
            require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            $contactstatic = new Contact($db);
            $tmpcompany = new Societe($db);

            foreach ($contactarr as $contact) {
                $contactstatic->fetch($contact['id']);
                // Complete substitution array
                $substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
                $substitutionarray['__CONTACT_LASTNAME_'.$contact['code'].'__'] = $contactstatic->lastname;
                $substitutionarray['__CONTACT_FIRSTNAME_'.$contact['code'].'__'] = $contactstatic->firstname;
                $substitutionarray['__CONTACT_TITLE_'.$contact['code'].'__'] = $contactstatic->getCivilityLabel();

                // Complete $liste with the $contact
                if (empty($liste[$contact['id']])) {	// If this contact id not already into the $liste
                    $contacttoshow = '';
                    if (isset($object->thirdparty) && is_object($object->thirdparty)) {
                        if ($contactstatic->fk_soc != $object->thirdparty->id) {
                            $tmpcompany->fetch($contactstatic->fk_soc);
                            if ($tmpcompany->id > 0) {
                                $contacttoshow .= $tmpcompany->name.': ';
                            }
                        }
                    }
                    $contacttoshow .= $contactstatic->getFullName($outputlangs, 1);
                    $contacttoshow .= ' <' .($contactstatic->email ?: $langs->transnoentitiesnoconv('NoEMail')) . '>';
                    $liste[$contact['id']] = $contacttoshow;
                }
            }
        }

        $result = $saturneMail->fetch(getDolGlobalInt('SATURNE_EMAIL_TEMPLATE_GLOBAL_SIGNATURE'));

        $topic = $result > 0 ? $saturneMail->topic : $outputlangs->transnoentities('EmailGlobalSignatureTopic');

        $formmail->withto     = $liste;
        $formmail->withtofree = (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1');
        $formmail->withtocc   = $liste;
        $formmail->withtopic  = make_substitutions($topic, $substitutionarray);
        $formmail->withbody   = 1;
        $formmail->withcancel = 1;

        if (dol_strlen($object->thirdparty->email)) {
            $receiver          = ['thirdparty'];
            $_POST['receiver'] = $receiver;
        }

        if (is_array($signatoriesByRole) && !empty($signatoriesByRole)) {
            $signatoriesByRoleSignatureEmailURL = '';
            foreach ($signatoriesByRole as $signatoryRole) {
                foreach ($signatoryRole as $attendant) {
                    $signatoriesByRoleSignatureEmailURL .= $outputlangs->trans($attendant->role) . ' : ' . strtoupper($attendant->lastname) . ' ' . $attendant->firstname . '<br>';
                    $signatureUrl = dol_buildpath('custom/saturne/public/signature/add_signature.php?track_id=' . $attendant->signature_url . '&entity=' . $conf->entity . '&module_name=' . $moduleNameLowerCase . '&object_type=' . $object->element . '&document_type=' . $documentType, 3);
                    $signatoriesByRoleSignatureEmailURL .= '<a href=' . $signatureUrl . ' target="_blank">' . $langs->transnoentities('SignatureEmailURL') . '</a><br><br>';
                }
            }
            $substitutionarray['__SIGNATORIES_BY_ROLE_SIGNATURE_EMAIL_URL__'] = $signatoriesByRoleSignatureEmailURL;
        }
        $content = $result > 0 ? $saturneMail->content : $langs->transnoentities('EmailGlobalSignatureContent');

        $_POST['message'] = make_substitutions($content, $substitutionarray);

        // Array of substitutions
        $formmail->substit = $substitutionarray;

        // Array of other parameters
        $formmail->param['action']    = 'send';
        $formmail->param['id']        = $object->id;
        $formmail->trackid            = $object->element . '_' . $object->id;
        $formmail->param['returnurl'] = $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&document_type=' . $documentType . '&attendant_table_mode=' . $attendantTableMode;

        // Show form
        print $formmail->get_form();

        print dol_get_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
