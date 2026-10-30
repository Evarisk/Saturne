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
 * \file    lib/emailtemplate.lib.php
 * \ingroup saturne
 * \brief   Library of the email templates overview (admin/emailtemplates.php)
 *
 * Dolibarr lists every email template in one flat table, truncated, with the raw translation keys of the
 * templates it ships. The overview reads the same rows with the same visibility rules, groups them by
 * element and tells which setting sends each of them.
 */

/**
 * Key of the group gathering the templates Dolibarr does not offer anywhere
 */
const SATURNE_EMAIL_TEMPLATE_UNAVAILABLE = '__unavailable__';

/**
 * Return the elements an email template can be attached to, as the core email templates page lists them.
 *
 * The core page builds this list inline, so it is rebuilt here with the same conditions, then completed
 * by the modules through the emailElementlist hook, called in the 'emailtemplates' context the modules
 * test for.
 *
 * @param  DoliDB    $db    Database handler
 * @param  User      $user  User reading the list
 * @param  Translate $langs Output language
 * @return array<string, string> HTML label, picto included, by type_template
 */
function saturne_email_template_elements(DoliDB $db, User $user, Translate $langs): array
{
    // Type => [module enabled, right, picto, language key]
    $definitions = [
        'user'                        => [true, true, 'user', 'MailToUser'],
        'member'                      => [isModEnabled('member'), $user->hasRight('adherent', 'lire'), 'object_member', 'MailToMember'],
        'recruitmentcandidature_send' => [isModEnabled('recruitment'), $user->hasRight('recruitment', 'recruitmentjobposition', 'read'), 'recruitmentcandidature', 'RecruitmentCandidatures'],
        'thirdparty'                  => [isModEnabled('societe'), $user->hasRight('societe', 'lire'), 'company', 'MailToThirdparty'],
        'contact'                     => [isModEnabled('societe'), $user->hasRight('societe', 'contact', 'lire'), 'contact', 'MailToContact'],
        'project'                     => [isModEnabled('project'), true, 'project', 'MailToProject'],
        'propal_send'                 => [isModEnabled('propal'), $user->hasRight('propal', 'lire'), 'propal', 'MailToSendProposal'],
        'order_send'                  => [isModEnabled('order'), $user->hasRight('commande', 'lire'), 'order', 'MailToSendOrder'],
        'facture_send'                => [isModEnabled('invoice'), $user->hasRight('facture', 'lire'), 'bill', 'MailToSendInvoice'],
        'shipping_send'               => [isModEnabled('shipping'), true, 'dolly', 'MailToSendShipment'],
        'delivery_send'               => [isModEnabled('shipping'), true, 'dolly', 'MailToSendDelivery'],
        'reception_send'              => [isModEnabled('reception'), true, 'dollyrevert', 'MailToSendReception'],
        'fichinter_send'              => [isModEnabled('intervention'), true, 'intervention', 'MailToSendIntervention'],
        'supplier_proposal_send'      => [isModEnabled('supplier_proposal'), true, 'propal', 'MailToSendSupplierRequestForQuotation'],
        'order_supplier_send'         => [isModEnabled('supplier_order'), $user->hasRight('fournisseur', 'commande', 'lire') || $user->hasRight('supplier_order', 'read'), 'order', 'MailToSendSupplierOrder'],
        'invoice_supplier_send'       => [isModEnabled('supplier_invoice'), $user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'read'), 'bill', 'MailToSendSupplierInvoice'],
        'supplier_payment_send'       => [isModEnabled('supplier_invoice'), $user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'write'), 'bill', 'SuppliersPayment'],
        'contract'                    => [isModEnabled('contract'), $user->hasRight('contrat', 'lire'), 'contract', 'MailToSendContract'],
        'ticket_send'                 => [isModEnabled('ticket'), $user->hasRight('ticket', 'read'), 'ticket', 'MailToTicket'],
        'expensereport_send'          => [isModEnabled('expensereport'), $user->hasRight('expensereport', 'lire'), 'trip', 'MailToExpenseReport'],
        'actioncomm_send'             => [isModEnabled('agenda'), true, 'action', 'MailToSendEventPush'],
        'conferenceorbooth'           => [isModEnabled('eventorganization'), $user->hasRight('project', 'read'), 'action', 'MailToSendEventOrganization'],
        'partnership_send'            => [isModEnabled('partnership'), $user->hasRight('partnership', 'read'), 'partnership', 'MailToPartnership'],
        'product_send'                => [isModEnabled('product'), $user->hasRight('produit', 'lire'), 'product', 'Product'],
    ];

    $elements = [
        'all'  => img_picto('', 'email', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('EmailTemplatesForAllElements')),
        'none' => img_picto('', 'generic', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('EmailTemplatesForNoElement')),
    ];
    foreach ($definitions as $type => $definition) {
        list($enabled, $allowed, $picto, $labelKey) = $definition;
        if ($enabled && $allowed) {
            $elements[$type] = img_picto('', $picto, 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans($labelKey));
        }
    }

    // A hook manager of its own: the page context must not trigger what the modules print on the core page.
    // 'main' too, as on the core page: some modules only register that context (doliletter)
    $hookmanager = new HookManager($db);
    $hookmanager->initHooks(['main', 'emailtemplates']);
    $parameters = ['elementList' => $elements];
    $reshook    = $hookmanager->executeHooks('emailElementlist', $parameters);
    if ($reshook == 0 && is_array($hookmanager->resArray)) {
        foreach ($hookmanager->resArray as $type => $label) {
            $elements[$type] = $label;
        }
    }

    return $elements;
}

