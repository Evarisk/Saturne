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
 * \file    lib/ticket.lib.php
 * \ingroup saturne
 * \brief   Library of the shortcuts added to the core ticket card (ticket/card.php)
 *
 * The status badge of the banner opens the list of statuses, and a modal traces an event that happened
 * outside Dolibarr (an email answered from a mailbox, a phone call) without leaving the card.
 */

/**
 * Action posted by the status list of the badge, handled by the doActions hook before the core actions of the card
 */
const SATURNE_TICKET_STATUS_ACTION = 'set_ticket_status';

/**
 * Action posted by the quick event modal, handled by the doActions hook before the core actions of the card
 */
const SATURNE_TICKET_QUICK_EVENT_ACTION = 'add_ticket_quick_event';

/**
 * Tell whether the user may change the status of a ticket from the card.
 *
 * Dolibarr 24 checks 'manage_advance' instead of 'write' when advanced permissions are on, Dolibarr 23 checks
 * 'write' only: both are required, so that the badge never does more than the status buttons of the core allow.
 * External users never get the status buttons of the core either.
 *
 * @param  User $user Current user
 * @return bool       True if the badge can be clicked
 */
function saturne_ticket_can_manage_status(User $user): bool
{
    if (!empty($user->socid) || !$user->hasRight('ticket', 'write')) {
        return false;
    }

    return !getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->hasRight('ticket', 'manage_advance');
}

/**
 * Return the statuses a ticket can switch to.
 *
 * The core already hides the suspended status in labelStatusShort when it is disabled. A closed ticket can only be
 * reopened: Ticket::close() refuses to close it twice.
 *
 * @param  Ticket    $ticket Ticket shown on the card
 * @param  Translate $langs  Translation object
 * @return array<int, array{label: string, picto: string}> Choices keyed by status
 */
function saturne_ticket_get_status_choices(Ticket $ticket, Translate $langs): array
{
    $isClosed = in_array((int) $ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELED], true);

    $choices = [];
    foreach ($ticket->labelStatusShort as $status => $labelKey) {
        $status = (int) $status;
        if ($status === (int) $ticket->status || ($isClosed && in_array($status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELED], true))) {
            continue;
        }

        $choices[$status] = [
            'label' => $langs->trans($labelKey),
            'picto' => $ticket->LibStatut($status, 3, 1),
        ];
    }

    return $choices;
}

/**
 * Switch a ticket to a status, the way the status actions of ticket/card.php do.
 *
 * Closed and abandoned go through Ticket::close(), like confirm_close and confirm_abandon: it fills the closing date
 * and runs TICKET_CLOSE, which sends the closing emails, where setStatut() would not. The other statuses go through
 * setStatut() with TICKET_MODIFY, like confirm_set_status. As it does on Dolibarr 24, the read date is set when the
 * ticket leaves unread and cleared when it goes back to it; written directly, as Ticket::setReadDate() is missing
 * from Dolibarr 23.
 *
 * @param  Ticket    $ticket Ticket shown on the card
 * @param  User      $user   Current user
 * @param  Translate $langs  Translation object
 * @param  int       $status New status, one of saturne_ticket_get_status_choices()
 * @param  string[]  $errors Errors, filled when the status is not changed
 * @return int               1 on success, -1 on error
 */
function saturne_ticket_set_status(Ticket $ticket, User $user, Translate $langs, int $status, array &$errors): int
{
    $errors = [];
    if (!array_key_exists($status, saturne_ticket_get_status_choices($ticket, $langs))) {
        $errors[] = $langs->trans('ErrorBadValueForParameter', (string) $status, 'new_status');
        return -1;
    }

    if ($status === Ticket::STATUS_CLOSED || $status === Ticket::STATUS_CANCELED) {
        if ($status === Ticket::STATUS_CLOSED) {
            // Same recipients as the default answer of the closing confirmation of the core
            $ticket->context['contact_id'] = getDolGlobalString('TICKET_NOTIFY_AT_CLOSING') ? -2 : -3;
        }
        $result = $ticket->close($user, $status === Ticket::STATUS_CANCELED ? 1 : 0);
    } else {
        $result = $ticket->setStatut($status, null, '', 'TICKET_MODIFY');
        if ($result > 0) {
            if ($status === Ticket::STATUS_NOT_READ) {
                $result = $ticket->setValueFrom('date_read', '', '', null, 'date', '', $user);
            } elseif (empty($ticket->date_read)) {
                $result = $ticket->setValueFrom('date_read', dol_now(), '', null, 'date', '', $user);
            }
        }
    }

    if ($result <= 0) {
        // Ticket reports some errors as translation keys
        $errors = array_map([$langs, 'trans'], array_merge(array_filter([$ticket->error]), $ticket->errors));
        if (empty($errors)) {
            $errors[] = $langs->trans('Error');
        }
        return -1;
    }

    return 1;
}

