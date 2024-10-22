/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/dashboard.js
 * \ingroup saturne
 * \brief   JavaScript dashboard file for module Saturne.
 */

/**
 * Init dashboard JS.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @type {Object}
 */
window.saturne.dashboard = {};

/**
 * Dashboard init.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.init = function() {
    window.saturne.dashboard.event();
};

/**
 * Dashboard event.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.event = function() {
    $(document).on('change', '.add-dashboard-widget', window.saturne.dashboard.addDashBoardInfo);
    $(document).on('click', '.close-dashboard-widget', window.saturne.dashboard.closeDashBoardInfo);
    $(document).on('click', '.select-dataset-dashboard-info', window.saturne.dashboard.selectDatasetDashboardInfo);

    $(document).on('click', '#export-csv', window.saturne.dashboard.exportCSV);
};

/**
 * Add widget dashboard info.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.addDashBoardInfo = function() {
    const dashboardWidgetForm = document.getElementById('dashBoardForm');
    const formData            = new FormData(dashboardWidgetForm);
    let dashboardWidgetName   = formData.get('boxcombo');
    let token                 = window.saturne.toolbox.getToken();
    let querySeparator        = window.saturne.toolbox.getQuerySeparator(document.URL);

    $.ajax({
        url: document.URL + querySeparator + 'action=adddashboardinfo&token=' + token,
        type: "POST",
        processData: false,
        data: JSON.stringify({
            dashboardWidgetName: dashboardWidgetName
        }),
        contentType: false,
        success: function(resp) {
          $('.fichecenter').replaceWith($(resp).find('.fichecenter'));
        },
        error: function() {}
    });
};

/**
 * Close widget dashboard info.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.closeDashBoardInfo = function() {
    let box = $(this);
    let dashboardWidgetName = box.data('widgetname');
    let token = window.saturne.toolbox.getToken();
    let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

    $.ajax({
        url: document.URL + querySeparator + 'action=closedashboardinfo&token=' + token,
        type: "POST",
        processData: false,
        data: JSON.stringify({
            dashboardWidgetName: dashboardWidgetName
        }),
        contentType: false,
        success: function(resp) {
            box.closest('.wpeo-infobox').fadeOut(400);
            $('.add-widget-box').attr('style', '');
            $('.add-widget-box').html($(resp).find('.add-widget-box').children())
        },
        error: function() {}
    });
};

/**
 * Select dataset dashboard info.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.selectDatasetDashboardInfo = function() {
    let userID = $('#search_userid').val();
    let year   = $('#search_year').val();
    let month  = $('#search_month').val();

    let token          = window.saturne.toolbox.getToken();
    let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

    window.saturne.loader.display($('.fichecenter'));

    $.ajax({
        url: document.URL + querySeparator + 'token=' + token + '&search_userid=' + userID + '&search_year=' + year + '&search_month=' + month,
        type: "POST",
        processData: false,
        contentType: false,
        success: function(resp) {
            $('.fichecenter').replaceWith($(resp).find('.fichecenter'));
        },
        error: function() {}
    });
};

/**
 * Export CSV.
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.exportCSV = function(e) {
  e.preventDefault();
  let button         = $(this);
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  let graph = button.parent().find('input[name="graph"]').val();

  window.saturne.loader.display(button);

  $.ajax({
    url: document.URL + querySeparator + 'action=generate_csv&token=' + token,
    type: "POST",
    data: graph,
    success: function(resp) {
      let url = window.URL.createObjectURL(new Blob([resp], { type: 'text/csv' }));
      let graphName = button.data('graph-name').replace(/ /g, '_');
      let date = new Date();
      let dateFormat = date.getFullYear() + ('0' + (date.getMonth() + 1)).slice(-2) + ('0' + date.getDate()).slice(-2);
      let fileName = dateFormat + '_' +  graphName + '.csv';
      button.after($('<a href="' + url + '" download="' + fileName + '"></a>'));
      button.next()[0].click();
      window.saturne.loader.remove(button);
    },
    error: function() {}
  })

}