/**
 * Return the email templates the user can see, with the visibility rules of the core email templates page.
 *
 * @param  DoliDB    $db    Database handler
 * @param  User      $user  User reading the list
 * @param  Translate $langs Output language
 * @return array<int, stdClass> Rows of c_email_templates by rowid, empty on error
 */
function saturne_email_template_fetch_all(DoliDB $db, User $user, Translate $langs): array
{
    $sql  = 'SELECT rowid, module, label, type_template, lang, fk_user, private, position, topic, email_from, joinfiles, defaultfortype,';
    $sql .= ' content, content_lines, enabled, active';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_email_templates';
    $sql .= ' WHERE entity IN (' . getEntity('email_template') . ')';
    if (!$user->admin) {
        $sql .= ' AND (private = 0 OR (private = 1 AND fk_user = ' . ((int) $user->id) . '))';
        $sql .= ' AND (active = 1 OR fk_user = ' . ((int) $user->id) . ')';
    }
    if (!getDolGlobalInt('MAIN_MULTILANGS')) {
        $sql .= " AND (lang = '" . $db->escape($langs->defaultlang) . "' OR lang IS NULL OR lang = '')";
    }
    $sql .= $db->order('position, label', 'ASC, ASC');

    $templates = [];
    $resql     = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ' ' . $db->lasterror(), LOG_ERR);
        return [];
    }
    while ($obj = $db->fetch_object($resql)) {
        $templates[(int) $obj->rowid] = $obj;
    }
    $db->free($resql);

    return $templates;
}

/**
 * Return the settings (constants of the current entity) that point to each email template.
 *
 * Modules keep the template a setting sends in a constant named after it (..._EMAIL_TEMPLATE_...,
 * ..._MAIL_MODEL_..., ..._TEMPLATE_EMAIL_...), but not in one shape: the rowid, the label, "label:type"
 * or a JSON list of rowids. Every shape is read; a value naming no template is ignored, which leaves
 * out the flags sharing the naming (DOLIMEET_EMAIL_TEMPLATE_SET, MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES).
 *
 * @param  DoliDB                 $db        Database handler
 * @param  array<int, stdClass>   $templates Templates by rowid, from saturne_email_template_fetch_all()
 * @return array<int, string[]>              Constant names by template rowid
 */
function saturne_email_template_usages(DoliDB $db, array $templates): array
{
    global $conf;

    $rowidsByLabel = [];
    foreach ($templates as $rowid => $template) {
        $rowidsByLabel[(string) $template->label][] = $rowid;
    }

    $sql  = 'SELECT name, value FROM ' . MAIN_DB_PREFIX . 'const';
    $sql .= ' WHERE entity IN (0, ' . ((int) $conf->entity) . ")";
    $sql .= " AND name LIKE '%MAIL%' AND value <> ''";

    $usages = [];
    $resql  = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ' ' . $db->lasterror(), LOG_ERR);
        return [];
    }
    while ($obj = $db->fetch_object($resql)) {
        $name = (string) $obj->name;
        if (!preg_match('/(^|_)(E?MAIL_TEMPLATE|E?MAIL_MODEL|TEMPLATE_E?MAIL)(_|$)/', $name) || preg_match('/_(SET|UPDATED)$/', $name)) {
            continue;
        }

        foreach (saturne_email_template_value_rowids(trim((string) $obj->value), $templates, $rowidsByLabel) as $rowid) {
            $usages[$rowid][$name] = $name;
        }
    }
    $db->free($resql);

    return array_map('array_values', $usages);
}

