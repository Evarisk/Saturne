<?php
// Select document models
$def = [];
$sql = 'SELECT nom';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'document_model';
$sql .= " WHERE type = '" . (!empty($documentType) ? $documentType : $documentParentType) . "'";
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

if (is_array($filelist) && !empty($filelist)) {
    foreach ($filelist as $file) {
        if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/' . $documentParentType . '/i', $file)) {
            print load_fiche_titre($langs->trans('DocumentTemplate'), '', '');

            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans('Name') . '</td>';
            print '<td>' . $langs->trans('Description') . '</td>';
            print '<td class="center">' . $langs->trans('Status') . '</td>';
            print '<td class="center">' . $langs->trans('Default') . '</td>';
            print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
            print '<td class="center">' . $langs->trans('Preview') . '</td>';
            print '</tr>';

            break;
        }
    }
}

if (is_array($filelist) && !empty($filelist)) {
    foreach ($filelist as $file) {
        if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/' . $documentParentType . '/i', $file)) {
            if (file_exists($dir . '/' . $file)) {
                $name       = substr($file, 4, dol_strlen($file) - 16);
                $customName = substr($file, 4, dol_strlen($file) - 20) . '_custom_odt';
                $classname  = substr($file, 0, dol_strlen($file) - 12);

                require_once $dir . '/' . $file;
                $module = new $classname($db);

                print '<tr class="oddeven"><td>';
                print (empty($module->name) ? $name : $module->name);
                print '</td><td>';
                if (method_exists($module, 'info')) {
                    print $module->info($langs);
                } else {
                    print $module->description;
                }
                print '</td>';

                // Active
                print '<td class="center">';
                if (in_array($name, $def)) {
                    print img_picto($langs->trans('Enabled'), 'switch_on');
                } else {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=set&model_name=' . $name . '&const=' . $module->scandir . '&label=' . urlencode($module->name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                    print img_picto($langs->trans('Disabled'), 'switch_off');
                    print '</a>';
                }
                print '</td>';

                // Default
                print '<td class="center">';
                $defaultModelConf = strtoupper($moduleName) . '_' . strtoupper($documentParentType) . '_DEFAULT_MODEL';
                if ($conf->global->$defaultModelConf == $name) {
                    print img_picto($langs->trans('Default'), 'on');
                } else {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=setdoc&model_name=' . $name .'&const=' . $module->scandir . '&label=' . urlencode($module->name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_picto($langs->trans('Disabled'), 'off') . '</a>';
                }
                print '</td>';

                // Info
                $htmlToolTip  = $langs->trans('Name') . ': ' . $module->name;
                $htmlToolTip .= '<br>' . $langs->trans('Type') . ': ' . ($module->type ?: $langs->trans('Unknown'));
                $htmlToolTip .= '<br>' . $langs->trans('Width') . '/' . $langs->trans('Height') . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
                $htmlToolTip .= '<br><br><u>' . $langs->trans('FeaturesSupported') . ':</u>';
                $htmlToolTip .= '<br>' . $langs->trans('Logo') . ': ' . yn($module->option_logo, 1, 1);
                print '<td class="center">';
                print $form->textwithpicto('', $htmlToolTip, -1, 0);
                print '</td>';

                // Preview
                print '<td class="center">';
                if ($module->type == 'pdf') {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=specimen&model_name=' . $name . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_object($langs->trans('Preview'), 'pdf') . '</a>';
                } else {
                    print img_object($langs->trans('PreviewNotAvailable'), 'generic');
                }
                print '</td></tr>';

                // Custom ODT document
                if (method_exists($module, 'info')) {
                    print '<tr class="oddeven"><td>';
                    print $langs->trans('CustomODT');
                    print '</td><td>';
                    $module->custom_info = true;
                    print $module->info($langs);
                    print '</td>';

                    // Active
                    print '<td class="center">';
                    if (in_array($customName, $def)) {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=del&model_name=' . $customName . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                        print img_picto($langs->trans('Enabled'), 'switch_on');
                    } else {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=set&model_name=' . $customName . '&const=' . $module->custom_scandir . '&label=' . urlencode($module->custom_name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                        print img_picto($langs->trans('Disabled'), 'switch_off');
                    }
                    print '</a>';

                    // Default
                    print '<td class="center">';
                    $defaultModelConf = strtoupper($moduleName) . '_' . strtoupper($documentParentType) . '_DEFAULT_MODEL';
                    if ($conf->global->$defaultModelConf == $customName) {
                        print img_picto($langs->trans('Default'), 'on');
                    } else {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=setdoc&model_name=' . $customName .'&const=' . $module->custom_scandir . '&label=' . urlencode($module->custom_name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_picto($langs->trans('Disabled'), 'off') . '</a>';
                    }
                    print '</td><td colspan=2></td></tr>';
                }
            }
        }
    }
}
