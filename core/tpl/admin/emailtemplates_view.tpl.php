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

/**
 * \file    core/tpl/admin/emailtemplates_view.tpl.php
 * \ingroup saturne
 * \brief   View of the email templates overview
 *
 * Expects $form and $overview prepared by admin/emailtemplates.php (saturne_email_template_overview())
 */

global $langs;

$coreTemplatesUrl = DOL_URL_ROOT . '/admin/mails_templates.php';
$showLang         = getDolGlobalInt('MAIN_MULTILANGS');

print '<div class="saturne-mailtpl">';

print '<p class="opacitymedium">' . $langs->trans('EmailTemplatesOverviewIntro') . '</p>';

// Toolbar: the filters run in the browser (js/modules/emailTemplate.js), every template is already on the page
print '<div class="saturne-mailtpl-toolbar">';
print '<input type="search" id="saturne-mailtpl-search" class="saturne-mailtpl-toolbar__search" placeholder="' . dol_escape_htmltag($langs->trans('EmailTemplatesSearchPlaceholder')) . '" autocomplete="off">';
print '<select id="saturne-mailtpl-status" class="saturne-mailtpl-toolbar__status">';
print '<option value="">' . $langs->trans('EmailTemplatesAllStatuses') . '</option>';
print '<option value="1">' . $langs->trans('EmailTemplatesActiveOnly') . '</option>';
print '<option value="0">' . $langs->trans('EmailTemplatesInactiveOnly') . '</option>';
print '</select>';
print '<label class="saturne-mailtpl-toolbar__used"><input type="checkbox" id="saturne-mailtpl-used"> ' . $langs->trans('EmailTemplatesUsedOnly') . '</label>';
print '<span class="saturne-mailtpl-toolbar__actions">';
print '<button type="button" class="button buttongen saturne-mailtpl-expand" data-open="1">' . $langs->trans('EmailTemplatesExpandAll') . '</button>';
print '<button type="button" class="button buttongen saturne-mailtpl-expand" data-open="0">' . $langs->trans('EmailTemplatesCollapseAll') . '</button>';
print dolGetButtonTitle($langs->trans('EmailTemplatesNew'), '', 'fa fa-plus-circle', $coreTemplatesUrl . '?action=create');
print '</span>';
print '</div>';

print '<div class="saturne-mailtpl-summary opacitymedium">';
print $langs->trans('EmailTemplatesSummary', $overview['total'], count($overview['groups']), $overview['inactive'], $overview['used']);
print '<span class="saturne-mailtpl-summary__filtered hidden"> · ' . $langs->trans('EmailTemplatesShown', '<span class="saturne-mailtpl-summary__count"></span>') . '</span>';
print '</div>';

if (empty($overview['groups'])) {
    print '<div class="opacitymedium">' . $langs->trans('NoRecordFound') . '</div>';
}

