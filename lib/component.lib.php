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
    global $langs;

    $defaults = [
        'id'        => '',
        'field'     => '', // Optional field name for the badge, useful for data binding
        'className' => '',
        'iconClass' => 'fas fa-user',
        'title'     => 'Untitled',
        'details'   => [$langs->transnoentities('NotKnown')],
        'actions'   => []
    ];

    $merged_args = array_merge( $defaults, $args );

    // Sanitize and prepare data
    $componentId    = ! empty( $merged_args['id'] ) ? ' id="' . htmlspecialchars( $merged_args['id'] ) . '"' : '';
    // Main block class is 'badge', then add any custom classes
    $classNames     = 'wpeo-badge ' . htmlspecialchars( $merged_args['className'] );
    $iconClass      = htmlspecialchars( $merged_args['iconClass'] );
    $title          = $langs->transnoentities($merged_args['title']);
    $details        = (array) $merged_args['details'];
    $actions        = (array) $merged_args['actions'];

    // Build details HTML
    $detailsHtml = '';
    foreach ( $details as $detail ) {
        $detailsHtml .= '<div class="badge__detail" contenteditable="true" data-field="' . $merged_args['field'] . '">' . htmlspecialchars( $detail ) . '</div>'; // BEM class
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

            // Build hidden inputs HTML
            $hiddenInputsHtml = '';
            if ( isset( $action['hiddenInputs'] ) && is_array( $action['hiddenInputs'] ) ) {
                foreach ( $action['hiddenInputs'] as $input ) {
                    $inputName  = isset( $input['name'] ) ? ' name="' . htmlspecialchars( $input['name'] ) . '"' : '';
                    $inputValue = isset( $input['value'] ) ? ' value="' . htmlspecialchars( $input['value'] ) . '"' : '';
                    $inputClass = isset( $input['class'] ) ? ' class="' . htmlspecialchars( $input['class'] ) . '"' : '';

                    $inputDataAttrs = '';
                    if ( isset( $input['data'] ) && is_array( $input['data'] ) ) {
                        foreach ( $input['data'] as $dataKey => $dataValue ) {
                            $inputDataAttrs .= ' data-' . htmlspecialchars( $dataKey ) . '="' . htmlspecialchars( $dataValue ) . '"';
                        }
                    }
                    $hiddenInputsHtml .= "<input type=\"hidden\"{$inputName}{$inputValue}{$inputClass}{$inputDataAttrs}>";
                }
            }

            $actionButtonsHtml .= <<<BUTTON
            <{$tag} class="{$fullButtonClasses}" aria-label="{$actionLabel}"{$actionHref}{$typeAttribute}{$actionOnClick}>
                <i class="{$actionIconClass}"></i>
                {$hiddenInputsHtml}
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


// functions.php

// (Gardez toutes vos fonctions existantes ici : saturne_get_badge_component_html, etc.)


/**
 * Generates the HTML for a generic modal header's recap section.
 *
 * @param array $args      {
 *                         Optional. Array of arguments for the header content.
 *
 * @type string $iconClass Optional. The Font Awesome class string for the main icon (e.g., 'fa-solid fa-user').
 *                         Defaults to 'fa-solid fa-user'.
 * @type string $title     The main title for the header. Default 'Untitled'.
 * @type array  $details   Optional. An array of strings for additional detail lines.
 *                         Each string will be rendered in a .modal-header__detail div.
 *                         }
 * @return string The HTML string for the modal header's recap content.
 */
function saturne_get_modal_header_recap_html($args = array())
{
    $defaults = array(
        'iconClass' => 'fa-solid fa-user',
        'title'     => 'Untitled',
        'details'   => array(),
    );

    $merged_args = array_merge($defaults, $args);

    $iconClass = htmlspecialchars($merged_args['iconClass']);
    $title = htmlspecialchars($merged_args['title']);
    $details = (array)$merged_args['details'];

    // Build details HTML
    $detailsHtml = '';
    foreach ($details as $detail) {
        $detailsHtml .= '<div class="modal-header__detail">' . htmlspecialchars($detail) . '</div>';
    }

    $html = <<<HTML
    <div class="modal-header__info">
        <div class="modal-header__icon">
            <i class="{$iconClass}"></i>
        </div>
        <div class="modal-header__details-wrapper">
            <div class="modal-header__title">{$title}</div>
            {$detailsHtml}
        </div>
    </div>
HTML;

    return $html;
}

function saturne_get_button_component_html(array $args = []): string
{
    $defaults = [
        'id'            => '',
        'className'     => '',
        'iconClass'     => '', // Icon is now optional, as not all components might have one
        'href'          => '#',
        'onClick'       => '',
        'modifierClass' => '',
        'moreAttr'      => [], // NOUVEAU : Tableau pour les attributs supplémentaires
        'spans'         => [], // NEW: Array for multiple span configurations
        'tag'           => 'a', // NEW: Allows defining the main HTML tag (e.g., 'a', 'div', 'button')
    ];

    $merged_args = array_merge($defaults, $args);

    // Sanitize and prepare data
    $componentId    = !empty($merged_args['id']) ? ' id="' . htmlspecialchars($merged_args['id']) . '"' : '';
    $classNames     = 'button-component ' . htmlspecialchars($merged_args['className']);
    $iconClass      = htmlspecialchars($merged_args['iconClass']);
    $href           = htmlspecialchars($merged_args['href']);
    $onClick        = htmlspecialchars($merged_args['onClick']);
    $modifierClass  = !empty($merged_args['modifierClass']) ? ' button-component--' . htmlspecialchars($merged_args['modifierClass']) : '';
    $mainTag        = htmlspecialchars($merged_args['tag']);

    // Prepare additional attributes
    $additionalAttributes = '';
    foreach ($merged_args['moreAttr'] as $attrName => $attrValue) {
        $additionalAttributes .= ' ' . htmlspecialchars($attrName) . '="' . htmlspecialchars($attrValue) . '"';
    }

    // Build span HTML
    $spanHtml = '';
    foreach ($merged_args['spans'] as $spanConfig) {
        $spanDefaults = [
            'className' => '',
            'label'     => '',
            'moreAttr'  => [],
        ];
        $mergedSpanConfig = array_merge($spanDefaults, $spanConfig);

        $spanClassNames = htmlspecialchars($mergedSpanConfig['className']);
        $spanLabel      = htmlspecialchars($mergedSpanConfig['label']);

        $spanAdditionalAttributes = '';
        foreach ($mergedSpanConfig['moreAttr'] as $spanAttrName => $spanAttrValue) {
            $spanAdditionalAttributes .= ' ' . htmlspecialchars($spanAttrName) . '="' . htmlspecialchars($spanAttrValue) . '"';
        }

        $spanHtml .= '<span class="' . $spanClassNames . '"' . $spanAdditionalAttributes . '>' . $spanLabel . '</span>';
    }

    // Build the component HTML
    $html = <<<HTML
    <{$mainTag}{$componentId} class="{$classNames}{$modifierClass}" href="{$href}" onclick="{$onClick}"{$additionalAttributes}>
    HTML;

    if (!empty($iconClass)) {
        $html .= <<<HTML
        <i class="{$iconClass}"></i>
        HTML;
    }

    $html .= <<<HTML
        {$spanHtml}
    </{$mainTag}>
    HTML;

    return $html;
}
