/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/object.js
 * \ingroup saturne
 * \brief   JavaScript object file for module Saturne
 */

'use strict';

/**
 * Init object JS
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @type {Object}
 */
window.saturne.object = {};

/**
 * Object init
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @returns {void}
 */
window.saturne.object.init = function() {
  window.saturne.object.event();
};

/**
 * Object event
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @returns {void}
 */
window.saturne.object.event = function() {
  $(document).on('click', '.toggle-object-infos', window.saturne.object.toggleObjectInfos);
};

/**
 * Show object infos if toggle is on
 *
 * @since   1.2.1
 * @version 1.2.1
 *
 * @return {void}
 */
window.saturne.object.toggleObjectInfos = function() {
  if ($(this).hasClass('fa-minus-square')) {
    $(this).removeClass('fa-minus-square').addClass('fa-caret-square-down');
    $(this).closest('.fiche').find('.fichecenter.object-infos').addClass('hidden');
  } else {
    $(this).removeClass('fa-caret-square-down').addClass('fa-minus-square');
    $(this).closest('.fiche').find('.fichecenter.object-infos').removeClass('hidden');
  }
};

window.saturne.object.getFields = function getFields(mode, objectElement, fromId = null, fromType = null) {
  let datas = {};
  $(`#${objectElement}_${mode} .input-ajax`).each(function() {
    const $this    = $(this);
    let fieldName  = $this.attr('name');
    let fieldValue = $this.val();

    if (fieldName) {
      datas[fieldName] = fieldValue;
    }
  });

  if (fromId !== null) {
    datas.fk_object_id = fromId;
  }
  if (fromType !== null) {
    datas.fk_object_element = fromType;
  }

  return JSON.stringify(datas);
};

window.saturne.object.ajax = function ajax(mode, objectElement, additionalDatas = {}, successCallback = null) {
  const token          = window.saturne.toolbox.getToken();
  const querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  let formData    = JSON.parse(window.saturne.object.getFields(mode, objectElement));
  const finalData = { ...formData, ...additionalDatas };

  $.ajax({
    url: `${document.URL}${querySeparator}&action=${mode}_${objectElement}&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify(finalData),
    success: function (resp) {
      if (typeof successCallback === 'function') {
        successCallback(resp);
      }
    },
    error: function(xhr, status, error) {
      console.error(`Error ${objectElement}:`, xhr.responseText || error);
    }
  });
};

window.saturne.object.ObjectFromModal = function ObjectFromModal(mode, objectElement) {
  const $this  = $(this);
  const $modal = $this.closest(`#${objectElement}_${mode}`);
  const fromId = $modal.data('from-id');
  const $list  = $(document).find(`#${objectElement}_list_container_${fromId}`);

  window.saturne.loader.display($list);

  let additionalDatas = {};
  if (mode === 'create') {
    const fromType  = $modal.data('from-type');
    additionalDatas = { fk_object_id: fromId, fk_object_element: fromType };
  } else if (mode === 'update') {
    const objectId  = $modal.data('object-id');
    additionalDatas = { object_id: objectId };
  }

  window.saturne.object.ajax(
    mode,
    objectElement,
    additionalDatas,
    function(resp) {
      window.saturne.object.reloadListSuccess($modal, mode, objectElement, $list, fromId, resp);
    }
  );
};

window.saturne.object.reloadListSuccess = function reloadListSuccess($modal, mode, objectElement, $list, fromId, resp) {
  $modal.replaceWith($(resp).find(`#${objectElement}_${mode}`));
  $list.replaceWith($(resp).find(`#${objectElement}_list_container_${fromId}`));
};
