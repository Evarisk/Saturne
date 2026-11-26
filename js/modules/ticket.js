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
 * \file    js/modules/ticket.js
 * \ingroup saturne
 * \brief   Shortcuts of the core ticket card (ticket/card.php): the status picked in the list of the badge and the
 *          event typed in the quick event modal are saved without leaving the page, then the parts of the card they
 *          change are read again from the server and swapped in place.
 */

/**
 * Init ticket JS
 *
 * @since   23.11.0
 * @version 23.11.0
 */
window.saturne.ticket = {};

/**
 * Parts of the card that follow the status: the badge, the properties (read and closing dates, progress, status
 * buttons of the core), the action buttons (close, abandon, reopen) and the lists of events
 *
 * @type {string[]}
 */
window.saturne.ticket.statusZones = [
    '.arearef .statusref',
    '.tabBar > .fichecenter',
    '.fiche > .tabsAction',
    '.fiche > .fichecenter > .fichehalfright',
    '.fiche ul.timeline'
];

/**
 * Parts of the card listing the events of the ticket
 *
 * @type {string[]}
 */
window.saturne.ticket.eventZones = [
    '.fiche > .fichecenter > .fichehalfright',
    '.fiche ul.timeline'
];

/**
 * Ticket init
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @return {void}
 */
window.saturne.ticket.init = function() {
    window.saturne.ticket.event();
};

/**
 * Ticket events
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @return {void}
 */
window.saturne.ticket.event = function() {
    $(document).on('submit', '.saturne-ticket-status form', window.saturne.ticket.setStatus);
    $(document).on('submit', '.saturne-ticket-quick-event-form', window.saturne.ticket.addQuickEvent);
};

/**
 * Save the status picked in the list of the badge
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {Event} event Submit of the form of a status
 * @return {void}
 */
window.saturne.ticket.setStatus = function(event) {
    event.preventDefault();

    const form     = $(this);
    const dropdown = form.closest('.saturne-ticket-status');

    dropdown.removeClass('dropdown-active');
    window.saturne.loader.display(dropdown);

    window.saturne.ticket.post(form, dropdown.data('error'), function(response) {
        // The badge is swapped with the rest: its loader goes with it
        window.saturne.ticket.refresh(form.attr('action'), window.saturne.ticket.statusZones, function() {
            $.jnotify(response.message, 3000, false);
        });
    }, function() {
        window.saturne.loader.remove(dropdown);
    });
};

/**
 * Save the event typed in the quick event modal
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {Event} event Submit of the form of the modal
 * @return {void}
 */
window.saturne.ticket.addQuickEvent = function(event) {
    event.preventDefault();

    const form   = $(this);
    const button = form.find('button[type="submit"]');
    if (button.hasClass('button-disable')) {
        return;
    }

    window.saturne.loader.display(button);
    button.toggleClass('button-blue button-disable');
    const release = function() {
        window.saturne.loader.remove(button);
        button.toggleClass('button-blue button-disable');
    };

    window.saturne.ticket.post(form, form.data('error'), function(response) {
        release();
        form.find('.wpeo-modal').removeClass('modal-active');
        window.saturne.ticket.resetQuickEvent(form);
        $.jnotify(response.message, 3000, false);
        window.saturne.ticket.refresh(form.attr('action'), window.saturne.ticket.eventZones);
    }, release);
};

/**
 * Empty the quick event modal for the next event, dated now again
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {jQuery} form Form of the modal
 * @return {void}
 */
window.saturne.ticket.resetQuickEvent = function(form) {
    form[0].reset();
    // The "Now" button of the core date picker
    form.find('#quickeventdateButtonNow').trigger('click');
};

/**
 * Post a form of the card to its Saturne action, which answers in JSON
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {jQuery}   form         Form to post
 * @param  {string}   errorMessage Message shown when the answer carries none
 * @param  {Function} onSuccess    Called with the answer when the action succeeded
 * @param  {Function} onError      Called once the error is shown
 * @return {void}
 */
window.saturne.ticket.post = function(form, errorMessage, onSuccess, onError) {
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            if (response && response.error === false) {
                onSuccess(response);
                return;
            }
            window.saturne.ticket.notifyErrors(response, errorMessage);
            onError();
        },
        error: function(xhr) {
            window.saturne.ticket.notifyErrors(xhr.responseJSON, errorMessage);
            onError();
        }
    });
};

/**
 * Show the errors of an answer, or the fallback message when it has none (a page instead of JSON, a network error)
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {Object|undefined} response Answer of the action
 * @param  {string}           fallback Message shown when the answer carries none
 * @return {void}
 */
window.saturne.ticket.notifyErrors = function(response, fallback) {
    const messages = response && Array.isArray(response.messages) && response.messages.length ? response.messages : [fallback];
    $.jnotify(messages.join('<br>'), 'error', true);
};

/**
 * Read the card again and swap the given parts in place
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {string}   url      URL of the card
 * @param  {string[]} zones    Selectors of the parts to swap; a part missing on either side is left as it is
 * @param  {Function} callback Called once the parts are swapped
 * @return {void}
 */
window.saturne.ticket.refresh = function(url, zones, callback) {
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'html',
        success: function(html) {
            // Parsed without its scripts: the card already ran them on its first load
            const page     = $('<div>').append($.parseHTML(html));
            const replaced = [];

            zones.forEach(function(selector) {
                const current = $(selector);
                const fresh   = page.find(selector);
                if (current.length === 0 || current.length !== fresh.length) {
                    return;
                }
                current.each(function(index) {
                    const element = fresh.eq(index);
                    $(this).replaceWith(element);
                    replaced.push(element[0]);
                });
            });

            // Dolibarr may renew the token on every call: the forms left in place take the new one
            const token = page.find('input[name="token"]').first().val();
            if (token) {
                $('input[name="token"]').val(token);
            }

            window.saturne.ticket.initNewContent($(replaced));
            // Lets a module apply again what it does to these parts on page load
            $(document).trigger('saturne:ticketCardRefreshed', [$(replaced)]);

            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function() {
            // The change is saved: show it, even at the cost of a reload
            window.location.reload();
        }
    });
};

/**
 * Bind the tooltips of the swapped parts, as the core does on page load
 *
 * @since   23.11.0
 * @version 23.11.0
 *
 * @param  {jQuery} elements Swapped parts
 * @return {void}
 */
window.saturne.ticket.initNewContent = function(elements) {
    if (elements.length === 0) {
        return;
    }

    if (window.Dolibarr && typeof window.Dolibarr.initNewContent === 'function') {
        // Dolibarr 24
        window.Dolibarr.initNewContent(elements, false);
    } else if ($.fn.tooltip) {
        elements.find('.classfortooltip').tooltip({
            tooltipClass: 'mytooltip',
            content: function() {
                return $(this).prop('title');
            }
        });
    }
};
