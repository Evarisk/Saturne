<?php

// Type stored in the document_model table, it may differ from the object type used by the config constants
$documentModelType = !empty($documentType) ? $documentType : $documentParentType;

// Select document models
$def = [];
$sql = 'SELECT nom';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'document_model';
$sql .= " WHERE type = '" . $documentModelType . "'";
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
        // A document type directory holds the models of every document it groups, and a model is
        // named after the document it builds: filtering on the directory name hid a sibling model
        if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
            $titleLabel = $langs->trans('DocumentTemplate' . $documentParentType);
            if ($titleLabel == 'DocumentTemplate' . $documentParentType) {
                $titleLabel = $langs->trans('DocumentTemplate');
            }
            print load_fiche_titre($titleLabel, '', '');

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
        // A document type directory holds the models of every document it groups, and a model is
        // named after the document it builds: filtering on the directory name hid a sibling model
        if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
            if (file_exists($dir . '/' . $file)) {
                $name       = substr($file, 4, dol_strlen($file) - 16);
                $customName = substr($file, 4, dol_strlen($file) - 20) . '_custom_odt';
                $classname  = substr($file, 0, dol_strlen($file) - 12);

                require_once $dir . '/' . $file;
                $module = new $classname($db);

                print '<tr class="oddeven"><td>';
                print (empty($module->name) ? $name : $module->name);
                print '</td><td>';
                // info() lists the ODT templates found in the model scan directory and offers to upload
                // one: a PDF model has no such directory, it inherits the method from SaturneDocumentModel
                // and advertised the templates of the ODT model shown right under it
                if ($module->type != 'pdf' && method_exists($module, 'info')) {
                    print $module->info($langs);
                } else {
                    print $module->description;
                }
                print '</td>';

                // PDF models do not scan a template directory: never store scandir as
                // description, otherwise saturne_get_list_of_models() would list one entry
                // per file found in that directory instead of a single model entry.
                // A PDF model class is standalone, it may not even declare the property.
                $modelScandir = ($module->type == 'pdf') ? '' : ($module->scandir ?? '');

                // Active
                print '<td class="center">';
                if (in_array($name, $def)) {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=del&model_name=' . $name . '&type=' . $documentModelType . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                    print img_picto($langs->trans('Enabled'), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=set&model_name=' . $name . '&const=' . $modelScandir . '&label=' . urlencode($module->name) . '&type=' . $documentModelType . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                    print img_picto($langs->trans('Disabled'), 'switch_off');
                    print '</a>';
                }
                print '</td>';

                // Default
                print '<td class="center">';
                $defaultModelConf = strtoupper($moduleName) . '_' . strtoupper($documentParentType) . '_DEFAULT_MODEL';
                if (getDolGlobalString($defaultModelConf) == $name) {
                    print img_picto($langs->trans('Default'), 'on');
                } else {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=setdoc&model_name=' . $name . '&const=' . $modelScandir . '&label=' . urlencode($module->name) . '&object_type=' . $documentParentType . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_picto($langs->trans('Disabled'), 'off') . '</a>';
                }
                print '</td>';

                // Info
                $htmlToolTip  = $langs->trans('Name') . ': ' . $module->name;
                $htmlToolTip .= '<br>' . $langs->trans('Type') . ': ' . ($module->type ?: $langs->trans('Unknown'));
                $htmlToolTip .= '<br>' . $langs->trans('Width') . '/' . $langs->trans('Height') . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
                $htmlToolTip .= '<br><br><u>' . $langs->trans('FeaturesSupported') . ':</u>';
                $htmlToolTip .= '<br>' . $langs->trans('Logo') . ': ' . yn($module->option_logo, 1, 1);
                print '<td class="center">';
                print $form->textwithpicto('', $htmlToolTip, -1, 'info');
                print '</td>';

                // Preview
                print '<td class="center">';
                if ($module->type == 'pdf') {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=specimen&model_name=' . $name . '&object_type=' . $documentParentType . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_object($langs->trans('Preview'), 'pdf') . '</a>';
                } else {
                    print img_object($langs->trans('PreviewNotAvailable'), 'generic');
                }
                print '</td></tr>';

                // Custom ODT document: only an ODT model scans a template directory. A PDF model
                // inherits info() and the custom template properties from SaturneDocumentModel, but its
                // name carries no _odt suffix, so the custom name built above is a truncation naming a
                // model that does not exist: its buttons registered and defaulted an unusable model.
                if ($module->type != 'pdf' && method_exists($module, 'info')) {
                    print '<tr class="oddeven"><td>';
                    print $langs->trans('CustomODT');
                    print '</td><td>';
                    $module->custom_info = true;
                    print $module->info($langs);
                    print '</td>';

                    // Active
                    print '<td class="center">';
                    if (in_array($customName, $def)) {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=del&model_name=' . $customName . '&type=' . $documentModelType . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                        print img_picto($langs->trans('Enabled'), 'switch_on');
                    } else {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=set&model_name=' . $customName . '&const=' . $module->custom_scandir . '&label=' . urlencode($module->custom_name) . '&type=' . $documentModelType . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
                        print img_picto($langs->trans('Disabled'), 'switch_off');
                    }
                    print '</a>';
                    print '</td>';

                    // Default
                    print '<td class="center">';
                    $defaultModelConf = strtoupper($moduleName) . '_' . strtoupper($documentParentType) . '_DEFAULT_MODEL';
                    if (getDolGlobalString($defaultModelConf) == $customName) {
                        print img_picto($langs->trans('Default'), 'on');
                    } else {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=setdoc&model_name=' . $customName . '&const=' . $module->custom_scandir . '&label=' . urlencode($module->custom_name) . '&object_type=' . $documentParentType . '&module_name=' . $moduleName . '&token=' . newToken() . '">' . img_picto($langs->trans('Disabled'), 'off') . '</a>';
                    }
                    print '</td><td colspan=2></td></tr>';
                }
            }
        }
    }
}
