<?php
print load_fiche_titre($langs->trans('DocumentTemplate'), '', '');

// Select document models
$def = [];
$sql = 'SELECT nom';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'document_model';
$sql .= " WHERE type = '" . (!empty($documentParentType) ? $documentParentType : $documentType) . "'";
$sql .= ' AND entity = ' . $conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows) {
        $array = $db->fetch_array($resql);
        $def[] = $array[0];
        $i++;
    }
} else {
    dol_print_error($db);
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('Default') . '</td>';
print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
print '<td class="center">' . $langs->trans('Preview') . '</td>';
print '</tr>';

if (is_array($filelist) && !empty($filelist)) {
    foreach ($filelist as $file) {
        if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/' . $documentParentType . '/i', $file) && preg_match('/odt/i', $file)) {
            if (file_exists($dir.'/'.$file)) {
                $name      = substr($file, 4, dol_strlen($file) - 16);
                $classname = substr($file, 0, dol_strlen($file) - 12);

                require_once $dir  . '/' . $file;
                $module = new $classname($db);

                $modulequalified = 1;
                if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
                    $modulequalified = 0;
                }
                if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
                    $modulequalified = 0;
                }

                if ($modulequalified) {
                    print '<tr class="oddeven"><td>';
                    print (empty($module->name) ? $name : $module->name);
                    print '</td><td>';
                    if (method_exists($module, 'info')) {
                        print $module->info($langs);
                    }else {
                        print $module->description;
                    }
                    print '</td>';

                    // Active
                    print '<td class="center">';

                    if (in_array($name, $def)) {
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del&value=' . $name . '&const=' . $module->scandir . '&label=' . urlencode($module->name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                        print img_picto($langs->trans('Enabled'), 'switch_on');
                    } else {
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set&value=' . $name . '&const=' . $module->scandir . '&label=' . urlencode($module->name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                        print img_picto($langs->trans('Disabled'), 'switch_off');
                    }
                    print '</a>';
                    print '</td>';

                    // Default
                    print '<td class="center">';
                    $defaultModelConf = strtoupper($moduleName) . '_' . strtoupper($documentParentType) . '_DEFAULT_MODEL';
                    if ($conf->global->$defaultModelConf == $name) {
                        print img_picto($langs->trans('Default'), 'on');
                    } else {
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=setdoc&value=' . $name .'&const=' . $module->scandir . '&label=' . urlencode($module->name) . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_picto($langs->trans('Disabled'), 'off') . '</a>';
                    }
                    print '</td>';

                    // Info
                    $htmltooltip = ''.$langs->trans('Name') . ': ' . $module->name;
                    $htmltooltip .= '<br>'.$langs->trans('Type') . ': ' . ($module->type ?: $langs->trans('Unknown'));
                    $htmltooltip .= '<br>'.$langs->trans('Width') . '/' . $langs->trans('Height') . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
                    $htmltooltip .= '<br><br><u>' . $langs->trans('FeaturesSupported') . ':</u>';
                    $htmltooltip .= '<br>' . $langs->trans('Logo') . ': ' . yn($module->option_logo, 1, 1);
                    print '<td class="center">';
                    print $form->textwithpicto('', $htmltooltip, -1, 0);
                    print '</td>';

                    // Preview
                    print '<td class="center">';
                    if ($module->type == 'pdf') {
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=specimen&module=' . $name . '&module_name=' . $moduleName . '">' . img_object($langs->trans('Preview'), 'intervention') . '</a>';
                    } else {
                        print img_object($langs->trans('PreviewNotAvailable'), 'generic');
                    }
                    print '</td>';
                    print '</tr>';
                }
            }
        }
    }
}
print '</table>';