/**
 * Return the templates a setting value designates.
 *
 * @param  string                $value         Value of the constant
 * @param  array<int, stdClass>  $templates     Templates by rowid
 * @param  array<string, int[]>  $rowidsByLabel Template rowids by label
 * @return int[]                                Rowids of the designated templates
 */
function saturne_email_template_value_rowids(string $value, array $templates, array $rowidsByLabel): array
{
    if ($value === '') {
        return [];
    }

    if (ctype_digit($value)) {
        return isset($templates[(int) $value]) ? [(int) $value] : [];
    }

    if ($value[0] === '{' || $value[0] === '[') {
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }
        $rowids = [];
        array_walk_recursive($decoded, function ($item) use ($templates, $rowidsByLabel, &$rowids) {
            if (is_scalar($item)) {
                $rowids = array_merge($rowids, saturne_email_template_value_rowids(trim((string) $item), $templates, $rowidsByLabel));
            }
        });
        return array_values(array_unique($rowids));
    }

    if (isset($rowidsByLabel[$value])) {
        return $rowidsByLabel[$value];
    }

    // "label:type_template", the form of TICKET_NOTIFICATION_EMAIL_TEMPLATE
    $separator = strrpos($value, ':');
    if ($separator !== false && isset($rowidsByLabel[substr($value, 0, $separator)])) {
        $type = substr($value, $separator + 1);
        return array_values(array_filter($rowidsByLabel[substr($value, 0, $separator)], function ($rowid) use ($templates, $type) {
            return $templates[$rowid]->type_template === $type;
        }));
    }

    return [];
}

/**
 * Return a template text as a reader sees it: translation keys __(Key)__ and constants __[NAME]__ resolved.
 *
 * The other substitution keys (__REF__, __THIRDPARTY_NAME__...) depend on the object sent and stay as is.
 *
 * @param  string    $text  Subject or content of a template
 * @param  Translate $langs Output language
 * @return string           Readable text
 */
function saturne_email_template_readable(string $text, Translate $langs): string
{
    if ($text === '' || strpos($text, '__') === false) {
        return $text;
    }

    return make_substitutions($text, [], $langs);
}

/**
 * Return the label of a template as the email forms show it: a label between parentheses is a translation key.
 *
 * @param  string    $label Label stored in c_email_templates
 * @param  Translate $langs Output language
 * @return string           Readable label
 */
function saturne_email_template_label(string $label, Translate $langs): string
{
    $reg = [];
    if (preg_match('/\((.*)\)/', $label, $reg)) {
        return $langs->transnoentities($reg[1]);
    }

    return $label;
}

/**
 * Load the language files holding the translation keys of the templates.
 *
 * A template is translated on the page sending it, which has loaded the file of its element: the keys of
 * the templates shipped for members live in members.lang, those of partnerships in partnership.lang...
 * Those of a module come from the module that created the template.
 *
 * @param  Translate            $langs     Output language
 * @param  array<int, stdClass> $templates Templates by rowid
 * @return void
 */
function saturne_email_template_load_langs(Translate $langs, array $templates): void
{
    $domains = ['main', 'other', 'mails', 'companies', 'members', 'partnership', 'propal', 'orders', 'bills', 'sendings', 'receptions', 'interventions', 'supplier_proposal', 'projects', 'contracts', 'ticket', 'recruitment', 'eventorganization', 'agenda', 'trips', 'products'];

    foreach (array_unique(array_column($templates, 'module')) as $module) {
        if (!empty($module) && preg_match('/^[a-z0-9_]+$/', $module) && is_dir(dol_buildpath('/' . $module . '/langs', 0))) {
            $domains[] = $module . '@' . $module;
        }
    }

    $langs->loadLangs($domains);
}

/**
 * Build the email templates overview: templates grouped by element, ready to display.
 *
 * @param  DoliDB    $db    Database handler
 * @param  User      $user  User reading the overview
 * @param  Translate $langs Output language
 * @return array{groups: array<string, array{label: string, templates: array<int, array<string, mixed>>}>, total: int, inactive: int, used: int}
 */
