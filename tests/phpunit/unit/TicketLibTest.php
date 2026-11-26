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

namespace Saturne\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use Ticket;
use Translate;
use User;

/**
 * Tests for lib/ticket.lib.php
 *
 * The status badge of the ticket card must only offer what the core actions of ticket/card.php accept,
 * and send closed and abandoned through the close actions.
 */
class TicketLibTest extends TestCase
{
    /**
     * Load ticket.lib.php once for the entire class.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../lib/ticket.lib.php';
    }

    /**
     * Each test starts from an empty constant set.
     */
    protected function setUp(): void
    {
        global $conf;

        $conf->global = new stdClass();
    }

    /**
     * User holding the given ticket rights
     *
     * @param  string[] $ticketRights Rights of the ticket module, e.g. ['read', 'write']
     * @return User
     */
    private function userWith(array $ticketRights): User
    {
        global $db;

        // The arguments of the core constructors, which the stubs of the bootstrap ignore
        $user                 = new User($db);
        $user->rights         = new stdClass();
        $user->rights->ticket = new stdClass();
        foreach ($ticketRights as $right) {
            $user->rights->ticket->$right = 1;
        }

        return $user;
    }

    /**
     * Ticket in the given status
     *
     * @param  int    $status Status of the ticket
     * @return Ticket
     */
    private function ticketIn(int $status): Ticket
    {
        global $db;

        $ticket         = new Ticket($db);
        $ticket->id     = 81;
        $ticket->status = $status;

        return $ticket;
    }

    /**
     * Translation object returning the keys
     *
     * @return Translate
     */
    private function langs(): Translate
    {
        global $conf;

        return new Translate('', $conf);
    }

    // ─── saturne_ticket_can_manage_status ─────────────────────────────────────

    public function testCanManageStatusNeedsTheWriteRight(): void
    {
        $this->assertFalse(saturne_ticket_can_manage_status($this->userWith(['read'])));
        $this->assertTrue(saturne_ticket_can_manage_status($this->userWith(['read', 'write'])));
    }

    public function testCanManageStatusRefusesExternalUsers(): void
    {
        $user        = $this->userWith(['read', 'write']);
        $user->socid = 12;

        $this->assertFalse(saturne_ticket_can_manage_status($user));
    }

    public function testCanManageStatusWithAdvancedPermissionsNeedsManageAdvanceToo(): void
    {
        global $conf;

        $conf->global->MAIN_USE_ADVANCED_PERMS = 1;

        $this->assertFalse(saturne_ticket_can_manage_status($this->userWith(['read', 'write'])));
        $this->assertFalse(saturne_ticket_can_manage_status($this->userWith(['read', 'manage_advance'])));
        $this->assertTrue(saturne_ticket_can_manage_status($this->userWith(['read', 'write', 'manage_advance'])));
    }

    // ─── saturne_ticket_get_status_choices ────────────────────────────────────

