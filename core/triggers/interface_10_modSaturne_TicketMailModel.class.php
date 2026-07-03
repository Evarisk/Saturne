<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/triggers/interface_10_modSaturne_TicketMailModel.class.php
 * \ingroup saturne
 * \brief   Trigger that sends the ticket-creation emails (admin, customer, assignee)
 *          from a configurable "Email template" (Modèle d'email) instead of Dolibarr's
 *          hardcoded content.
 *
 *          It is prefixed "10" on purpose: triggers run in filename order, so this one
 *          runs BEFORE the core ticket email trigger (interface_50_modTicket_TicketEmail).
 *          It sets $object->context['disableticketemail'] = 1 so the core trigger skips
 *          its own 3 creation emails, then sends them itself. When no template is
 *          configured for a given recipient type, it falls back to the exact original
 *          Dolibarr content, so nothing regresses until templates are created.
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/**
 * Class of triggers for ticket creation emails driven by email templates.
 */
class InterfaceTicketMailModel extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;

        $this->name        = preg_replace('/^Interface/i', '', get_class($this));
        $this->family      = 'ticket';
        $this->description = 'Send ticket creation emails from configurable email templates (Modèles d\'email).';
        $this->version     = '1.0.0';
        $this->picto       = 'saturne@saturne';
    }

    /**
     * Function called when a Dolibarr business event is done.
     * All functions "runTrigger" are triggered if file is inside directory core/triggers.
     *
     * @param  string       $action Event action code
     * @param  CommonObject $object Object
     * @param  User         $user   Object user
     * @param  Translate    $langs  Object langs
     * @param  Conf         $conf   Object conf
     * @return int                  0 if no trigger ran, >0 if OK, <0 if KO
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (!isModEnabled('ticket')) {
            return 0; // Ticket module not active, nothing to do
        }
        if ($action !== 'TICKET_CREATE') {
            return 0; // We only take over the ticket creation emails
        }

        /** @var Ticket $object */

        // If another process (e.g. a public interface) already handles the ticket emails,
        // let it do its job and do not take over.
        if (!empty($object->context['disableticketemail'])) {
            return 0;
        }

        dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);

        $langs->load('ticket');

        // Take over: disable the 3 creation emails of the core trigger (interface_50), which runs after us.
        $object->context['disableticketemail'] = 1;

        // Send files that were just uploaded (they are not yet moved to the ticket document directory).
        $formmail = new FormMail($this->db);
        $formmail->trackid = '';
        $attachedfiles = $formmail->get_attached_files();
        $filepaths = $attachedfiles['paths'];
        $filenames = $attachedfiles['names'];
        $mimetypes = $attachedfiles['mimes'];

        // --- Admin notification email ---
        if (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')) {
            $sendto = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO');
            $this->sendAdminMessage($sendto, $object, $user, $langs, $conf, $filepaths, $mimetypes, $filenames);
        }

        // --- Assignee email (if an assignee was set at creation) ---
        if ($object->fk_user_assign > 0 && $object->fk_user_assign != $user->id && !getDolGlobalString('TICKET_DISABLE_ALL_MAILS')) {
            $userstat = new User($this->db);
            if ($userstat->fetch($object->fk_user_assign) > 0 && !empty($userstat->email)) {
                $old_autocopy = null;
                if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
                    $old_autocopy = getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO');
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                }
                $this->sendAssigneeMessage($userstat->email, $object, $user, $langs, $filepaths, $mimetypes, $filenames);
                if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_autocopy;
                }
            } else {
                $this->setErrorsFromObject($userstat);
            }
        }

        // --- Customer email ---
        if (!empty($object->notify_tiers_at_create)) {
            $sendto     = '';
            $contactid  = empty($object->context['contact_id']) ? 0 : $object->context['contact_id'];
            $contactObj = null;

            if (!empty($contactid)) {
                $contactObj = new Contact($this->db);
                $contactObj->fetch($contactid);
            }

            if ($contactObj !== null && !empty($contactObj->email) && !empty($contactObj->statut)) {
                $sendto = $contactObj->email;
            } elseif (!empty($object->fk_soc)) {
                $object->fetch_thirdparty();
                $sendto = $object->thirdparty->email;
            } elseif (!empty($object->origin_email)) {
                $sendto = $object->origin_email;
            }

            if ($sendto) {
                $this->sendCustomerMessage($sendto, $object, $user, $langs, $conf, $filepaths, $mimetypes, $filenames);
            }
        }

        return 1;
    }

    /**
     * Build the substitution array used to fill an email template for a ticket.
     *
     * @param  Ticket    $object The ticket the email refers to
     * @param  Translate $langs  The translation object
     * @return array<string,string>
     */
    private function getTicketSubstitutionArray($object, Translate $langs)
    {
        $substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
        complete_substitutions_array($substitutionarray, $langs, $object);

        // Add ticket-specific keys that are not part of the common substitution array.
        $substitutionarray['__TICKET_REF__']       = (string) $object->ref;
        $substitutionarray['__TICKET_TRACK_ID__']  = (string) $object->track_id;
        $substitutionarray['__TICKET_SUBJECT__']   = (string) $object->subject;
        $substitutionarray['__TICKET_MESSAGE__']   = (string) $object->message;
        $substitutionarray['__TICKET_TYPE__']      = (string) $langs->getLabelFromKey($this->db, 'TicketTypeShort' . $object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code);
        $substitutionarray['__TICKET_CATEGORY__']  = (string) $langs->getLabelFromKey($this->db, 'TicketCategoryShort' . $object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code);
        $substitutionarray['__TICKET_SEVERITY__']  = (string) $langs->getLabelFromKey($this->db, 'TicketSeverityShort' . $object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code);
        $substitutionarray['__TICKET_PUBLIC_URL__'] = dol_buildpath('/public/ticket/view.php', 2) . '?track_id=' . urlencode($object->track_id);
        $substitutionarray['__TICKET_MANAGEMENT_URL__'] = dol_buildpath('/ticket/card.php', 2) . '?track_id=' . urlencode($object->track_id);

        return $substitutionarray;
    }

    /**
     * Fetch the email template configured for a given ticket email type.
     *
     * @param  string    $constname Name of the config constant holding the template label
     * @param  Ticket    $object    The ticket the email refers to
     * @param  User      $user      Object user
     * @param  Translate $langs     The translation object
     * @return array{subject:string,body:string}|null  Substituted subject/body, or null if no template configured/found
     */
    private function getTemplatedContent($constname, $object, User $user, Translate $langs)
    {
        $label = getDolGlobalString($constname);
        if (empty($label)) {
            return null;
        }

        $formmail = new FormMail($this->db);
        // type_template 'ticket' also matches 'ticket_send' and 'all' inside getEMailTemplate().
        $template = $formmail->getEMailTemplate($this->db, 'ticket', $user, $langs, 0, 1, $label);
        if (!is_object($template) || $template->id <= 0) {
            return null;
        }

        $substitutionarray = $this->getTicketSubstitutionArray($object, $langs);

        return array(
            'subject' => make_substitutions($template->topic, $substitutionarray, $langs),
            'body'    => make_substitutions($template->content, $substitutionarray, $langs),
        );
    }

    /**
     * Actually send an email through CMailFile, honouring the TICKET_DISABLE_MAIL_AUTOCOPY_TO setting.
     *
     * @param  string        $subject   Email subject
     * @param  string        $sendto    Recipient addresses
     * @param  string        $from      From header
     * @param  string        $message   Email body (HTML)
     * @param  Ticket        $object    The ticket the email refers to
     * @param  Conf          $conf      Object conf
     * @param  array<string> $filepaths File paths
     * @param  array<string> $mimetypes Mime types
     * @param  array<string> $filenames File names
     * @param  User|null     $user      User to update date_last_msg_sent (only for customer/assignee mails)
     * @return void
     */
    private function sendMail($subject, $sendto, $from, $message, $object, Conf $conf, $filepaths, $mimetypes, $filenames, $user = null)
    {
        $trackid = 'tic' . $object->id;

        $old_autocopy = null;
        if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
            $old_autocopy = getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO');
            $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
        }

        $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepaths, $mimetypes, $filenames, '', '', 0, -1, '', '', $trackid, '', 'ticket');
        if ($mailfile->error) {
            dol_syslog($mailfile->error, LOG_DEBUG);
        } else {
            $result = $mailfile->sendfile();
            if ($result && $user instanceof User) {
                // update last_msg_sent date
                $object->fetch($object->id);
                $object->date_last_msg_sent = dol_now();
                $object->update($user);
            }
        }

        if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
            $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_autocopy;
        }
    }

    /**
     * Compose and send the admin notification email for a new ticket.
     * Uses the configured email template, or falls back to the original Dolibarr content.
     *
     * @param  string        $sendto    Recipient addresses
     * @param  Ticket        $object    The ticket the email refers to
     * @param  User          $user      Object user
     * @param  Translate     $langs     The translation object
     * @param  Conf          $conf      Object conf
     * @param  array<string> $filepaths File paths
     * @param  array<string> $mimetypes Mime types
     * @param  array<string> $filenames File names
     * @return void
     */
    private function sendAdminMessage($sendto, $object, User $user, Translate $langs, Conf $conf, $filepaths, $mimetypes, $filenames)
    {
        global $mysoc;

        $appli = $mysoc->name;

        $templated = $this->getTemplatedContent('SATURNE_TICKET_CREATE_MAIL_MODEL_ADMIN', $object, $user, $langs);
        if ($templated !== null) {
            $subject       = $templated['subject'];
            $message_admin = $templated['body'];
        } else {
            // Fallback: original content of interface_50_modTicket_TicketEmail::composeAndSendAdminMessage()
            $subject        = '[' . $appli . '] ' . $langs->transnoentities('TicketNewEmailSubjectAdmin', $object->ref, $object->track_id);
            $message_admin  = $langs->transnoentities('TicketNewEmailBodyAdmin', $object->track_id) . '<br>';
            $message_admin .= '<ul><li>' . $langs->trans('Title') . ' : ' . $object->subject . '</li>';
            $message_admin .= '<li>' . $langs->trans('Type') . ' : ' . $langs->getLabelFromKey($this->db, 'TicketTypeShort' . $object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code) . '</li>';
            $message_admin .= '<li>' . $langs->trans('TicketCategory') . ' : ' . $langs->getLabelFromKey($this->db, 'TicketCategoryShort' . $object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code) . '</li>';
            $message_admin .= '<li>' . $langs->trans('Severity') . ' : ' . $langs->getLabelFromKey($this->db, 'TicketSeverityShort' . $object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code) . '</li>';
            $message_admin .= '<li>' . $langs->trans('From') . ' : ' . ($object->email_from ? $object->email_from : ($object->fk_user_create > 0 ? $langs->trans('Internal') : '')) . '</li>';
            // Extrafields
            $extraFields = new ExtraFields($this->db);
            $extraFields->fetch_name_optionals_label($object->table_element);
            if (is_array($object->array_options) && count($object->array_options) > 0) {
                foreach ($object->array_options as $key => $value) {
                    $key = substr($key, 8); // remove "options_"
                    $message_admin .= '<li>' . $langs->trans($extraFields->attributes[$object->element]['label'][$key]) . ' : ' . $extraFields->showOutputField($key, $value, '', $object->table_element) . '</li>';
                }
            }
            if ($object->fk_soc > 0) {
                $object->fetch_thirdparty();
                $message_admin .= '<li>' . $langs->trans('Company') . ' : ' . $object->thirdparty->name . '</li>';
            }
            $message_admin .= '</ul>';

            $message = $object->message;
            if (!dol_textishtml($message)) {
                $message = dol_nl2br($message);
            }
            $message_admin .= '<p>' . $langs->trans('Message') . ' : <br><br>' . $message . '</p><br>';
            $message_admin .= '<p><a href="' . dol_buildpath('/ticket/card.php', 2) . '?track_id=' . $object->track_id . '">' . $langs->trans('SeeThisTicketIntomanagementInterface') . '</a></p>';
        }

        $from = (getDolGlobalString('MAIN_INFO_SOCIETE_NOM') ? getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . ' ' : '') . '<' . getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM') . '>';

        $this->sendMail($subject, $sendto, $from, $message_admin, $object, $conf, $filepaths, $mimetypes, $filenames);
    }

    /**
     * Compose and send the customer notification email for a new ticket.
     * Uses the configured email template, or falls back to the original Dolibarr content.
     *
     * @param  string        $sendto    Recipient addresses
     * @param  Ticket        $object    The ticket the email refers to
     * @param  User          $user      Object user
     * @param  Translate     $langs     The translation object
     * @param  Conf          $conf      Object conf
     * @param  array<string> $filepaths File paths
     * @param  array<string> $mimetypes Mime types
     * @param  array<string> $filenames File names
     * @return void
     */
    private function sendCustomerMessage($sendto, $object, User $user, Translate $langs, Conf $conf, $filepaths, $mimetypes, $filenames)
    {
        global $extrafields, $mysoc;

        $appli = $mysoc->name;

        $templated = $this->getTemplatedContent('SATURNE_TICKET_CREATE_MAIL_MODEL_CUSTOMER', $object, $user, $langs);
        if ($templated !== null) {
            $subject          = $templated['subject'];
            $message_customer = $templated['body'];
        } else {
            // Fallback: original content of interface_50_modTicket_TicketEmail::composeAndSendCustomerMessage()
            $subject           = '[' . $appli . '] ' . $langs->transnoentities('TicketNewEmailSubjectCustomer');
            $message_customer  = $langs->transnoentities('TicketNewEmailBodyCustomer', $object->track_id) . '<br>';
            $message_customer .= '<ul><li>' . $langs->trans('Title') . ' : ' . $object->subject . '</li>';
            $message_customer .= '<li>' . $langs->trans('Type') . ' : ' . $langs->getLabelFromKey($this->db, 'TicketTypeShort' . $object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code) . '</li>';
            $message_customer .= '<li>' . $langs->trans('TicketCategory') . ' : ' . $langs->getLabelFromKey($this->db, 'TicketCategoryShort' . $object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code) . '</li>';
            $message_customer .= '<li>' . $langs->trans('Severity') . ' : ' . $langs->getLabelFromKey($this->db, 'TicketSeverityShort' . $object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code) . '</li>';

            // Extrafields
            if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label'])) {
                foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $value) {
                    $enabled = 1;
                    if ($enabled && isset($extrafields->attributes[$object->table_element]['list'][$key])) {
                        $enabled = (int) dol_eval((string) $extrafields->attributes[$object->table_element]['list'][$key], 1);
                    }
                    $perms = 1;
                    if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$key])) {
                        $perms = (int) dol_eval((string) $extrafields->attributes[$object->table_element]['perms'][$key], 1);
                    }

                    $qualified = true;
                    if (empty($enabled)) {
                        $qualified = false;
                    }
                    if (empty($perms)) {
                        $qualified = false;
                    }

                    if ($qualified) {
                        $message_customer .= '<li>' . $langs->trans($key) . ' : ' . $value . '</li>';
                    }
                }
            }

            $message_customer .= '</ul>';

            $message = $object->message;
            if (!dol_textishtml($message)) {
                $message = dol_nl2br($message);
            }
            $message_customer .= '<p>' . $langs->trans('Message') . ' : <br><br>' . $message . '</p><br>';

            if (getDolGlobalInt('TICKET_ENABLE_PUBLIC_INTERFACE')) {
                $url_public_ticket = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', dol_buildpath('/public/ticket/', 2)) . 'view.php?track_id=' . urlencode($object->track_id);
                $message_customer .= '<p>' . $langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer') . ' : <a href="' . $url_public_ticket . '">' . $url_public_ticket . '</a></p>';
                $message_customer .= '<p>' . $langs->trans('TicketEmailPleaseDoNotReplyToThisEmail') . '</p>';
            } else {
                $message_customer .= '<p>' . $langs->trans('TicketEmailPleaseDoNotReplyToThisEmailNoInterface') . '</p>';
            }
        }

        $from = (getDolGlobalString('MAIN_INFO_SOCIETE_NOM') ? getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . ' ' : '') . '<' . getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM') . '>';

        $this->sendMail($subject, $sendto, $from, $message_customer, $object, $conf, $filepaths, $mimetypes, $filenames, $user);
    }

    /**
     * Compose and send the assignee notification email for a new ticket.
     * Uses the configured email template, or falls back to the original Dolibarr content.
     *
     * @param  string        $sendto    Recipient addresses
     * @param  Ticket        $object    The ticket the email refers to
     * @param  User          $user      Object user
     * @param  Translate     $langs     The translation object
     * @param  array<string> $filepaths File paths
     * @param  array<string> $mimetypes Mime types
     * @param  array<string> $filenames File names
     * @return void
     */
    private function sendAssigneeMessage($sendto, $object, User $user, Translate $langs, $filepaths, $mimetypes, $filenames)
    {
        global $conf, $mysoc;

        $appli = $mysoc->name;

        $templated = $this->getTemplatedContent('SATURNE_TICKET_CREATE_MAIL_MODEL_ASSIGNEE', $object, $user, $langs);
        if ($templated !== null) {
            $subject = $templated['subject'];
            $message = $templated['body'];
        } else {
            // Fallback: original content of interface_50_modTicket_TicketEmail::composeAndSendAssigneeMessage()
            $subject  = '[' . $appli . '] ' . $langs->transnoentities('TicketAssignedToYou');
            $message  = '<p>' . $langs->transnoentities('TicketAssignedEmailBody', $object->track_id, dolGetFirstLastname($user->firstname, $user->lastname)) . '</p>';
            $message .= '<ul><li>' . $langs->trans('Title') . ' : ' . $object->subject . '</li>';
            $message .= '<li>' . $langs->trans('Type') . ' : ' . $object->type_label . '</li>';
            $message .= '<li>' . $langs->trans('Category') . ' : ' . $object->category_label . '</li>';
            $message .= '<li>' . $langs->trans('Severity') . ' : ' . $object->severity_label . '</li>';
            // Extrafields
            if (is_array($object->array_options) && count($object->array_options) > 0) {
                foreach ($object->array_options as $key => $value) {
                    $message .= '<li>' . $langs->trans($key) . ' : ' . $value . '</li>';
                }
            }
            $message .= '</ul>';
            $message .= '<p>' . $langs->trans('Message') . ' : <br>' . $object->message . '</p>';
            $message .= '<p><a href="' . dol_buildpath('/ticket/card.php', 2) . '?track_id=' . $object->track_id . '">' . $langs->trans('SeeThisTicketIntomanagementInterface') . '</a></p>';
            $message  = dol_nl2br($message);
        }

        // The assignee email keeps the original "from = the acting user" behaviour.
        $from = dolGetFirstLastname($user->firstname, $user->lastname) . '<' . $user->email . '>';

        $this->sendMail($subject, $sendto, $from, $message, $object, $conf, $filepaths, $mimetypes, $filenames, $user);
    }
}
