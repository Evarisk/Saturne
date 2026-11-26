<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/ticket/ticket_quick_event_button.tpl.php
 * \ingroup saturne
 * \brief   Template of the "Add an event" button of the ticket card, opening ticket_quick_event_modal.tpl.php
 */

/**
 * The following vars must be defined :
 * Global : $langs
 */ ?>

<span class="butAction modal-open">
    <input type="hidden" class="modal-options" data-modal-to-open="saturne-ticket-quick-event">
    <i class="fas fa-calendar-plus pictofixedwidth"></i><?php echo $langs->trans('TicketQuickEvent'); ?>
</span>