    public function testStatusChoicesLeaveOutTheCurrentAndTheSuspendedStatus(): void
    {
        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_IN_PROGRESS), $this->langs());

        $this->assertSame([0, 1, 2, 5, 8, 9], array_keys($choices));
    }

    public function testStatusChoicesOfferTheSuspendedStatusWhenItIsEnabled(): void
    {
        global $conf;

        $conf->global->TICKET_INCLUDE_SUSPENDED_STATUS = 1;

        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_IN_PROGRESS), $this->langs());

        $this->assertArrayHasKey(Ticket::STATUS_WAITING, $choices);
    }

    public function testClosedTicketCanOnlyBeReopened(): void
    {
        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_CLOSED), $this->langs());

        $this->assertSame([0, 1, 2, 3, 5], array_keys($choices));
    }

    // ─── saturne_ticket_set_status ────────────────────────────────────────────

    /**
     * Ticket recording the calls the status change makes, in its context: the methods of the core write in the database
     *
     * @param  int    $status Status of the ticket
     * @param  int    $result Result returned by every recorded method
     * @return Ticket
     */
    private function recordingTicket(int $status, int $result = 1): Ticket
    {
        global $db;

        $ticket = new class ($db) extends Ticket {
            /**
             * @param  User $user User that closes
             * @param  int  $mode 0 = closed, 1 = abandoned
             * @return int
             */
            public function close(User $user, $mode = 0)
            {
                $this->context['test_calls'][] = ['close', $mode, $this->context['contact_id'] ?? null];
                return $this->context['test_result'];
            }

            /**
             * @param  int         $status      New status
             * @param  int|null    $elementId   Id of the element
             * @param  string      $elementType Type of the element
             * @param  string      $trigkey     Trigger key
             * @param  string      $fieldstatus Status field
             * @return int
             */
            public function setStatut($status, $elementId = null, $elementType = '', $trigkey = '', $fieldstatus = 'fk_statut')
            {
                $this->context['test_calls'][] = ['setStatut', $status, $trigkey];
                return $this->context['test_result'];
            }

            /**
             * @param  string      $field         Field to update
             * @param  mixed       $value         New value
             * @param  string      $table         Table
             * @param  int|null    $id            Id of the row
             * @param  string      $format        Format of the value
             * @param  string      $id_field      Id field
             * @param  User|string $fuser         User
             * @param  string      $trigkey       Trigger key
             * @param  string      $fk_user_field User field
             * @return int
             */
            public function setValueFrom($field, $value, $table = '', $id = null, $format = '', $id_field = '', $fuser = null, $trigkey = '', $fk_user_field = 'fk_user_modif')
            {
                // The read date set to now is only told apart from an empty one
                $this->context['test_calls'][] = ['setValueFrom', $field, $value === '' ? '' : 'now', $format];
                return $this->context['test_result'];
            }
        };

        $ticket->id                     = 81;
        $ticket->status                 = $status;
        $ticket->context['test_calls']  = [];
        $ticket->context['test_result'] = $result;

        return $ticket;
    }

    public function testSetStatusClosesThroughCloseWithTheDefaultRecipientsOfTheCore(): void
    {
        global $conf;

        $errors = [];
        $ticket = $this->recordingTicket(Ticket::STATUS_IN_PROGRESS);
        $this->assertSame(1, saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_CLOSED, $errors));
        $this->assertSame([['close', 0, -3]], $ticket->context['test_calls']);

        $conf->global->TICKET_NOTIFY_AT_CLOSING = 1;
        $ticket = $this->recordingTicket(Ticket::STATUS_IN_PROGRESS);
        saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_CLOSED, $errors);
        $this->assertSame([['close', 0, -2]], $ticket->context['test_calls']);
    }

    public function testSetStatusAbandonsThroughClose(): void
    {
        $errors = [];
        $ticket = $this->recordingTicket(Ticket::STATUS_READ);
        saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_CANCELED, $errors);

        $this->assertSame([['close', 1, null]], $ticket->context['test_calls']);
    }

    public function testSetStatusSetsTheReadDateOnceLikeTheCore(): void
    {
        $errors = [];
        $ticket = $this->recordingTicket(Ticket::STATUS_NOT_READ);
        saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_IN_PROGRESS, $errors);
        $this->assertSame([['setStatut', Ticket::STATUS_IN_PROGRESS, 'TICKET_MODIFY'], ['setValueFrom', 'date_read', 'now', 'date']], $ticket->context['test_calls']);

        // Already read: the first read date is kept
        $ticket            = $this->recordingTicket(Ticket::STATUS_READ);
        $ticket->date_read = 1795000000;
        saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_IN_PROGRESS, $errors);
        $this->assertSame([['setStatut', Ticket::STATUS_IN_PROGRESS, 'TICKET_MODIFY']], $ticket->context['test_calls']);
    }

    public function testSetStatusBackToUnreadClearsTheReadDate(): void
    {
        $errors = [];
        $ticket = $this->recordingTicket(Ticket::STATUS_READ);
        saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_NOT_READ, $errors);

        $this->assertSame([['setStatut', Ticket::STATUS_NOT_READ, 'TICKET_MODIFY'], ['setValueFrom', 'date_read', '', 'date']], $ticket->context['test_calls']);
    }

    public function testSetStatusRefusesAStatusNotOffered(): void
    {
        $errors = [];
        $ticket = $this->recordingTicket(Ticket::STATUS_CLOSED);

        $this->assertSame(-1, saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_CANCELED, $errors));
        $this->assertSame([], $ticket->context['test_calls']);
        $this->assertCount(1, $errors);
    }

    public function testSetStatusReportsTheErrorOfTheTicket(): void
    {
        $errors        = [];
        $ticket        = $this->recordingTicket(Ticket::STATUS_READ, -1);
        $ticket->error = 'ErrorTicketLocked';

        $this->assertSame(-1, saturne_ticket_set_status($ticket, $this->userWith(['write']), $this->langs(), Ticket::STATUS_CLOSED, $errors));
        $this->assertSame(['ErrorTicketLocked'], $errors);
    }

    // ─── saturne_ticket_add_quick_event ───────────────────────────────────────

    public function testQuickEventRefusesMissingFieldsBeforeCreatingAnything(): void
    {
        $errors = [];
        $result = saturne_ticket_add_quick_event($this->ticketIn(Ticket::STATUS_READ), $this->userWith([]), $this->langs(), 'AC_UNKNOWN', 0, '  ', '', $errors);

        $this->assertSame(-1, $result);
        $this->assertCount(3, $errors);
    }
}
