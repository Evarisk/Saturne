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

    public function testClosedAndAbandonedGoThroughTheCloseActions(): void
    {
        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_READ), $this->langs());

        $this->assertSame('confirm_close', $choices[Ticket::STATUS_CLOSED]['action']);
        $this->assertSame('confirm_abandon', $choices[Ticket::STATUS_CANCELED]['action']);
        $this->assertSame('confirm_set_status', $choices[Ticket::STATUS_IN_PROGRESS]['action']);
        $this->assertNull($choices[Ticket::STATUS_CANCELED]['contactid']);
    }

    public function testClosingNotifiesTheContactsAsTheCoreConfirmationDoesByDefault(): void
    {
        global $conf;

        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_READ), $this->langs());
        $this->assertSame(-3, $choices[Ticket::STATUS_CLOSED]['contactid']);

        $conf->global->TICKET_NOTIFY_AT_CLOSING = 1;
        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_READ), $this->langs());
        $this->assertSame(-2, $choices[Ticket::STATUS_CLOSED]['contactid']);
    }

    public function testClosedTicketCanOnlyBeReopened(): void
    {
        $choices = saturne_ticket_get_status_choices($this->ticketIn(Ticket::STATUS_CLOSED), $this->langs());

        $this->assertSame([0, 1, 2, 3, 5], array_keys($choices));
        $this->assertSame(['confirm_set_status'], array_values(array_unique(array_column($choices, 'action'))));
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
