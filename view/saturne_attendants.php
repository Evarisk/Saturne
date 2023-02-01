<?php
/* Copyright (C) 2023 EVARISK <dev@evarisk.com>
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
} else {
    die('Include of saturne main fails');
}

// Get module parameters
$moduleName       = GETPOST('module_name', 'alpha');
$objectType       = GETPOST('object_type', 'alpha');
$objectParentType = GETPOSTISSET('object_parent_type') ? GETPOST('object_parent_type', 'alpha') : $objectType;

$moduleNameLowerCase = strtolower($moduleName);

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
if (isModEnabled('societe')) {
    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
}
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}
if (isModEnabled('contrat')) {
    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}

require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/lib/' . $moduleNameLowerCase . '_' . $objectParentType . '.lib.php';

// Global variables definitions
global $conf, $db, $langs, $hookmanager, $user;

// Load translation files required by the page
// @todo cibler fichier lang signature
$langs->loadLangs([$moduleNameLowerCase . '@' . $moduleNameLowerCase, 'other']);

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $objectType . 'signature'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$classname          = ucfirst($objectType);
$signatureClassname = ucfirst($objectType) . 'Signature';
$object             = new $classname($db);
$signatory          = new $signatureClassname($db);
$usertmp            = new User($db);
if (isModEnabled('societe')) {
    $thirdparty = new Societe($db);
    $contact    = new Contact($db);
}
if (isModEnabled('project')) {
    $project = new Project($db);
}
if (isModEnabled('contrat')) {
    $contract = new Contrat($db);
}

$hookmanager->initHooks([$objectType . 'signature', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
$permissiontoread   = $user->rights->$moduleNameLowerCase->$objectType->read;
$permissiontoadd    = $user->rights->$moduleNameLowerCase->$objectType->write;
$permissiontodelete = $user->rights->$moduleNameLowerCase->$objectType->delete;
if (empty($conf->$moduleNameLowerCase->enabled) || !$permissiontoread) {
    accessforbidden();
}

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
        $attendantID   = GETPOST('attendant');
        $attendantType = GETPOST('attendant_type');
        $attendantRole = strtoupper(GETPOST('attendant_role'));

        //$result = $signatory->setSignatory($object->id, $objectType, 'socpeople', [$extintervenant_id], strtoupper($objectType).'_EXTERNAL_ATTENDANT', 1);
        $result = $signatory->setSignatory($object->id, $objectType, $attendantType == 'internal' ? 'user' : 'socpeople', [$attendantID], strtoupper($objectType) . '_' . $attendantRole, $attendantRole == 'SESSION_TRAINER' ? 0 : 1);

        if ($result > 0) {
            // Creation attendant OK
            if ($attendantType == 'internal') {
                $usertmp = $user;
                $usertmp->fetch($attendantID);
                setEventMessages($langs->trans('AddAttendantMessage') . ' ' . $usertmp->getFullName($langs, 1), []);
            } else {
                $contact->fetch($attendantID);
                setEventMessages($langs->trans('AddAttendantMessage') . ' ' . $contact->getFullName($langs, 1), []);
            }
//            $urltogo = str_replace('__ID__', $result, $backtopage);
//            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
//            header('Location: ' . $urltogo);
        } elseif (!empty($object->errors)) {
            // Creation attendant KO
            setEventMessages('', $object->errors, 'errors');
        } else {
            setEventMessages($object->error, [], 'errors');
        }
    }

    // Action to add signature
    if ($action == 'add_signature') {
        $data        = json_decode(file_get_contents('php://input'), true);
        $signatoryID = GETPOST('signatoryID');

        $signatory->fetch($signatoryID);
        $signatory->signature      = $data['signature'];
        $signatory->signature_date = dol_now('tzuser');

        $result = $signatory->update($user, false);

        if ($result > 0) {
            // Creation signature OK
            $signatory->setSigned($user);
            setEventMessages($langs->trans('SignatureEvent') . ' ' . $contact->getFullName($langs, 1), []);
//            $urltogo = str_replace('__ID__', $result, $backtopage);
//            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
//            header('Location: ' . $urltogo);
//            exit;
        } elseif (!empty($signatory->errors)) {
            // Creation signature KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
    }

    // Action to set status STATUS_ABSENT
    if ($action == 'set_absent') {
        $signatoryID = GETPOST('signatoryID');

        $signatory->fetch($signatoryID);

        $result = $signatory->setAbsent($user, 0);

        if ($result > 0) {
            // set absent OK
            setEventMessages($langs->trans('Attendant') . ' ' . $signatory->firstname . ' ' . $signatory->lastname . ' ' . $langs->trans('SetAbsentAttendant'), []);
//            $urltogo = str_replace('__ID__', $result, $backtopage);
//            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
//            header('Location: ' . $urltogo);
        } elseif (!empty($signatory->errors)) {
            // Creation absent KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
    }

    // Action to send Email
    if ($action == 'send') {
        $signatoryID = GETPOST('signatoryID');
        $signatory->fetch($signatoryID);

        if ( ! $error) {
            $langs->load('mails');

            if (!dol_strlen($signatory->email)) {
                if ($signatory->element_type == 'user') {
                    $usertmp = $user;
                    $usertmp->fetch($signatory->element_id);
                    if (dol_strlen($usertmp->email)) {
                        $signatory->email = $usertmp->email;
                        $signatory->update($user, true);
                    }
                } else if ($signatory->element_type == 'socpeople') {
                    $contact->fetch($signatory->element_id);
                    if (dol_strlen($contact->email)) {
                        $signatory->email = $contact->email;
                        $signatory->update($user, true);
                    }
                }
            }

            $sendto = $signatory->email;

            if (dol_strlen($sendto) && ( ! empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

                $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
                $url  = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $signatory->signature_url  . '&type=' . $objectType, 3);

                $message = $langs->trans('SignatureEmailMessage') . ' ' . $url;
                $subject = $langs->trans('SignatureEmailSubject') . ' ' . $object->ref;

                // Create form object
                // Send mail (substitutionarray must be done just before this)
                $mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), '', '', 0, -1, '', '', '', '', 'mail');

                if ($mailfile->error) {
                    setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                } else {
                    if ( ! empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
                        $result = $mailfile->sendfile();
                        if ($result) {
                            $signatory->last_email_sent_date = dol_now('tzuser');
                            $signatory->update($user, true);
                            $signatory->setPending($user, false);
                            setEventMessages($langs->trans('SendEmailAt') . ' ' . $signatory->email, array());
                            // This avoid sending mail twice if going out and then back to page
                            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                            exit;
                        } else {
                            $langs->load('other');
                            $mesg = '<div class="error">';
                            if ($mailfile->error) {
                                $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                                $mesg .= '<br>' . $mailfile->error;
                            } else {
                                $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                            }
                            $mesg .= '</div>';
                            setEventMessages($mesg, null, 'warnings');
                        }
                    } else {
                        setEventMessages($langs->trans('ErrorSetupEmail'), '', 'errors');
                    }
                }
            } else {
                $langs->load('errors');
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailTo')), null, 'warnings');
                dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
            }
        } else {
            // Mail sent KO
            if ( ! empty($signatory->errors)) setEventMessages(null, $signatory->errors, 'errors');
            else setEventMessages($signatory->error, null, 'errors');
        }
    }

    // Action to delete attendant
    if ($action == 'delete_attendant') {
        $signatoryToDeleteID = GETPOST('signatoryID');
        $signatory->fetch($signatoryToDeleteID);

        $result = $signatory->setDeleted($user, 0);

        if ($result > 0) {
            setEventMessages($langs->trans('DeleteAttendantMessage') . ' ' . $signatory->firstname . ' ' . $signatory->lastname, []);
            // Deletion attendant OK
            // @todo utilité ??
//            $urltogo = str_replace('__ID__', $result, $backtopage);
//            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
//            header('Location: ' . $urltogo);
        } elseif (!empty($signatory->errors)) {
            // Deletion attendant KO
            setEventMessages('', $signatory->errors, 'errors');
        } else {
            setEventMessages($signatory->error, [], 'errors');
        }
    }
}

/*
*	View
*/

