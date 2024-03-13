<?php
print load_fiche_titre($langs->trans('DocumentTemplate'), '', '');

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

$saturneDocumentModel = new SaturneDocumentModel($db, $module->name);
$modellist            = $saturneDocumentModel->liste_modeles($db, $type);
$modellist            = is_array($modellist) ? $modellist : [];

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
        if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/' . $documentParentType . '/i', $file)) {

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
                    print '&nbsp; <a class="reposition" href="'. $_SERVER['PHP_SELF'] . '?module_name='. $moduleName .'&action=download_template&type='. dol_strtolower($type) . '&filename=template_'. str_replace('_odt', '.odt', urlencode(basename($name))) .'">'.img_picto('', 'listlight').'</a>';
                    //print '&nbsp;<a class="reposition" href="download.php?path='.DOL_DOCUMENT_ROOT.'/custom/' . $moduleNameLowerCase . '/documents/doctemplates/' . dol_strtolower($type) .'/template_'.str_replace('_odt', '.odt', urlencode(basename($name))).'">'.img_picto('', 'listlight').'</a>';
                    print '</td><td>';
                    if (method_exists($module, 'info')) {
                        print $module->info($langs);
                    }else {
                        print $module->description;
                    }
                    print '</td>';

                    // Active
                    print '<td class="center">';

                    if (in_array($name, $def) && (array_search('index.php', $modellist))) {
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
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=specimen&module=' . $name . '&module_name=' . $moduleName . '">' . img_object($langs->trans('Preview'), 'pdf') . '</a>';
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

$value = dol_strtoupper($moduleName) . '_'. dol_strtoupper($type) .'_CUSTOM_ADDON_ODT_PATH';

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setModuleOptions">';
print '<input type="hidden" name="keyforuploaddir" value="' . $value . '">';
print '<input type="hidden" name="value1" value="' . $conf->global->$value . '">';
print '<input type="hidden" name="module_name" value="' . $moduleName . '">';

// List of directories area
print '<tr><td>';
print $langs->trans('CustomODT');
print '</td>';
print '<td>';

$texttitle   = $langs->trans('ListOfDirectories');
$listofdir   = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->$value)));
$listoffiles = [];
foreach ($listofdir as $key => $tmpdir) {
    $tmpdir = trim($tmpdir);
    $tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
    if ( ! $tmpdir) {
        unset($listofdir[$key]); continue;
    }
    if ( ! is_dir($tmpdir)) $texttitle .= img_warning($langs->trans('ErrorDirNotFound', $tmpdir), 0);
    else {
        $tmpfiles                          = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
        if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
    }
}
$texthelp = $langs->trans('ListOfDirectoriesForModelGenODT');
// Add list of substitution keys
$texthelp .= '<br>' . $langs->trans('FollowingSubstitutionKeysCanBeUsed') . '<br>';
$texthelp .= $langs->transnoentitiesnoconv('FullListOnOnlineDocumentation'); // This contains an url, we don't modify it

print $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1);
print '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
print '<span class="flat" style="font-weight: bold">';
print $conf->global->$value;
print '</span>';
print '</div><div style="display: inline-block; vertical-align: middle;">';
print '<br></div></div>';

// Scan directories
$nbofiles = count($listoffiles);
if (!empty($conf->global->$value)) {
    print $langs->trans('NumberOfModelFilesFound') . ': <b>';
    print count($listoffiles);
    print '</b>';
}
if ($nbofiles) {
    print '<div id="div_' . get_class($object) . '" class="hiddenx">';
    // Show list of found files
    foreach ($listoffiles as $file) {
        print '- '.$file['name'];
        print ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=ecm&keyforuploaddir='. $value .'&action=deletefile&token='.newToken().'&file='.urlencode(basename($file['name'])).'">'.img_picto('', 'delete').'</a>';
        print '<br>';
    }
    print '</div>';
}
// Add input to upload a new template file.
print '<div>' . $langs->trans('UploadNewTemplate') . ' <input type="file" name="userfile">';
print '<input type="hidden" value='. $value .' name="keyforuploaddir">';
print '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Upload')) . '" name="upload">';
print '</div>';
print '</td>';

// Active
print '<td class="center">';

if (array_search('index.php', $modellist) || empty($modellist)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set&value=' . $name . '&const=' . $value . '&label=' . urlencode($module->name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
    print img_picto($langs->trans('Disabled'), 'switch_off');
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del&value=' . $name . '&const=' . $value . '&label=' . urlencode($module->name) . '&type=' . explode('_', $name)[0] . '&module_name=' . $moduleName . '&token=' . newToken() . '">';
    print img_picto($langs->trans('Enabled'), 'switch_on');
}
print '</a>';
print '</td>';

print '<td colspan=3></td>';

print '</tr>';

print '</table>';
print '</form>';
