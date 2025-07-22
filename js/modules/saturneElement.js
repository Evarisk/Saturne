/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/saturneElement.js
 * \ingroup saturne
 * \brief   JavaScript saturneElement file
 */

'use strict';

/**
 * Init saturneElement JS
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.saturne.saturneElement = {};

/**
 * SaturneElement init
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.init = function init() {
  window.saturne.saturneElement.event();
  window.saturne.saturneElement.getLeftMenu();
};

/**
 * SaturneElement event initialization. Binds all necessary event listeners
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.event = function initializeEvents() {
  $(document).on( 'click', '.toggle-all', window.saturne.saturneElement.toggleAll);
  $(document).on( 'click', '.toggle-unit', window.saturne.saturneElement.switchToggle);
};

/**
 * Navigation toggle handler for toggling all units in the left menu
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.toggleAll = function toggleAll() {
  const $this = $(this);
  // const toggledIcon = $this.find('.toggle-icon');
  // const $unit       = $this.closest('.unit');
  // const objectId    = $unit.data('object-id');
  //
  // let saturneElementLeftMenu = new Set(JSON.parse(localStorage.getItem('saturneElementLeftMenu') || '[]'));
  //
  // if (toggledIcon.hasClass('fa-chevron-down')) {
  //   toggledIcon.toggleClass('fa-chevron-down fa-chevron-right');
  //   $unit.removeClass('toggled');
  //   saturneElementLeftMenu.delete(objectId);
  // } else if (toggledIcon.hasClass( 'fa-chevron-right')) {
  //   toggledIcon.toggleClass('fa-chevron-right fa-chevron-down');
  //   $unit.addClass('toggled');
  //   saturneElementLeftMenu.add(objectId);
  // }
  //
  // localStorage.setItem('saturneElementLeftMenu', JSON.stringify(Array.from(saturneElementLeftMenu)));

  if ($this.hasClass( 'toggle-plus')) {
    $( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
    $( '.digirisk-wrap .navigation-container .workunit-list .unit' ).addClass( 'toggled' );

    // local storage add all
    let MENU = []
    $( '.digirisk-wrap .navigation-container .workunit-list .unit .title' ).get().map(function (v){
      MENU.push($(v).attr('value'))
    })
    localStorage.setItem('menu', JSON.stringify(Array.from(MENU.values())) );
  } else if ($this.hasClass('toggle-minus')) {
    $( '.digirisk-wrap .navigation-container .workunit-list .unit .toggle-icon').addClass( 'fa-chevron-right').removeClass( 'fa-chevron-down' );
    $( '.digirisk-wrap .navigation-container .workunit-list .unit.toggled' ).removeClass( 'toggled' );

    // local storage delete all
    let emptyMenu = new Set('0');
    localStorage.setItem('menu', JSON.stringify(Object.values(emptyMenu)) );
  }
};

/**
 * Navigation toggle switch handler
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.switchToggle = function switchToggle() {
  const $this       = $(this);
  const toggledIcon = $this.find('.toggle-icon');
  const $unit       = $this.closest('.unit');
  const objectId    = $unit.data('object-id');

  let saturneElementLeftMenu = new Set(JSON.parse(localStorage.getItem('saturneElementLeftMenu') || '[]'));

  if (toggledIcon.hasClass('fa-chevron-down')) {
    toggledIcon.toggleClass('fa-chevron-down fa-chevron-right');
    $unit.removeClass('toggled');
    saturneElementLeftMenu.delete(objectId);
  } else if (toggledIcon.hasClass( 'fa-chevron-right')) {
    toggledIcon.toggleClass('fa-chevron-right fa-chevron-down');
    $unit.addClass('toggled');
    saturneElementLeftMenu.add(objectId);
  }

  localStorage.setItem('saturneElementLeftMenu', JSON.stringify(Array.from(saturneElementLeftMenu)));
};

window.saturne.saturneElement.getLeftMenuCurrentUnit = function getLeftMenuCurrentUnit(id) {
  let $currentUnit = $('#unit'+id);

  $currentUnit.find('.unit-container').first().addClass('active');

  while ($currentUnit.length > 0 && !$currentUnit.hasClass('workunit-list')) {
    $currentUnit = $currentUnit.parent();
    if ($currentUnit.hasClass('unit')) {
      $currentUnit.find('.toggle-icon').toggleClass('fa-chevron-right fa-chevron-down');
      $currentUnit.addClass('toggled');
    }
  }

  // Scroll to the current unit in the sidebar
  const sideBarSecondaryContainer = $('.sidebar-secondary__container');
  const animationDurationMS       = 500;
  const scrollOffset              = 100; //@todo make this dynamic based on the height of the header or other elements
  $(sideBarSecondaryContainer).animate({
    scrollTop: $currentUnit.offset().top - scrollOffset
  }, animationDurationMS);
};

/**
 * Get left menu state
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.getLeftMenu = function getLeftMenu() {
  let saturneElementLeftMenu = new Set(JSON.parse(localStorage.getItem('saturneElementLeftMenu') || '[]'));
  saturneElementLeftMenu.forEach((id) =>  {
    $('#menu'+id).toggleClass('fa-chevron-right fa-chevron-down');
    $('#unit'+id).addClass('toggled');
  });

  // Get the current unit from the URL parameters
  const params = new URLSearchParams(window.location.search);
  const id     = params.get('id') || params.get('fromid');
  if (id) {
    window.saturne.saturneElement.getLeftMenuCurrentUnit(id);
  }
};