// Initialize view objects
$form        = new Form($db);
$formcompany = new FormCompany($db);

$title   = $langs->trans('Attendants') . ' - ' . $langs->trans(ucfirst($objectType));
$helpUrl = 'FR:Module_' . $moduleName;
$morejs  = ['/saturne/js/modules/signature-pad.min.js', '/saturne/js/saturne.js.php'];
$morecss = ['/' . $moduleNameLowerCase . '/css/' . $moduleNameLowerCase . '.css'];

llxHeader('', $title, $helpUrl, '', 0, 0, $morejs, $morecss);

if ($id > 0 || !empty($ref) && empty($action)) {
    // @todo pertinence
    $object->fetch_optionals();
    // Configuration header
    $prepareHead = $objectParentType . 'PrepareHead';
    $head = $prepareHead($object);
    print dol_get_fiche_head($head, 'attendants', $title, -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/' . $moduleNameLowerCase . '/view/' . $objectType . '/' . $objectType . '_list.php', 1) . '?restore_lastsearch_values=1' . '">' . $langs->trans('BackToList') . '</a>';

    $morehtmlref = '<div class="refidno">';
    // Thirdparty
    // @todo a test sur dolimeet
    if (isModEnabled('societe')) {
        $object->fetch_thirdparty();
        $morehtmlref .= $langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
    }

    // Project
    if (isModEnabled('project')) {
        if (!empty($object->fk_project)) {
            $project->fetch($object->fk_project);
            $morehtmlref .= $langs->trans('Project') . ' : ' . $project->getNomUrl(1, '', 1);
        } else {
            $morehtmlref .= '';
        }
    }

    // Contract @todo hook car spécifique a dolimeet
    if (isModEnabled('contrat')) {
        if ($objectType == 'trainingsession') {
            if (!empty($object->fk_contrat)) {
                $contract->fetch($object->fk_contrat);
                $morehtmlref .= $langs->trans('Contract') . ' : ' . $contract->getNomUrl(1, '', 1);
            } else {
                $morehtmlref .= '';
            }
        }
    }
    $morehtmlref .= '</div>';

    // @todo problème avec dolimeet
    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="underbanner clearboth"></div>';

    print dol_get_fiche_end();

    print '<div class="fichecenter">'; ?>

    <?php if ($object->status == $object::STATUS_DRAFT) : ?>
        <div class="wpeo-notice notice-warning">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('DisclaimerSignatureTitle') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans(ucfirst($objectType) . 'MustBeValidatedToSign') ?></div>
            </div>
            <a class="butAction" style="width = 100%;margin-right:0" href="<?php echo dol_buildpath('/custom/' . $moduleNameLowerCase . '/view/' . $objectType . '/' . $objectType . '_card.php?id=' . $id, 1); ?>"><?php echo $langs->trans('GoToValidate') ?></a>;
        </div>
    <?php endif; ?>
    <div class="noticeSignatureSuccess wpeo-notice notice-success hidden">
        <div class="all-notice-content">
            <div class="notice-content">
                <div class="notice-title"><?php echo $langs->trans('AddSignatureSuccess') ?></div>
                <div class="notice-subtitle"><?php echo $langs->trans('AddSignatureSuccessText') . GETPOST('signature_id')?></div>
            </div>
        </div>
    </div>
    <?php

    // Society attendants -- Participants de la société
    // @todo problème rôle
    $society_intervenants = $signatory->fetchSignatory(strtoupper($objectType).'_SOCIETY_ATTENDANT', $object->id, $objectType);
    $society_trainer = $signatory->fetchSignatory(strtoupper($objectType).'_SESSION_TRAINER', $object->id, $objectType);

    $society_intervenants = array_merge($society_intervenants, $society_trainer);

    print load_fiche_titre($langs->trans('Attendants'), '', '');

    print '<table class="border centpercent tableforfield">';

    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Name') . '</td>';
    print '<td>' . $langs->trans('Role') . '</td>';
    print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
    print '<td class="center">' . $langs->trans('SendMailDate') . '</td>';
    print '<td class="center">' . $langs->trans('SignatureDate') . '</td>';
    print '<td class="center">' . $langs->trans('Status') . '</td>';
    print '<td class="center">' . $langs->trans('ActionsSignature') . '</td>';
    print '<td class="center">' . $langs->trans('Signature') . '</td>';
    print '</tr>';

    $alreadyAddedUsers = [];
    $j = 1;
    if (!empty($society_intervenants) && $society_intervenants > 0) {
        foreach ($society_intervenants as $element) {
            $usertmp = $user;
            $usertmp->fetch($element->element_id);
            print '<tr class="oddeven"><td class="minwidth200">';
            print $usertmp->getNomUrl(1);
            print '</td><td>';
            print $langs->trans($element->role);
            print '</td><td class="center">';
            if ($object->status == $object::STATUS_VALIDATED) {
                $signatureUrl = dol_buildpath('/custom/' . $moduleName . '/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&type=' . $objectType, 3);
                print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
            } else {
                print '-';
            }

            print '</td><td class="center">';
            print dol_print_date($element->last_email_sent_date, 'dayhour');
            print '</td><td class="center">';
            print dol_print_date($element->signature_date, 'dayhour');
            print '</td><td class="center">';
            print $element->getLibStatut(5);
            print '</td>';
            print '<td class="center">';
            if ($permissiontoadd && $object->status < $object::STATUS_LOCKED) {
                require __DIR__ . '/../core/tpl/signature/signature_action_view.tpl.php';
            }
            print '</td>';
            print '<td class="center">';
            if ($element->signature != $langs->transnoentities('FileGenerated') && $permissiontoadd) {
                require __DIR__ . '/../core/tpl/signature/signature_view.tpl.php';
            }
            print '</td>';
            print '</tr>';
            $alreadyAddedUsers[$element->element_id] = $element->element_id;
            $j++;
        }
    } else {
        print '<tr><td>';
        print $langs->trans('NoSocietyAttendants');
        print '</td></tr>';
    }

    // @todo check conf et surement changer la post method
    if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
        print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="add_attendant">';
        print '<input type="hidden" name="attendant_type" value="internal">';
        if (!empty($backtopage)) {
            print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
        }

        // Internal attendant
        print '<tr class="oddeven"><td class="maxwidth200">';
        print $form->select_dolusers('', 'attendant', 1, $alreadyAddedUsers, 0, '', '', $conf->entity);
        print '</td>';
        print '<td>';
        print '<select id="attendant_role" name="attendant_role">';
        print '<option value="society_attendant">' . $langs->trans('SocietyAttendant') . '</option>';
        print '<option value="session_trainer">' . $langs->trans('SessionTrainer') . '</option>';
        print '</select>';
        print ajax_combobox('attendant_role');
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i>  ' . $langs->trans('Add') . '</button>';
        print '<td class="center">';
        print '-';
        print '</td>';
        print '</tr>';
        print '</table>';
        print '</form>';
    }

    // External society attendant
    $thirdparty->fetch($object->fk_soc);
    $ext_society_intervenants = $signatory->fetchSignatory(strtoupper($objectType) . '_EXTERNAL_ATTENDANT', $object->id, $objectType);

    print load_fiche_titre($langs->trans('ExternalAttendants'), '', '');

    print '<table class="border centpercent tableforfield">';

    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Thirdparty') . '</td>';
    print '<td>' . $langs->trans('ContactLinked') . '</td>';
    print '<td>' . $langs->trans('Role') . '</td>';
    print '<td class="center">' . $langs->trans('SignatureLink') . '</td>';
    print '<td class="center">' . $langs->trans('SendMailDate') . '</td>';
    print '<td class="center">' . $langs->trans('SignatureDate') . '</td>';
    print '<td class="center">' . $langs->trans('Status') . '</td>';
    print '<td class="center">' . $langs->trans('ActionsSignature') . '</td>';
    print '<td class="center">' . $langs->trans('Signature') . '</td>';
    print '</tr>';

    $already_selected_intervenants = [];
    $j                                           = 1;
    if (is_array($ext_society_intervenants) && ! empty($ext_society_intervenants) && ($ext_society_intervenants > 0)) {
        foreach ($ext_society_intervenants as $element) {
            $contact->fetch($element->element_id);
            print '<tr class="oddeven"><td class="minwidth200">';
            $thirdparty->fetch($contact->fk_soc);
            print $thirdparty->getNomUrl(1);
            print '</td><td>';
            print $contact->getNomUrl(1);
            print '</td><td>';
            print $langs->trans('ExtSocietyIntervenant');
            print '</td><td class="center">';
            if ($object->status == $object::STATUS_VALIDATED) {
                $signatureUrl = dol_buildpath('/custom/dolimeet/public/signature/add_signature.php?track_id=' . $element->signature_url  . '&type=' . $objectType, 3);
                print '<a href=' . $signatureUrl . ' target="_blank"><i class="fas fa-external-link-alt"></i></a>';
			} else {
				print '-';
			}
            print '</td><td class="center">';
            print dol_print_date($element->last_email_sent_date, 'dayhour');
            print '</td><td class="center">';
            print dol_print_date($element->signature_date, 'dayhour');
            print '</td><td class="center">';
            print $element->getLibStatut(5);
            print '</td>';
            print '<td class="center">';
            if ($permissiontoadd && $object->status < $object::STATUS_LOCKED) {
                require __DIR__ . '/../signature/dolimeet_signature_action_view.tpl.php';
            }
            print '</td>';
            print '<td class="center">';
            if ($element->signature != $langs->transnoentities('FileGenerated') && $permissiontoadd) {
                require __DIR__ . '/../signature/dolimeet_signature_view.tpl.php';
            }
            print '</td>';
            print '</tr>';
            $already_selected_intervenants[$element->element_id] = $element->element_id;
            $j++;
        }
    } else {
        print '<tr><td>';
        print $langs->trans('NoExternalAttendants');
        print '</td></tr>';
    }

    if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
        print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="add_attendant">';
        if (!empty($backtopage)) {
            print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
        }

        //Intervenants extérieurs
        $ext_society = $object->fk_soc;
        if ($ext_society < 1) {
            $ext_society = new StdClass();
        }

        print '<tr class="oddeven">';
        print '<td class="tagtd nowrap noborderbottom">';
        $selectedCompany = GETPOSTISSET('newcompany') ? GETPOST('newcompany', 'int') : (empty($object->socid) ?  0 : $object->socid);
        $param           = '&module_name=' . urlencode($moduleName) . '&object_parent_type=' . urlencode($objectParentType) . '&object_type=' . urlencode($objectType);
        $urlbacktopage   = $_SERVER['PHP_SELF'] . '?id=' . $object->id . $param;
        $param          .= '&backtopage=' . urlencode($urlbacktopage);
        $moreparam       = $param;
        $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', '', 0, $moreparam, 'minwidth300imp');

        print '</td>';
        print '<td class="tagtd noborderbottom minwidth500imp">';
        print img_object('', 'contact', 'class="pictofixedwidth"').$form->selectcontacts(($selectedCompany > 0 ? $selectedCompany : -1), '', 'external_attendant', 1, $already_selected_intervenants, '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
        $nbofcontacts = $form->num;
        $newcardbutton = '';
        if (!empty(GETPOST('newcompany')) && GETPOST('newcompany') > 1 && $user->rights->societe->creer) {
            $newcardbutton .= '<a href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$selectedCompany.'&action=create&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id.'&newcompany=' . GETPOST('newcompany')).'" title="'.$langs->trans('NewContact').'"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
        }
        print $newcardbutton;
        print '</td>';
        print '<td>' . $langs->trans('ExtSocietyIntervenant') . '</td>';
        print '<td class="center">';
        print '-';
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '-';
        print '</td><td class="center">';
        print '<button type="submit" class="wpeo-button button-blue"><i class="fas fa-plus"></i>  ' . $langs->trans('Add') . '</button>';
        print '<td class="center">';
        print '-';
        print '</td>';
        print '</tr>';
        print '</table>';
        print '</form>';
    }
    print '</div>';
}

// End of page
llxFooter();
$db->close();