function saturne_email_template_overview(DoliDB $db, User $user, Translate $langs): array
{
    $elements  = saturne_email_template_elements($db, $user, $langs);
    $templates = saturne_email_template_fetch_all($db, $user, $langs);
    $usages    = saturne_email_template_usages($db, $templates);

    saturne_email_template_load_langs($langs, $templates);

    // Owners, read in one query
    $owners  = [];
    $userIds = array_filter(array_unique(array_map(function ($template) {
        return (int) $template->fk_user;
    }, $templates)));
    if (!empty($userIds)) {
        $resql = $db->query('SELECT rowid, firstname, lastname, login FROM ' . MAIN_DB_PREFIX . 'user WHERE rowid IN (' . $db->sanitize(implode(',', $userIds)) . ')');
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $owners[(int) $obj->rowid] = dolGetFirstLastname((string) $obj->firstname, (string) $obj->lastname) ?: (string) $obj->login;
            }
            $db->free($resql);
        }
    }

    // Choosing a template by its label keeps the first one: an active namesake for the same element and language is never sent
    $namesakes = [];
    foreach ($templates as $template) {
        if ($template->active) {
            $key             = $template->type_template . '|' . $template->label . '|' . $template->lang;
            $namesakes[$key] = ($namesakes[$key] ?? 0) + 1;
        }
    }

    $groups   = [];
    $inactive = 0;
    $used     = 0;
    foreach ($templates as $rowid => $template) {
        $type = (string) $template->type_template;

        $available = isset($elements[$type]) && (int) dol_eval((string) $template->enabled, 1, 1, '1');
        $groupKey  = $available ? $type : SATURNE_EMAIL_TEMPLATE_UNAVAILABLE;
        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [
                'label'     => $available ? $elements[$type] : img_picto('', 'warning', 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans('EmailTemplatesUnavailable')),
                'templates' => [],
            ];
        }

        $label   = saturne_email_template_label((string) $template->label, $langs);
        $topic   = saturne_email_template_readable((string) $template->topic, $langs);
        $content = saturne_email_template_readable((string) $template->content, $langs);

        $variables = [];
        preg_match_all('/__([A-Z][A-Z0-9_]*)__/', $template->topic . ' ' . $template->content . ' ' . $template->content_lines, $variables);
        $variables = array_values(array_unique($variables[0]));
        sort($variables);

        $templateUsages = $usages[$rowid] ?? [];
        if (!$template->active) {
            $inactive++;
        }
        if (!empty($templateUsages)) {
            $used++;
        }

        $groups[$groupKey]['templates'][$rowid] = [
            'id'           => $rowid,
            'label'        => $label,
            // The raw key, under a label it was translated into: a key translated nowhere is already shown as is
            'code'         => (!in_array($label, [(string) $template->label, trim((string) $template->label, '()')], true) ? (string) $template->label : ''),
            'type'         => $type,
            'lang'         => (string) $template->lang,
            'module'       => (string) $template->module,
            'active'       => (int) $template->active,
            'private'      => (int) $template->private,
            'owner'        => $owners[(int) $template->fk_user] ?? '',
            'default'      => (int) $template->defaultfortype,
            'joinfiles'    => (int) $template->joinfiles,
            'duplicate'    => $template->active && ($namesakes[$type . '|' . $template->label . '|' . $template->lang] ?? 0) > 1,
            'from'         => (string) $template->email_from,
            'topic'        => $topic,
            'content'      => $content,
            'contentLines' => saturne_email_template_readable((string) $template->content_lines, $langs),
            'variables'    => $variables,
            'usages'       => $templateUsages,
            'search'       => dol_strtolower($label . ' ' . $template->label . ' ' . $type . ' ' . $topic . ' ' . implode(' ', $templateUsages) . ' ' . dol_trunc(dol_string_nohtmltag($content), 2000, 'right', 'UTF-8', 1)),
        ];
    }

    // Elements in alphabetical order, the templates for every element first and those offered nowhere last
    uksort($groups, function ($a, $b) use ($groups) {
        $rank = function ($key) {
            return $key === 'all' ? 0 : ($key === SATURNE_EMAIL_TEMPLATE_UNAVAILABLE ? 2 : 1);
        };
        if ($rank($a) !== $rank($b)) {
            return $rank($a) <=> $rank($b);
        }
        return strcasecmp(dol_string_unaccent(dol_string_nohtmltag($groups[$a]['label'])), dol_string_unaccent(dol_string_nohtmltag($groups[$b]['label'])));
    });

    return ['groups' => $groups, 'total' => count($templates), 'inactive' => $inactive, 'used' => $used];
}

/**
 * Activate or deactivate an email template.
 *
 * @param  DoliDB $db     Database handler
 * @param  int    $rowid  Template rowid
 * @param  int    $active 1 to activate, 0 to deactivate
 * @return int            1 if OK, < 0 if KO
 */
function saturne_email_template_set_active(DoliDB $db, int $rowid, int $active): int
{
    $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'c_email_templates SET active = ' . ($active ? 1 : 0);
    $sql .= ' WHERE rowid = ' . $rowid . ' AND entity IN (' . getEntity('email_template') . ')';

    if (!$db->query($sql)) {
        dol_syslog(__FUNCTION__ . ' ' . $db->lasterror(), LOG_ERR);
        return -1;
    }

    return 1;
}
