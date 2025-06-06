<?php
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
 * \file    lib/component.lib.php
 * \ingroup saturne
 * \brief   Utility functions for render element with component in the Saturne module
 */

///**
// * Renders a reusable badge component with icon, title, and description.
// *
// * @param string $title       Main title of the badge (e.g., "Source")
// * @param string $description Secondary text or description (e.g., "Management process")
// * @param string $iconHtml    HTML code for the icon (can be emoji, <img>, or inline SVG)
// * @param string $type        Badge style type (e.g., "default", "success", "warning") – matched with a CSS modifier
// *
// * @return void
// */
//function saturne_render_badge_component(string $title, string $description, string $iconHtml = '👤', string $type = 'default') {
//    $html = <<<HTML
//    <div class="badge badge--$type">
//        <div class="badge__icon">$iconHtml</div>
//        <div class="badge__text">
//            <div class="badge__title">$title</div>
//            <div class="badge__description">$description</div>
//        </div>
//    </div>
//    HTML;
//
//    echo $html;
//}

/**
 * Renders a generic badge component.
 *
 * @param array $args {
 * Optional. Array of arguments for the component.
 *
 * @type string $id               Optional ID for the badge component.
 * @type string $className        Optional additional CSS classes for the main badge container.
 * @type string $iconClass        Optional. The Font Awesome class string for the main icon (e.g., 'fa-solid fa-user').
 * Defaults to 'fa-solid fa-user'.
 * @type string $title            The main title of the badge. Default 'Untitled'.
 * @type array  $details          Optional. An array of strings for additional detail lines.
 * Each string will be rendered in a .badge__detail div.
 * @type array  $actions          Optional. An array of action button definitions.
 * Each action should be an array with:
 * - 'iconClass': (string) The Font Awesome class string for the button icon (e.g., 'fa-solid fa-plus').
 * - 'label': (string) The ARIA label for the button.
 * - 'href': (string, optional) An URL if the button is a link.
 * - 'onClick': (string, optional) JavaScript code for an onclick event.
 * - 'modifierClass': (string, optional) A BEM modifier class for the button (e.g., 'add', 'edit', 'delete').
 * }
 * @return string The HTML string of the generic badge.
 */
function saturne_get_badge_component_html(array $args = []): string
{
    $defaults = [
        'id'        => '',
        'className' => '',
        'iconClass' => 'fa-solid fa-user', // Default Font Awesome user icon
        'title'     => 'Untitled',
        'details'   => [],
        'actions'   => [],
    ];

    $merged_args = array_merge( $defaults, $args );

    // Sanitize and prepare data
    $componentId    = ! empty( $merged_args['id'] ) ? ' id="' . htmlspecialchars( $merged_args['id'] ) . '"' : '';
    // Main block class is 'badge', then add any custom classes
    $classNames     = 'badge ' . htmlspecialchars( $merged_args['className'] );
    $iconClass      = htmlspecialchars( $merged_args['iconClass'] );
    $title          = htmlspecialchars( $merged_args['title'] );
    $details        = (array) $merged_args['details'];
    $actions        = (array) $merged_args['actions'];

    // Build details HTML
    $detailsHtml = '';
    foreach ( $details as $detail ) {
        $detailsHtml .= '<div class="badge__detail">' . htmlspecialchars( $detail ) . '</div>'; // BEM class
    }

    // Build actions HTML
    $actionButtonsHtml = '';
    if ( ! empty( $actions ) ) {
        foreach ( $actions as $action ) {
            $actionIconClass = isset( $action['iconClass'] ) ? htmlspecialchars( $action['iconClass'] ) : '';
            $actionLabel     = isset( $action['label'] ) ? htmlspecialchars( $action['label'] ) : 'Action';
            $actionHref      = isset( $action['href'] ) ? ' href="' . htmlspecialchars( $action['href'] ) . '"' : '';
            $actionOnClick   = isset( $action['onClick'] ) ? ' onclick="' . htmlspecialchars( $action['onClick'] ) . '"' : '';

            // Add BEM modifier if provided
            $actionModifierClass = isset( $action['modifierClass'] ) ? ' action-button--' . htmlspecialchars( $action['modifierClass'] ) : '';
            // Any additional custom class for the button
            $actionCustomClass   = isset( $action['className'] ) ? ' ' . htmlspecialchars( $action['className'] ) : '';

            $fullButtonClasses = 'action-button' . $actionModifierClass . $actionCustomClass;

            // Decide if it's a button or an anchor link
            $tag = empty( $actionHref ) ? 'button' : 'a';
            $typeAttribute = ( $tag === 'button' ) ? ' type="button"' : '';

            $actionButtonsHtml .= <<<BUTTON
            <{$tag} class="{$fullButtonClasses}" aria-label="{$actionLabel}"{$actionHref}{$typeAttribute}{$actionOnClick}>
                <i class="{$actionIconClass}"></i>
            </{$tag}>
BUTTON;
        }

        $actionButtonsHtml = <<<ACTIONS
            <div class="badge__actions">
                {$actionButtonsHtml}
            </div>
ACTIONS;
    }

    // Main HTML using heredoc
    $html = <<<HTML
    <div{$componentId} class="{$classNames}">
        <div class="badge__info">
            <div class="badge__icon">
                <i class="{$iconClass}"></i>
            </div>
            <div class="badge__details-wrapper">
                <div class="badge__title">{$title}</div>
                {$detailsHtml}
            </div>
        </div>
        {$actionButtonsHtml}
    </div>
    HTML;

    return $html;
}
