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
 * Navigation toggle handler for toggling all units in the left menu (expand/collapse all).
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.toggleAll = function toggleAll() {
  const $this             = $(this);
  const $toggledAllIcon   = $this.find('.toggle-all-icon');
  const $sideBarSecondary = $('.sidebar-secondary'); // The main sidebar container
  const $allToggleIcons   = $sideBarSecondary.find('.unit > .unit-container > .toggle-unit > .toggle-icon'); // Target ONLY direct child toggle icons of units
  const $allUnits         = $sideBarSecondary.find('.unit'); // Target all menu unit elements

  let saturneElementLeftMenu = new Set(); // Initialize an empty Set for localStorage updates

  try {
    // Not strictly necessary here as we overwrite, but good for consistent error handling patterns.
    JSON.parse(localStorage.getItem('saturneElementLeftMenu') || '[]');
  } catch (e) {
    console.error("Error parsing saturneElementLeftMenu from localStorage in toggleAll (initial read):", e);
    // We will proceed to overwrite, so no specific fallback Set is needed here.
  }

  // Determine if we are expanding or collapsing all
  if ($toggledAllIcon.hasClass('fa-caret-square-down')) {
    // --- Action: Expand All ---
    $toggledAllIcon.toggleClass('fa-caret-square-down fa-minus-square');
    $allToggleIcons.toggleClass('fa-chevron-right fa-chevron-down');
    $allUnits.addClass('toggled');

    // Update localStorage: Add all unit object-ids
    const allUnitObjectIds = [];
    $allUnits.each(function() {
      const objectId = String($(this).data('object-id'));
      if (objectId && objectId !== 'undefined' && objectId !== 'null') { // Ensure valid IDs
        allUnitObjectIds.push(objectId);
      }
    });
    saturneElementLeftMenu = new Set(allUnitObjectIds); // Create a Set of all valid IDs
  } else if ($toggledAllIcon.hasClass('fa-minus-square')) {
    // --- Action: Collapse All ---
    $toggledAllIcon.toggleClass('fa-minus-square fa-caret-square-down');
    $allToggleIcons.toggleClass('fa-chevron-down fa-chevron-right');
    $allUnits.removeClass('toggled');

    // Update localStorage: Clear all stored object-ids
    saturneElementLeftMenu = new Set(); // An empty Set means nothing is toggled
  }

  // Save the updated state to localStorage with error handling
  try {
    localStorage.setItem('saturneElementLeftMenu', JSON.stringify(Array.from(saturneElementLeftMenu)));
  } catch (e) {
    console.error("Error saving saturneElementLeftMenu to localStorage after toggleAll:", e);
    // Optionally, alert the user or use a different fallback storage mechanism
  }
};

/**
 * Handles the toggling of navigation menu items, persisting their state in local storage.
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
  const objectId    = String($unit.data('object-id')); // Ensure objectId is a string for consistency

  let saturneElementLeftMenu;
  try {
    // Attempt to parse existing data or initialize an empty array
    saturneElementLeftMenu = new Set(JSON.parse(localStorage.getItem('saturneElementLeftMenu') || '[]'));
  } catch (e) {
    // Handle potential parsing errors (e.g., malformed JSON in localStorage)
    console.error('Error parsing saturneElementLeftMenu from localStorage:', e);
    saturneElementLeftMenu = new Set(); // Fallback to an empty Set
  }

  // Determine the new state based on the current icon class
  const isCurrentlyDown = toggledIcon.hasClass('fa-chevron-down');

  // Toggle classes and update the Set based on the current state
  if (isCurrentlyDown) {
    toggledIcon.toggleClass('fa-chevron-down fa-chevron-right');
    $unit.removeClass('toggled');
    saturneElementLeftMenu.delete(objectId);
  } else {
    toggledIcon.toggleClass('fa-chevron-right fa-chevron-down');
    $unit.addClass('toggled');
    saturneElementLeftMenu.add(objectId);
  }

  // Save the updated Set back to localStorage
  try {
    localStorage.setItem('saturneElementLeftMenu', JSON.stringify(Array.from(saturneElementLeftMenu)));
  } catch (e) {
    console.error('Error saving saturneElementLeftMenu to localStorage:', e);
  }
};

/**
 * Finds and highlights a specific menu unit, expands its parent units,
 * and scrolls the menu to make it visible. Persists parent unit states in localStorage.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @param  {string} id The ID of the current unit to target (e.g., '123' for '#unit123').
 * @return {void}
 */