foreach ($overview['groups'] as $groupKey => $group) {
    $groupInactive = count(array_filter($group['templates'], function ($template) {
        return !$template['active'];
    }));

    print '<details class="saturne-mailtpl-group" data-group="' . dol_escape_htmltag($groupKey) . '">';
    print '<summary class="saturne-mailtpl-group__title">';
    print '<span class="saturne-mailtpl-group__label">' . $group['label'] . '</span>';
    print '<span class="badge marginleftonlyshort" title="' . dol_escape_htmltag($langs->trans('EmailTemplatesCount')) . '">' . count($group['templates']) . '</span>';
    if ($groupInactive > 0) {
        print '<span class="badge badge-secondary marginleftonlyshort">' . $langs->trans('EmailTemplatesInactiveCount', $groupInactive) . '</span>';
    }
    print '</summary>';

    if ($groupKey === SATURNE_EMAIL_TEMPLATE_UNAVAILABLE) {
        print '<div class="saturne-mailtpl-group__help opacitymedium">' . $langs->trans('EmailTemplatesUnavailableHelp') . '</div>';
    }

    print '<table class="noborder centpercent saturne-mailtpl-table">';
    print '<tr class="liste_titre">';
    print '<td class="saturne-mailtpl-col--label">' . $langs->trans('EmailTemplate') . '</td>';
    print '<td class="saturne-mailtpl-col--topic">' . $langs->trans('Topic') . '</td>';
    if ($showLang) {
        print '<td class="saturne-mailtpl-col--lang">' . $langs->trans('Language') . '</td>';
    }
    print '<td class="saturne-mailtpl-col--usages">' . $form->textwithpicto($langs->trans('EmailTemplatesUsedBy'), $langs->trans('EmailTemplatesUsedByHelp')) . '</td>';
    print '<td class="center saturne-mailtpl-col--status">' . $langs->trans('Status') . '</td>';
    print '<td class="saturne-mailtpl-col--actions"></td>';
    print '</tr>';

    foreach ($group['templates'] as $template) {
        $rowClasses = 'saturne-mailtpl-row' . ($template['active'] ? '' : ' saturne-mailtpl-row--inactive');
        $toggleUrl  = $_SERVER['PHP_SELF'] . '?action=' . ($template['active'] ? 'disable' : 'activate') . '&rowid=' . $template['id'] . '&token=' . newToken();
        $editUrl    = $coreTemplatesUrl . '?rowid=' . $template['id'] . '&action=edit&token=' . newToken() . '&search_type_template=' . urlencode($template['type']) . '#rowid-' . $template['id'];

        print '<tr class="' . $rowClasses . '" id="mailtpl-' . $template['id'] . '" data-search="' . dol_escape_htmltag($template['search']) . '" data-active="' . $template['active'] . '" data-used="' . (empty($template['usages']) ? 0 : 1) . '">';

        // Template: readable label, badges, raw code when the label is a translation key
        print '<td class="saturne-mailtpl-row__label">';
        print '<a href="#mailtpl-' . $template['id'] . '" class="saturne-mailtpl-preview-toggle">' . dol_escape_htmltag($template['label']) . '</a>';
        if ($template['default']) {
            print ' <span class="badge badge-status4" title="' . dol_escape_htmltag($langs->trans('EmailTemplatesDefaultHelp')) . '">' . $langs->trans('Default') . '</span>';
        }
        if ($template['private']) {
            print ' <span class="badge badge-secondary" title="' . dol_escape_htmltag($langs->trans('TemplateIsVisibleByOwnerOnly')) . '">' . $langs->trans('Private') . ($template['owner'] ? ' · ' . dol_escape_htmltag($template['owner']) : '') . '</span>';
        }
        if ($template['joinfiles']) {
            print ' <span class="badge badge-info" title="' . dol_escape_htmltag($langs->trans('AttachMainDocByDefault')) . '">' . img_picto('', 'attach', 'class="paddingright"') . $langs->trans('EmailTemplatesAttachment') . '</span>';
        }
        if ($template['duplicate']) {
            print ' <span class="badge badge-warning" title="' . dol_escape_htmltag($langs->trans('EmailTemplatesDuplicateHelp')) . '">' . $langs->trans('EmailTemplatesDuplicate') . '</span>';
        }
        if ($template['code'] !== '') {
            print '<br><span class="opacitymedium small">' . dol_escape_htmltag($template['code']) . '</span>';
        }
        print '</td>';

        print '<td class="saturne-mailtpl-row__topic">' . dol_escape_htmltag($template['topic']) . '</td>';

        if ($showLang) {
            print '<td class="nowraponall">' . ($template['lang'] ? picto_from_langcode($template['lang']) . ' ' . dol_escape_htmltag($template['lang']) : '<span class="opacitymedium">' . $langs->trans('EmailTemplatesAllLanguages') . '</span>') . '</td>';
        }

        print '<td class="saturne-mailtpl-row__usages">';
        if (empty($template['usages'])) {
            print '<span class="opacitymedium">-</span>';
        }
        // A setting with a label shows it, its constant in the tooltip, and leads to the page where it is set when known
        foreach ($template['usages'] as $usage) {
            if ($usage['label'] === '') {
                print '<code class="saturne-mailtpl-usage">' . dol_escape_htmltag($usage['name']) . '</code>';
            } elseif ($usage['url'] !== '') {
                print '<a class="saturne-mailtpl-usage saturne-mailtpl-usage--labelled classfortooltip" href="' . dol_escape_htmltag($usage['url']) . '" title="' . dol_escape_htmltag($usage['name']) . '">' . dol_escape_htmltag($usage['label']) . '</a>';
            } else {
                print '<span class="saturne-mailtpl-usage saturne-mailtpl-usage--labelled classfortooltip" title="' . dol_escape_htmltag($usage['name']) . '">' . dol_escape_htmltag($usage['label']) . '</span>';
            }
        }
        print '</td>';

        print '<td class="center">';
        print '<a class="reposition" href="' . $toggleUrl . '">' . img_picto($langs->trans($template['active'] ? 'Activated' : 'Disabled'), $template['active'] ? 'switch_on' : 'switch_off', 'class="size15x"') . '</a>';
        print '</td>';

        print '<td class="right nowraponall">';
        print '<a href="#mailtpl-' . $template['id'] . '" class="saturne-mailtpl-preview-toggle marginrightonly" title="' . dol_escape_htmltag($langs->trans('Preview')) . '">' . img_picto($langs->trans('Preview'), 'eye') . '</a>';
        print '<a class="editfielda" href="' . $editUrl . '" title="' . dol_escape_htmltag($langs->trans('Modify')) . '">' . img_edit() . '</a>';
        print '</td>';

        print '</tr>';

        // Preview, closed until asked: the content is kept in a <template> so its images are only loaded when shown
        print '<tr class="saturne-mailtpl-preview hidden" data-for="' . $template['id'] . '">';
        print '<td colspan="' . ($showLang ? 6 : 5) . '">';
        print '<dl class="saturne-mailtpl-preview__fields">';
        if ($template['from'] !== '') {
            print '<dt>' . $langs->trans('MailFrom') . '</dt><dd>' . dol_escape_htmltag($template['from']) . '</dd>';
        }
        print '<dt>' . $langs->trans('Topic') . '</dt><dd>' . ($template['topic'] !== '' ? dol_escape_htmltag($template['topic']) : '<span class="opacitymedium">' . $langs->trans('EmailTemplatesEmpty') . '</span>') . '</dd>';
        print '<dt>' . $langs->trans('Content') . '</dt><dd><div class="saturne-mailtpl-preview__content"></div></dd>';
        if ($template['contentLines'] !== '') {
            print '<dt>' . $langs->trans('EmailTemplatesContentLines') . '</dt><dd><div class="saturne-mailtpl-preview__lines">' . dolPrintHTML($template['contentLines']) . '</div></dd>';
        }
        if (!empty($template['variables'])) {
            print '<dt>' . $form->textwithpicto($langs->trans('EmailTemplatesVariables'), $langs->trans('EmailTemplatesVariablesHelp')) . '</dt><dd>';
            foreach ($template['variables'] as $variable) {
                print '<code class="saturne-mailtpl-variable">' . dol_escape_htmltag($variable) . '</code>';
            }
            print '</dd>';
        }
        if ($template['module'] !== '') {
            print '<dt>' . $langs->trans('EmailTemplatesShippedBy') . '</dt><dd>' . dol_escape_htmltag($template['module']) . '</dd>';
        }
        if (!$template['private'] && $template['owner'] !== '') {
            print '<dt>' . $langs->trans('Owner') . '</dt><dd>' . dol_escape_htmltag($template['owner']) . '</dd>';
        }
        print '</dl>';
        print '<template class="saturne-mailtpl-preview__source">' . ($template['content'] !== '' ? dolPrintHTML($template['content']) : '<span class="opacitymedium">' . $langs->trans('EmailTemplatesEmpty') . '</span>') . '</template>';
        print '</td>';
        print '</tr>';
    }

    print '</table>';
    print '</details>';
}

print '<div class="saturne-mailtpl-noresult opacitymedium hidden">' . $langs->trans('EmailTemplatesNoMatch') . '</div>';

print '</div>';
