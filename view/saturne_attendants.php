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
$moduleName = GETPOST('module_name', 'alpha');
$objectType = GETPOST('object_type', 'alpha');

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
if (isModEnabled('societe')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
}

require_once __DIR__ . '/../class/saturnesignature.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'signature'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname = ucfirst($objectType);
$object    = new $classname($db);
$signatory = new SaturneSignature($db);
$usertmp   = new User($db);
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
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Cancel
    if ($cancel && !empty($backtopage)) {
        header('Location: ' . $backtopage);
        exit;
    }

    // Action to add attendant
    if ($action == 'add_attendant') {
        $attendantRole        = GETPOST('attendant_role');
        $attendantTypeUser    = GETPOST('attendant' . $attendantRole . 'user');
        $attendantTypeContact = GETPOST('attendant' . $attendantRole . 'contact');

        if ((empty($attendantTypeUser) || $attendantTypeUser < 0) && (empty($attendantTypeContact) || $attendantTypeContact <= 0)) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('Attendant')), [], 'errors');
        }

        $result = $signatory->setSignatory($object->id, $object->element, ($attendantTypeUser > 0 ? 'user' : 'socpeople'), [($attendantTypeUser > 0 ? $attendantTypeUser : $attendantTypeContact)], $attendantRole, 1);

        if ($result > 0) {
            // Creation attendant OK
            if ($attendantTypeUser > 0) {
                $usertmp = $user;
                $usertmp->fetch($attendantTypeUser);
                setEventMessages($langs->trans('AddAttendantMessage', $langs->trans($attendantRole) . ' ' . $usertmp->getFullName($langs, 1)), []);
            } else {
                $contact->fetch($attendantTypeContact);
                setEventMessages($langs->trans('AddAttendantMessage', $langs->trans($attendantRole) . ' ' . $contact->getFullName($langs, 1)), []);
            }
            // Prevent form reloading page
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element);
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
            $signatory->call_trigger('SATURNESIGNATURE_' . $triggerName, $user);
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
                }
            } elseif ($signatory->element_type == 'socpeople') {
                $contact->fetch($signatory->element_id);
                if (dol_strlen($contact->email)) {
                    $signatory->email = $contact->email;
                    $signatory->update($user, true);
                }
            }
        } else {
            setEventMessage($langs->trans('NoEmailSet', $langs->trans($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), 'warnings');
        }

        $sendto = $signatory->email;
        if (dol_strlen($sendto) && (!empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

            $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
            $url  = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url  . '&module_name=' . $moduleNameLowerCase . '&object_type=' . $object->element , 3);

            $message = $langs->trans('SignatureEmailMessage', $url);
            $subject = $langs->trans('SignatureEmailSubject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref);

            // Create form object
            // Send mail (substitutionarray must be done just before this)
            $mailfile = new CMailFile($subject, $sendto, $from, $message, [], [], [], '', '', 0, -1, '', '', '', '', 'mail');
            if ($mailfile->error) {
                setEventMessages($mailfile->error, $mailfile->errors, 'errors');
            } elseif (!empty($conf->global->MAIN_MAIL_SMTPS_ID) || $conf->global->SATURNE_USE_ALL_EMAIL_MODE > 0) {
                $result = $mailfile->sendfile();
                if ($result) {
                    $signatory->last_email_sent_date = dol_now('tzuser');
                    $signatory->update($user, true);
                    $signatory->setPending($user, false);
                    setEventMessages($langs->trans('SendEmailAt', $signatory->email), []);
                    // Prevent form reloading page
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element);
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

    $paramname2 = 'module_name=' . $moduleName . '&object_type';
    $paramval2  = $object->element;
    $trackid    = $object->element . '_' . $object->id;
    include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

    // Action to delete attendant
    if ($action == 'delete_attendant') {
        $signatoryToDeleteID = GETPOST('signatoryID', 'int');
        $signatory->fetch($signatoryToDeleteID);

        $result = $signatory->setDeleted($user);

        if ($result > 0) {
            setEventMessages($langs->trans('DeleteAttendantMessage', $langs->trans($signatory->role) . ' ' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname), []);
            // Prevent form reloading page
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element);
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
$morejs  = ['/saturne/js/includes/signature-pad.min.js'];

saturne_header(0,'', $title, $helpUrl, '', 0, 0, $morejs);

if ($id > 0 || !empty($ref) && empty($action)) {
    $object->fetch_optionals();

    saturne_get_fiche_head($object, 'attendants', $title);
    saturne_banner_tab($object);

    print '<div class="fichecenter">';

    $backtocard = dol_buildpath('/custom/' . $moduleNameLowerCase . '/view/' . $object->element . '/' . $object->element . '_card.php?id=' . $id, 1);

    $parameters = ['backtocard' => $backtocard];
    $reshook    = $hookmanager->executeHooks('SaturneAttendantBackToCard', $parameters, $object); // Note that $action and $object may have been modified by some hooks
    if ($reshook > 0) {
        $backtocard = $hookmanager->results;
    }

    if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) : ?>
        <div class="wpeo-notice notice-warning">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('BeCareful') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))) ?></div>
            </div>
            <a class="butAction" href="<?php echo $backtocard ?>"><i class="fas fa-check"></i> <?php echo $langs->trans('GoToValidate', $langs->transnoentities('The' . ucfirst($object->element))) ?></a>;
        </div>
    <?php endif; ?>
        <div class="noticeSignatureSuccess wpeo-notice notice-success hidden">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('AddSignatureSuccess') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans('AddSignatureSuccessText') . GETPOST('signature_id')?></div>
            </div>
        </div>
    <?php
    print '</div>';

    print '<div class="signatures-container">';

    if ($object->status == $object::STATUS_VALIDATED && $permissiontoadd) {
        print '<div class="tabsAction" style="margin-bottom: 0">';
        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '&action=presend&mode=init&token=' .newToken() . '#formmailbeforetitle' . '"><i class="fas fa-paper-plane"></i> ' . $langs->trans('SendGlobalSignatureMail') . '</a>';
        if ($signatory->checkSignatoriesSignatures($object->id, $object->element)) {
            print '<a class="butAction" href="' . $backtocard . '"><i class="fas fa-lock"></i> ' . $langs->trans('GoToLock', $langs->transnoentities('The' . ucfirst($object->element))) . '</a>';
        }
        print '</div>';
    }

    $zone = 'private';
    switch ($object->element) {
        case 'meeting' :
            $attendantsRole = ['Contributor', 'Responsible'];
            break;
        case 'trainingsession' :
            $attendantsRole = ['Trainee', 'SessionTrainer'];
            break;
        case 'audit' :
            $attendantsRole = ['Auditor'];
            break;
        default :
            $attendantsRole = ['Attendant'];
    }
    $alreadyAddedUsers = [];

    foreach ($attendantsRole as $attendantRole) {
        $signatories = $signatory->fetchSignatory($attendantRole, $object->id, $object->element);

        print load_fiche_titre($langs->trans('Attendants') . ' - ' . $langs->trans($attendantRole), '', '');

        if (is_array($signatories) && !empty($signatories) && $signatories > 0) {
            print '<table class="border centpercent tableforfield">';

            print '<tr class="liste_titre">';
            print '<td>' . img_picto('', 'company') . ' ' . $langs->trans('ThirdParty') . '</td>';
            print '<td>' . img_picto('', 'user') . ' ' . $langs->trans('User') . ' | ' . img_picto('', 'contact') . ' ' . $langs->trans('Contacts') . '</td>';
            print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
            print '<td class="center">' . $langs->trans('SendMailDate') . '</td>';
            print '<td>' . $langs->trans('SignatureDate') . '</td>';
            print '<td class="center">' . $langs->trans('Status') . '</td>';
            print '<td class="center">' . $langs->trans('Attendance') . '</td>';
            print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
            print '</tr>';

            foreach ($signatories as $element) {
                $usertmp = new User($db);
                $contact = new Contact($db);
                print '<tr class="oddeven" data-signatory-id="' . $element->id . '"><td class="minwidth200">';
                if ($element->element_type == 'socpeople') {
                    $contact->fetch($element->element_id);
                    $thirdparty->fetch($contact->fk_soc);
                    print $thirdparty->getNomUrl(1);
                } else {
                    $usertmp->fetch($element->element_id);
                    if ($usertmp->contact_id > 0) {
                        $contact->fetch($usertmp->contact_id);
                        $thirdparty->fetch($contact->fk_soc);
                        print $thirdparty->getNomUrl(1);
                    } else {
                        print img_picto('', 'company') . ' ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
                    }
                }
                print '</td><td>';
                if ($element->element_type == 'user') {
                    $usertmp->fetch($element->element_id);
                    print $usertmp->getNomUrl(1, '', 0, 0, 24, 1);
                    if (!empty($usertmp->job)) {
                        print ' - ' . $usertmp->job;
                    }
                } else {
                    $contact->fetch($element->element_id);
                    print $contact->getNomUrl(1);
                    if (!empty($contact->job)) {
                        print ' - ' . $contact->job;
                    }
                }
                print '</td><td class="center copy-signatureurl-container">';
                if ($object->status == $object::STATUS_VALIDATED) {
                    if ((!$user->rights->$moduleNameLowerCase->$objectType->read && $user->rights->$moduleNameLowerCase->assignedtome->$objectType && ($element->element_id == $user->id || $element->element_id == $user->contact_id)) || $permissiontoadd) {
                        $signatureUrl = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $element->signature_url . '&module_name=' . $moduleNameLowerCase . '&object_type=' . $object->element, 3);
                        print '<a href=' . $signatureUrl . ' target="_blank"><div class="wpeo-button button-primary"><i class="fas' . (($element->status == SaturneSignature::STATUS_SIGNED) ? ' fa-eye' : ' fa-signature') . '"></i></div></a>';
                        print ' <i class="fas fa-clipboard copy-signatureurl" data-signature-url="' . $signatureUrl . '" style="color: #666"></i>';
                        print '<span class="copied-to-clipboard" style="display:none">' . '  ' . $langs->trans('CopiedToClipboard') . '</span>';
                    }
                }
                print '</td><td class="center">';
                if ($object->status == $object::STATUS_VALIDATED) {
                    if (dol_strlen($element->email) || dol_strlen($usertmp->email) || dol_strlen($contact->email)) {
                        print dol_print_date($element->last_email_sent_date, 'dayhour');
                        $nbEmailSent = 0;
                        // Enable caching of emails sent count actioncomm
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
                        $cacheKey = 'count_emails_sent_' . $element->id;
                        $dataRetrieved = dol_getcache($cacheKey);
                        if (!is_null($dataRetrieved)) {
                            $nbEmailSent = $dataRetrieved;
                        } else {
                            $sql = 'SELECT COUNT(id) as nb';
                            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm';
                            $sql .= ' WHERE fk_element = ' . $object->id;
                            if ($element->element_type == 'user') {
                                $sql .= ' AND fk_user_action = ' . $element->element_id;
                            } else {
                                $sql .= ' AND fk_contact = ' . $element->element_id;
                            }
                            $sql .= " AND code = '" . 'AC_SATURNESIGNATURE_PENDING_SIGNATURE' . "'";
                            $sql .= " AND elementtype = '" . $object->element . '@' . $moduleNameLowerCase . "'";
                            $resql = $db->query($sql);
                            if ($resql) {
                                $obj = $db->fetch_object($resql);
                                $nbEmailSent = $obj->nb;
                            } else {
                                dol_syslog('Failed to count actioncomm ' . $db->lasterror(), LOG_ERR);
                            }
                            dol_setcache($cacheKey, $nbEmailSent, 120); // If setting cache fails, this is not a problem, so we do not test result.
                        }

                        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                        print '<input type="hidden" name="token" value="' . newToken() . '">';
                        print '<input type="hidden" name="action" value="send_email">';
                        print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
                        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                        print '<button type="submit" class="signature-email wpeo-button button-primary" value="' . $element->id . '">';
                        print '<i class="fas fa-paper-plane"></i>';
                        print '</button>';
                        if ($nbEmailSent > 0) {
                            print ' <span class="badge badge-info">' . $nbEmailSent . '</span>';
                        }
                        print '</form>';
                    } else {
                        print '<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="' . $langs->trans('NoEmailSet', $langs->trans($element->role) . ' ' . strtoupper($element->lastname) . ' ' . $element->firstname) . '"><i class="fas fa-paper-plane"></i></div>';
                    }
                }
                print '</td><td>';
                print dol_print_date($element->signature_date, 'dayhour');
                print '</td><td class="center">';
                print $element->getLibStatut(5);
                print '</td><td class="center">';
                switch ($element->attendance) {
                    case 1:
                        $cssButton = '';
                        $userIcon  = 'fa-user-clock';
                        break;
                    case 2:
                        $cssButton = 'button-red';
                        $userIcon  = 'fa-user-slash';
                        break;
                    default:
                        $cssButton = 'button-green';
                        $userIcon  = 'fa-user';
                        break;
                }
                if ($object->status == $object::STATUS_VALIDATED && $permissiontoadd) {
                    print '<div class="wpeo-dropdown dropdown-right attendance-container">';
                    print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
                    print '<div class="dropdown-toggle wpeo-button ' . $cssButton . '"><i class="fas ' . $userIcon . '"></i></div>';
                    print '<ul class="dropdown-content wpeo-gridlayout grid-3">';
                    print '<li class="dropdown-item set-attendance" style="padding: 0" value="0"><div class="wpeo-button button-green"><i class="fas fa-user"></i></div></li>';
                    print '<li class="dropdown-item set-attendance" style="padding: 0" value="1"><div class="wpeo-button"><i class="fas fa-user-clock"></i></div></li>';
                    print '<li class="dropdown-item set-attendance" style="padding: 0" value="2"><div class="wpeo-button button-red"><i class="fas fa-user-slash"></i></div></li>';
                    print '</ul>';
                    print '</div>';
                } else {
                    print '<div class="dropdown-toggle wpeo-button ' . $cssButton . '"><i class="fas ' . $userIcon . '"></i></div>';
                }
                print '</td><td class="center">';
                if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
                    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="delete_attendant">';
                    print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                    print '<button type="submit" name="deleteAttendant" id="deleteAttendant" class="attendant-delete wpeo-button button-grey" value="' . $element->id . '">';
                    print '<i class="fas fa-trash"></i>';
                    print '</button>';
                    print '</form>';
                }
                print '</td>';
                print '</tr>';
                $alreadyAddedUsers[$element->element_id] = $element->element_id;
            }

            if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="add_attendant">';
                print '<input type="hidden" name="attendant_role" value="' . $attendantRole . '">';
                if (!empty($backtopage)) {
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                }

                print '<tr class="oddeven"><td>';
                $selectedCompany = GETPOSTISSET('newcompany' . $attendantRole) ? GETPOST('newcompany' . $attendantRole, 'int') : (empty($object->socid) ?  0 : $object->socid);
                $moreparam = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element);
                $moreparam .= '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreparam);
                $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany' . $attendantRole, '', 0, $moreparam, 'minwidth300imp');
                print '</td>';
                print '<td class=minwidth400">';
                if ($selectedCompany <= 0) {
                    print img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers('', 'attendant' . $attendantRole . 'user', 1, $alreadyAddedUsers, 0, '', '', $conf->entity, 0, 0, '', 0, '', 'minwidth200 widthcentpercentminusx maxwidth300') . '<br>';
                }
                print img_object('', 'contact', 'class="pictofixedwidth"') . $form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), GETPOST('contactID'), 'attendant' . $attendantRole . 'contact', 1, $alreadyAddedUsers, '', 1, 'minwidth200 widthcentpercentminusx maxwidth300');
                if (!empty($selectedCompany) && $selectedCompany > 0 && $user->rights->societe->creer) {
                    $newcardbutton = '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid=' . $selectedCompany . '&action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element) . '&newcompany' . $attendantRole . '=' . GETPOST('newcompany' . $attendantRole) . '&contactID=&#95;&#95;ID&#95;&#95;') . '" title="' . $langs->trans('NewContact') . '"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
                    print $newcardbutton;
                }
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td>';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i></button>';
                print '</td></tr>';
                print '</table>';
                print '</form>';
            }
        } else {
            print '<div class="opacitymedium">' . $langs->trans('NoAttendants') . '</div>';

            if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
                print '<br><table class="border centpercent tableforfield">';

                print '<tr class="liste_titre">';
                print '<td>' . img_picto('', 'company') . ' ' . $langs->trans('ThirdParty') . '</td>';
                print '<td>' . img_picto('', 'user') . ' ' . $langs->trans('User') . ' | ' . img_picto('', 'contact') . ' ' . $langs->trans('Contacts') . '</td>';
                print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
                print '<td class="center">' . $langs->trans('SendMailDate') . '</td>';
                print '<td>' . $langs->trans('SignatureDate') . '</td>';
                print '<td class="center">' . $langs->trans('Status') . '</td>';
                print '<td class="center">' . $langs->trans('Attendance') . '</td>';
                print '<td class="center">' . $langs->trans('SignatureActions') . '</td>';
                print '</tr>';

                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="add_attendant">';
                print '<input type="hidden" name="attendant_role" value="' . $attendantRole . '">';
                if (!empty($backtopage)) {
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                }

                print '<tr class="oddeven"><td>';
                $selectedCompany = GETPOSTISSET('newcompany' . $attendantRole) ? GETPOST('newcompany' . $attendantRole, 'int') : (empty($object->socid) ?  0 : $object->socid);
                $moreparam = '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element);
                $moreparam .= '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . $moreparam);
                $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany' . $attendantRole, '', 0, $moreparam, 'minwidth300imp');
                print '</td>';
                print '<td class=minwidth400">';
                if ($selectedCompany <= 0) {
                    print img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers('', 'attendant' . $attendantRole . 'user', 1, $alreadyAddedUsers, 0, '', '', $conf->entity, 0, 0, '', 0, '', 'minwidth200 widthcentpercentminusx maxwidth300') . '<br>';
                }
                print img_object('', 'contact', 'class="pictofixedwidth"') . $form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), GETPOST('contactID'), 'attendant' . $attendantRole . 'contact', 1, $alreadyAddedUsers, '', 1, 'minwidth200 widthcentpercentminusx maxwidth300');
                if (!empty($selectedCompany) && $selectedCompany > 0 && $user->rights->societe->creer) {
                    $newcardbutton = '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid=' . $selectedCompany . '&action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&module_name=' . urlencode($moduleName) . '&object_type=' . urlencode($object->element) . '&newcompany' . $attendantRole . '=' . GETPOST('newcompany' . $attendantRole) . '&contactID=&#95;&#95;ID&#95;&#95;') . '" title="' . $langs->trans('NewContact') . '"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
                    print $newcardbutton;
                }
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td>';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '-';
                print '</td><td class="center">';
                print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i></button>';
                print '</td></tr>';
                print '</table>';
                print '</form>';
            }
        }
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
        $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
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

        $formmail->withto              = $liste;
        $formmail->withtofree          = (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1');
        $formmail->withtocc            = $liste;
        $formmail->withtopic           = $outputlangs->trans('SendMailSubject', '__REF__');
        $formmail->withbody            = 1;
        $formmail->withcancel          = 1;

        if (dol_strlen($object->thirdparty->email)) {
            $receiver          = ['thirdparty'];
            $_POST['receiver'] = $receiver;
        }

        $mesg = $outputlangs->transnoentities('GlobalSignatureEmailMessage', strtolower($outputlangs->trans(ucfirst($object->element))), $object->label, dol_print_date($object->date_start, 'dayhour', 'tzuser'), strtolower($outputlangs->trans('AttendanceSheetDocument')));

        foreach ($attendantsRole as $attendantRole) {
            $signatories = $signatory->fetchSignatory($attendantRole, $object->id, $object->element);
            if (is_array($signatories) && !empty($signatories)) {
                foreach ($signatories as $objectSignatory) {
                    if ($objectSignatory->role == $attendantRole) {
                        $mesg .= $outputlangs->trans($objectSignatory->role) . ' : ' . strtoupper($objectSignatory->lastname) . ' ' . $objectSignatory->firstname . '<br>';
                        $signatureUrl = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $objectSignatory->signature_url . '&module_name=' . $moduleNameLowerCase . '&object_type=' . $object->element, 3);
                        $mesg .= '<a href=' . $signatureUrl . ' target="_blank">' . $signatureUrl . '</a><br><br>';
                    }
                }
            }
        }

        $mesg .= $outputlangs->transnoentities('GlobalSignatureEmailMessageEnd');

        $_POST['message'] = $mesg;

        // Array of substitutions
        $formmail->substit = $substitutionarray;

        // Array of other parameters
        $formmail->param['action']    = 'send';
        $formmail->param['id']        = $object->id;
        $formmail->trackid            = $object->element . '_' . $object->id;
        $formmail->param['returnurl'] = $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element;

        // Show form
        print $formmail->get_form();

        print dol_get_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
