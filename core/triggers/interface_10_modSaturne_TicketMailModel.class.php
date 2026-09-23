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
 * \brief   Trigger that sends the ticket notification emails from a configurable
 *          "Email template" (Modèle d'email) instead of Dolibarr's hardcoded content.
 *
 *          Three events are covered, which is every email the core trigger builds in PHP:
 *          - TICKET_CREATE   : admin, customer and assignee emails
 *          - TICKET_CLOSE    : admin and customer emails
 *          - TICKET_ASSIGNED : assignee and customer emails
 *
 *          It is prefixed "10" on purpose: triggers run in filename order, so this one
 *          runs BEFORE the core ticket email trigger (interface_50_modTicket_TicketEmail).
 *          When no template is configured for a given recipient type, it falls back to the
 *          exact original Dolibarr content, so nothing regresses until templates are created.
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
        $this->description = 'Send ticket notification emails from configurable email templates (Modèles d\'email).';
        $this->version     = '1.1.0';
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

        /** @var Ticket $object */

        switch ($action) {
            case 'TICKET_CREATE':
                return $this->handleTicketCreate($object, $user, $langs, $conf);
            case 'TICKET_CLOSE':
                return $this->handleTicketClose($object, $user, $langs, $conf);
            case 'TICKET_ASSIGNED':
                return $this->handleTicketAssigned($object, $user, $langs, $conf);
        }

        return 0;
    }

    /**
     * Send the three ticket creation emails (admin, assignee, customer).
     *
     * @param  Ticket    $object The created ticket
     * @param  User      $user   Object user
     * @param  Translate $langs  The translation object
     * @param  Conf      $conf   Object conf
     * @return int               0 if nothing was done, 1 if the emails were handled here
     */
    private function handleTicketCreate($object, User $user, Translate $langs, Conf $conf)
    {
        // If another process (e.g. a public interface) already handles the ticket emails,
        // let it do its job and do not take over.
        if (!empty($object->context['disableticketemail'])) {
            return 0;
        }

        dol_syslog("Trigger '" . $this->name . "' for action 'TICKET_CREATE' launched by " . __FILE__ . ". id=" . $object->id);

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
            $this->sendAdminMessage($sendto, $object, $user, $langs, $conf, 'SATURNE_TICKET_CREATE_MAIL_MODEL_ADMIN', 'TicketNewEmailSubjectAdmin', 'TicketNewEmailBodyAdmin', $filepaths, $mimetypes, $filenames);
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
                $this->sendAssigneeMessage($userstat->email, $object, $user, $langs, 'SATURNE_TICKET_CREATE_MAIL_MODEL_ASSIGNEE', $filepaths, $mimetypes, $filenames);
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
                $this->sendCustomerMessage($sendto, $object, $user, $langs, $conf, 'SATURNE_TICKET_CREATE_MAIL_MODEL_CUSTOMER', 'TicketNewEmailSubjectCustomer', 'TicketNewEmailBodyCustomer', 'TicketNewEmailBodyInfosTrackUrlCustomer', $filepaths, $mimetypes, $filenames);
            }
        }

        return 1;
    }

    /**
     * Send the two ticket closing emails (admin, customer).
     *
     * Unlike the creation case, this only takes over when at least one closing template is
     * configured. As long as nothing is configured the core trigger keeps running untouched,
     * so the recipient resolution below can never diverge from Dolibarr's on a default setup.
     *
     * @param  Ticket    $object The closed ticket
     * @param  User      $user   Object user
     * @param  Translate $langs  The translation object
     * @param  Conf      $conf   Object conf
     * @return int               0 if nothing was done, 1 if the emails were handled here
     */
    private function handleTicketClose($object, User $user, Translate $langs, Conf $conf)
    {
        if (!getDolGlobalString('SATURNE_TICKET_CLOSE_MAIL_MODEL_ADMIN') && !getDolGlobalString('SATURNE_TICKET_CLOSE_MAIL_MODEL_CUSTOMER')) {
            return 0;
        }

        // If another process already handles the ticket emails, let it do its job.
        if (!empty($object->context['disableticketemail'])) {
            return 0;
        }

        dol_syslog("Trigger '" . $this->name . "' for action 'TICKET_CLOSE' launched by " . __FILE__ . ". id=" . $object->id);

        $langs->load('ticket');

        // Take over: the core trigger (interface_50) skips both closing emails on this flag.
        $object->context['disableticketemail'] = 1;

        // --- Admin notification email ---
        if (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')) {
            $sendto = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO');
            $this->sendAdminMessage($sendto, $object, $user, $langs, $conf, 'SATURNE_TICKET_CLOSE_MAIL_MODEL_ADMIN', 'TicketCloseEmailSubjectAdmin', 'TicketCloseEmailBodyAdmin');
        }

        // --- Customer email ---
        $sendto = $this->getCloseCustomerRecipients($object, $langs);
        if ($sendto === null) {
            // The posted contact is not one of the ticket contacts, the error is already reported.
            return 0;
        }
        if ($sendto !== '') {
            $this->sendCustomerMessage($sendto, $object, $user, $langs, $conf, 'SATURNE_TICKET_CLOSE_MAIL_MODEL_CUSTOMER', 'TicketCloseEmailSubjectCustomer', 'TicketCloseEmailBodyCustomer', 'TicketCloseEmailBodyInfosTrackUrlCustomer');
        }

        return 1;
    }

    /**
     * Send the two ticket assignment emails (assignee, customer).
     *
     * The core trigger ignores $object->context['disableticketemail'] on TICKET_ASSIGNED, so the
     * only way to stop it from sending its own hardcoded emails is to blank the two options it
     * tests. That is done in memory on $conf only: nothing is written to the database and $conf
     * is rebuilt on the next request. It is also done only once a template is configured, so a
     * default setup never goes through this path.
     *
     * @param  Ticket    $object The assigned ticket
     * @param  User      $user   Object user
     * @param  Translate $langs  The translation object
     * @param  Conf      $conf   Object conf
     * @return int               0 if nothing was done, 1 if the emails were handled here
     */
    private function handleTicketAssigned($object, User $user, Translate $langs, Conf $conf)
    {
        if (!getDolGlobalString('SATURNE_TICKET_ASSIGNED_MAIL_MODEL_ASSIGNEE') && !getDolGlobalString('SATURNE_TICKET_ASSIGNED_MAIL_MODEL_CUSTOMER')) {
            return 0;
        }

        if ($object->fk_user_assign <= 0) {
            return 0;
        }

        dol_syslog("Trigger '" . $this->name . "' for action 'TICKET_ASSIGNED' launched by " . __FILE__ . ". id=" . $object->id);

        $langs->load('ticket');

        // Read the two gating options before neutralising them just below.
        $allMailsDisabled = (bool) getDolGlobalString('TICKET_DISABLE_ALL_MAILS');
        $notifyCustomer   = getDolGlobalString('TICKET_NOTIFY_CUSTOMER_TICKET_ASSIGNED') && empty($object->oldcopy->fk_user_assign);

        $conf->global->TICKET_DISABLE_ALL_MAILS               = 1;
        $conf->global->TICKET_NOTIFY_CUSTOMER_TICKET_ASSIGNED = '';

        // --- Assignee email ---
        if (!$allMailsDisabled && $object->fk_user_assign != $user->id) {
            $userstat = new User($this->db);
            if ($userstat->fetch($object->fk_user_assign) > 0) {
                if (!empty($userstat->email)) {
                    $this->sendAssigneeMessage($userstat->email, $object, $user, $langs, 'SATURNE_TICKET_ASSIGNED_MAIL_MODEL_ASSIGNEE');
                }
            } else {
                $this->setErrorsFromObject($userstat);
            }
        }

        // --- Customer email, telling the requester their ticket is now handled ---
        if ($notifyCustomer) {
            $sendto = $this->getAssignedCustomerRecipients($object);
            if ($sendto !== '') {
                $this->sendCustomerMessage($sendto, $object, $user, $langs, $conf, 'SATURNE_TICKET_ASSIGNED_MAIL_MODEL_CUSTOMER', 'TicketAssignedCustomerEmail', 'TicketAssignedCustomerBody', 'TicketNewEmailBodyInfosTrackUrlCustomer');
            }
        }

        return 1;
    }

    /**
     * Resolve the customer recipients of a closing email, reproducing the core trigger rules.
     *
     * @param  Ticket      $object The closed ticket
     * @param  Translate   $langs  The translation object
     * @return string|null         Comma separated addresses, '' when there is nobody to notify,
     *                             null when the posted contact is not a contact of the ticket
     */
    private function getCloseCustomerRecipients($object, Translate $langs)
    {
        $linked_contacts = $object->listeContact(-1, 'thirdparty');
        $linked_contacts = array_merge($linked_contacts, $object->listeContact(-1, 'internal'));
        if (empty($linked_contacts) && getDolGlobalString('TICKET_NOTIFY_AT_CLOSING') && !empty($object->fk_soc)) {
            $object->fetch_thirdparty();
            $linked_contacts[]['email'] = $object->thirdparty->email;
        }

        $contactid  = empty($object->context['contact_id']) ? 0 : $object->context['contact_id'];
        $contactObj = null;

        if ($contactid > 0) {
            // Only a contact that is really linked to the ticket as external/thirdparty may be used.
            $externalContactIds = array_column(
                array_filter(
                    $linked_contacts,
                    static function ($contact) {
                        // The TICKET_NOTIFY_AT_CLOSING fallback above pushes an entry holding only an email
                        return isset($contact['source']) && in_array($contact['source'], ['external', 'thirdparty']);
                    }
                ),
                'id'
            );

            if (in_array($contactid, $externalContactIds)) {
                $contactObj = new Contact($this->db);
                if ($contactObj->fetch($contactid) <= 0) {
                    $contactObj = null;
                }
            }

            if ($contactObj === null) {
                setEventMessages($langs->trans('Error') . ' : ' . $langs->transnoentities('TicketWrongContact'), [], 'errors');

                return null;
            }
        }

        if ($contactObj !== null && !empty($contactObj->email) && !empty($contactObj->statut)) {
            return $contactObj->email;
        }

        // Sending to every contact, either explicitly or through the mass "close" action.
        if (!empty($linked_contacts) && ($contactid == -2 || (GETPOST('massaction', 'alpha') == 'close' && GETPOST('confirm', 'alpha') == 'yes'))) {
            return implode(', ', array_column($linked_contacts, 'email'));
        }

        return '';
    }

    /**
     * Resolve the customer recipients of an assignment email, reproducing the core trigger rules.
     *
     * @param  Ticket $object The assigned ticket
     * @return string         Comma separated addresses, '' when there is nobody to notify
     */
    private function getAssignedCustomerRecipients($object)
    {
        $emails = [];
        if ($object->origin_email) {
            $emails[] = $object->origin_email;
        }

        foreach ($object->listeContact(-1, 'thirdparty') as $contact) {
            // Guard against a contact being listed twice
            if (!in_array($contact['email'], $emails)) {
                $emails[] = $contact['email'];
            }
        }

        return implode(', ', $emails);
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
     * Compose and send the admin notification email of a ticket event.
     * Uses the configured email template, or falls back to the original Dolibarr content.
     *
     * @param  string        $sendto     Recipient addresses
     * @param  Ticket        $object     The ticket the email refers to
     * @param  User          $user       Object user
     * @param  Translate     $langs      The translation object
     * @param  Conf          $conf       Object conf
     * @param  string        $constName  Name of the config constant holding the template label
     * @param  string        $subjectKey Language key of the fallback subject
     * @param  string        $bodyKey    Language key of the fallback body intro
     * @param  array<string> $filepaths  File paths
     * @param  array<string> $mimetypes  Mime types
     * @param  array<string> $filenames  File names
     * @return void
     */
    private function sendAdminMessage($sendto, $object, User $user, Translate $langs, Conf $conf, $constName, $subjectKey, $bodyKey, $filepaths = [], $mimetypes = [], $filenames = [])
    {
        global $mysoc;

        $appli = $mysoc->name;

        $templated = $this->getTemplatedContent($constName, $object, $user, $langs);
        if ($templated !== null) {
            $subject       = $templated['subject'];
            $message_admin = $templated['body'];
        } else {
            // Fallback: original content of interface_50_modTicket_TicketEmail::composeAndSendAdminMessage()
            $subject        = '[' . $appli . '] ' . $langs->transnoentities($subjectKey, $object->ref, $object->track_id);
            $message_admin  = $langs->transnoentities($bodyKey, $object->track_id) . '<br>';
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
     * Compose and send the customer notification email of a ticket event.
     * Uses the configured email template, or falls back to the original Dolibarr content.
     *
     * @param  string        $sendto       Recipient addresses
     * @param  Ticket        $object       The ticket the email refers to
     * @param  User          $user         Object user
     * @param  Translate     $langs        The translation object
     * @param  Conf          $conf         Object conf
     * @param  string        $constName    Name of the config constant holding the template label
     * @param  string        $subjectKey   Language key of the fallback subject
     * @param  string        $bodyKey      Language key of the fallback body intro
     * @param  string        $seeTicketKey Language key of the fallback public interface link label
     * @param  array<string> $filepaths    File paths
     * @param  array<string> $mimetypes    Mime types
     * @param  array<string> $filenames    File names
     * @return void
     */
    private function sendCustomerMessage($sendto, $object, User $user, Translate $langs, Conf $conf, $constName, $subjectKey, $bodyKey, $seeTicketKey, $filepaths = [], $mimetypes = [], $filenames = [])
    {
        global $extrafields, $mysoc;

        $appli = $mysoc->name;

        $templated = $this->getTemplatedContent($constName, $object, $user, $langs);
        if ($templated !== null) {
            $subject          = $templated['subject'];
            $message_customer = $templated['body'];
        } else {
            // Fallback: original content of interface_50_modTicket_TicketEmail::composeAndSendCustomerMessage()
            $subject           = '[' . $appli . '] ' . $langs->transnoentities($subjectKey);
            $message_customer  = $langs->transnoentities($bodyKey, $object->track_id) . '<br>';
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
                $message_customer .= '<p>' . $langs->trans($seeTicketKey) . ' : <a href="' . $url_public_ticket . '">' . $url_public_ticket . '</a></p>';
                $message_customer .= '<p>' . $langs->trans('TicketEmailPleaseDoNotReplyToThisEmail') . '</p>';
            } else {
                $message_customer .= '<p>' . $langs->trans('TicketEmailPleaseDoNotReplyToThisEmailNoInterface') . '</p>';
            }
        }

        $from = (getDolGlobalString('MAIN_INFO_SOCIETE_NOM') ? getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . ' ' : '') . '<' . getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM') . '>';

        $this->sendMail($subject, $sendto, $from, $message_customer, $object, $conf, $filepaths, $mimetypes, $filenames, $user);
    }

    /**
     * Compose and send the assignee notification email of a ticket event.
     * Uses the configured email template, or falls back to the original Dolibarr content.
     *
     * @param  string        $sendto    Recipient addresses
     * @param  Ticket        $object    The ticket the email refers to
     * @param  User          $user      Object user
     * @param  Translate     $langs     The translation object
     * @param  string        $constName Name of the config constant holding the template label
     * @param  array<string> $filepaths File paths
     * @param  array<string> $mimetypes Mime types
     * @param  array<string> $filenames File names
     * @return void
     */
    private function sendAssigneeMessage($sendto, $object, User $user, Translate $langs, $constName, $filepaths = [], $mimetypes = [], $filenames = [])
    {
        global $conf, $mysoc;

        $appli = $mysoc->name;

        $templated = $this->getTemplatedContent($constName, $object, $user, $langs);
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