/**
 * Tell whether the user may trace an event on a ticket from the card.
 *
 * @param  User   $user   Current user
 * @param  Ticket $ticket Ticket shown on the card
 * @return bool           True if the quick event button and modal are shown
 */
function saturne_ticket_can_add_quick_event(User $user, Ticket $ticket): bool
{
    return isModEnabled('agenda') && $ticket->id > 0 && $user->hasRight('agenda', 'myactions', 'create');
}

/**
 * Return the event types offered by the quick event modal.
 *
 * @param  Translate $langs Translation object
 * @return array<string, array{label: string, icon: string}> Types keyed by c_actioncomm code
 */
function saturne_ticket_get_quick_event_types(Translate $langs): array
{
    return [
        'AC_EMAIL' => ['label' => $langs->trans('TicketQuickEventTypeEmail'), 'icon' => 'fas fa-envelope'],
        'AC_TEL'   => ['label' => $langs->trans('TicketQuickEventTypeCall'), 'icon' => 'fas fa-phone-alt'],
        'AC_RDV'   => ['label' => $langs->trans('TicketQuickEventTypeMeeting'), 'icon' => 'fas fa-handshake'],
        'AC_OTH'   => ['label' => $langs->trans('TicketQuickEventTypeOther'), 'icon' => 'fas fa-ellipsis-h'],
    ];
}

/**
 * Trace an event on a ticket: linked to the ticket, its thirdparty and its project, owned by the current user.
 *
 * A past event is a trace, like the emails Dolibarr records itself: it gets no progress, so that it never shows
 * up in the events to do. A future one, typically a meeting, is an event to do.
 *
 * @param  Ticket    $ticket      Ticket the event is about
 * @param  User      $user        Current user, owner of the event
 * @param  Translate $langs       Translation object
 * @param  string    $typeCode    Code of the event type, one of saturne_ticket_get_quick_event_types()
 * @param  int       $date        Date of the event, timestamp
 * @param  string    $label       Label of the event
 * @param  string    $description Description of the event, for instance the email that was sent
 * @param  string[]  $errors      Errors, filled when the event is not created
 * @return int                    Id of the event, <0 on error
 */
function saturne_ticket_add_quick_event(Ticket $ticket, User $user, Translate $langs, string $typeCode, int $date, string $label, string $description, array &$errors): int
{
    $errors = [];
    if (!array_key_exists($typeCode, saturne_ticket_get_quick_event_types($langs))) {
        $errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type'));
    }
    if ($date <= 0) {
        $errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Date'));
    }
    if (trim($label) === '') {
        $errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Label'));
    }
    if (!empty($errors)) {
        return -1;
    }

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

    $actionComm = new ActionComm($ticket->db);

    $actionComm->type_code    = $typeCode;
    $actionComm->label        = $label;
    $actionComm->note_private = $description;
    $actionComm->datep        = $date;
    $actionComm->datef        = $date;
    $actionComm->percentage   = $date > dol_now() ? 0 : -1;
    $actionComm->userownerid  = $user->id;
    $actionComm->socid        = (int) $ticket->fk_soc;
    $actionComm->fk_project   = (int) $ticket->fk_project;
    $actionComm->elementtype  = $ticket->element;
    $actionComm->fk_element   = $ticket->id;

    $result = $actionComm->create($user);
    if ($result <= 0) {
        // ActionComm reports some errors as translation keys
        $errors = array_map([$langs, 'trans'], array_merge(array_filter([$actionComm->error]), $actionComm->errors));
        if (empty($errors)) {
            $errors[] = $langs->trans('Error');
        }
        return -1;
    }

    return $result;
}