window.saturne.saturneElement.getLeftMenuCurrentUnit = function getLeftMenuCurrentUnit(id) {
  let $currentUnit = $('#unit'+id);

  // Exit if the target unit doesn't exist to prevent errors
  if ($currentUnit.length === 0) {
    console.warn(`Unit with ID '${id}' not found. Cannot highlight or scroll.`);
    return;
  }

  // Highlight the immediate container of the current unit
  $currentUnit.find('.unit-container').first().addClass('active');

  let $parentUnit = $currentUnit; // Start traversing from the current unit itself

  // Traverse up the DOM tree until we hit the top-level list or no more parents
  while ($parentUnit .length > 0 && !$parentUnit .hasClass('workunit-list')) {
    // Check if the current parent in the loop is a 'unit' that needs toggling
    if ($parentUnit.hasClass('unit')) {
      // Ensure the icon is 'down' (expanded) and the unit has 'toggled' class
      const $toggleIcon = $parentUnit.find('.toggle-icon');
      if ($toggleIcon.hasClass('fa-chevron-right')) {
        $toggleIcon.toggleClass('fa-chevron-right fa-chevron-down');
      }
      if (!$parentUnit.hasClass('toggled')) {
        $parentUnit.addClass('toggled');
      }
    }
    // Move up to the next parent
    $parentUnit = $parentUnit.parent();
  }

  // Scroll to the current unit in the sidebar
  const sideBarSecondaryContainer = $('.sidebar-secondary__container');
  const animationDurationMS       = 500;
  const scrollOffset              = 100; //@todo make this dynamic based on the height of the header or other elements

  // Only attempt to scroll if the container and the unit are found
  if (sideBarSecondaryContainer.length > 0 && $currentUnit.length > 0) {
    // Ensure we scroll the container itself, not the document
    sideBarSecondaryContainer.animate({
      // Adjust offset.top relative to the scrollable container's top
      scrollTop: sideBarSecondaryContainer.scrollTop() + $currentUnit.offset().top - sideBarSecondaryContainer.offset().top - scrollOffset
    }, animationDurationMS);
  } else {
    console.warn('Could not scroll to unit. Sidebar container or target unit not found.');
  }
};

/**
 * Initializes the state of the left menu on page load.
 * Expands previously toggled units based on localStorage and highlights the current unit
 * if specified by URL parameters.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.saturne.saturneElement.getLeftMenu = function getLeftMenu() {
  let saturneElementLeftMenu;
  try {
    // Attempt to parse existing data or initialize an empty array
    saturneElementLeftMenu = new Set(JSON.parse(localStorage.getItem('saturneElementLeftMenu') || '[]'));
  } catch (e) {
    // Handle potential parsing errors from localStorage
    console.error("Error parsing saturneElementLeftMenu from localStorage on menu initialization:", e);
    saturneElementLeftMenu = new Set(); // Fallback to an empty Set
  }

  saturneElementLeftMenu.forEach((id) =>  {
    $('#menu'+id).toggleClass('fa-chevron-right fa-chevron-down');
    $('#unit'+id).addClass('toggled');
  });

  // Get the current unit from the URL parameters and activate it
  const params = new URLSearchParams(window.location.search);
  const id     = params.get('id') || params.get('fromid');

  if (id) {
    // Call the dedicated function to highlight, expand parents, and scroll to the current unit
    window.saturne.saturneElement.getLeftMenuCurrentUnit(id);
  }
};
