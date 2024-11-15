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
    $(document).on('change', '#disabledWidget, #disabledGraph', window.saturne.dashboard.addDashBoardInfo);
    $(document).on('click', '.select-dataset-dashboard-info', window.saturne.dashboard.selectDatasetDashboardInfo);
    $(document).on('click', '#dashboard-graph-filter', window.saturne.dashboard.openGraphFilter);
    $(document).on('click', '#dashboard-graph-filter-submit', window.saturne.dashboard.selectDashboardFilter);
    $(document).on('click', '#dashboard-close-item', window.saturne.dashboard.closeDashboardItem);
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

    let loaderElem    = $(this).data('id-load') ? $('#' + $(this).data('id-load')) : $(this);

    window.saturne.dashboard.configureDashboard({[$(this).data('item-type')]: $(this).val()}, loaderElem, $(this).data('id-refresh'));
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
 * Show dashboard filters
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.openGraphFilter = function() {
  let refId         = $(this).data('ref-id');
  let filterSection = $('#' + refId);

  if (filterSection.is(':hidden')) {
    filterSection.css('display', 'flex');
    filterSection.hide();
    filterSection.fadeIn(800);
  } else {
    filterSection.fadeOut(800);
  }
};

/**
 * Select dashboard filter
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.selectDashboardFilter = function(e) {
  let button = $(e.target).closest('button');

  let inputs = $("#" + button.data('ref-id')).find('input, select');
  let values = {};
  inputs.each(function() {
    values[$(this).attr('name')] = $(this).val();
  });

  let data = {
    graphFilters: values,
  }

  window.saturne.dashboard.configureDashboard(data, $("#" + button.data('ref-id')), button.data('item-refresh'));
};

/**
 * Close dashboard item
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.saturne.dashboard.closeDashboardItem = function() {
  let box = $(this);
  let itemName      = box.data('item-name');
  let itemType      = box.data('item-type');
  let itemRefreshId = box.data('item-refresh');
  let itemSuppress  = $('#' + box.data('item-suppress'));

  let data = {
    [itemType]: itemName,
  };
  window.saturne.dashboard.configureDashboard(data, box, itemRefreshId, itemSuppress);
};

/**
 * Configure dashboard
 *
 * @param data
 * @param loaderElem
 * @param refreshElemId
 * @param itemSuppress
 */
window.saturne.dashboard.configureDashboard = function(data, loaderElem, refreshElemId = null, itemSuppress = null) {

  let token = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  window.saturne.loader.display(loaderElem);

  $.ajax({
    url: document.URL + querySeparator + 'action=dashboardfilter&token=' + token,
    type: "POST",
    processData: false,
    data: JSON.stringify(data),
    contentType: false,
    success: function(resp) {
      window.saturne.loader.remove(loaderElem);

      if (refreshElemId) {
        let refreshElem = $('#' + refreshElemId);
        refreshElem.fadeOut(400, function() {
          refreshElem.replaceWith($(resp).find('#' + refreshElemId));
          let newRefreshElem = $('#' + refreshElemId);
          newRefreshElem.hide();
          newRefreshElem.fadeIn(400);
        });
      }
      if (itemSuppress) {
        itemSuppress.fadeOut(400);
      } else {
        loaderElem.replaceWith($(resp).find("#" + loaderElem.attr('id')));
      }
    },
    error: function() {}
  });
};

/**
 * Export graph to CSV
 *
 * @memberof Saturne_Dashboard
 *
 * @since   1.7.0
 * @version 1.7.0
 *
 * @param {Event} e Event
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
    type: 'POST',
    data: graph,
    success: function(resp) {
      let url        = window.URL.createObjectURL(new Blob([resp], { type: 'text/csv' }));
      let graphName  = button.data('graph-name').replace(/ /g, '_');
      let date       = new Date();
      let dateFormat = date.getFullYear() + ('0' + (date.getMonth() + 1)).slice(-2) + ('0' + date.getDate()).slice(-2);
      let fileName   = dateFormat + '_' +  graphName + '.csv';

      button.after($('<a href="' + url + '" download="' + fileName + '"></a>'));
      button.next()[0].click();

      window.saturne.loader.remove(button);
    },
    error: function() {}
  })
}
