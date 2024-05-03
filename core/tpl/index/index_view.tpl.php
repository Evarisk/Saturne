<?php
// Global variables definitions.
global $conf, $db, $langs, $hookmanager, $moduleName, $moduleNameLowerCase, $user;

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/parsedown/Parsedown.php';

// Load Saturne libraries.
if (!isset($showDashboard) || $showDashboard === true) {
    require_once __DIR__ . '/../../../class/saturnedashboard.class.php';
}

// Load Module libraries.
require_once __DIR__ . '/../../../../' . $moduleNameLowerCase . '/core/modules/mod' . $moduleName . '.class.php';

// Load translation files required by the page.
saturne_load_langs();

// Get parameters.
$action = GETPOST('action', 'aZ09');

// Initialize technical objects.
$classname = 'mod' . $moduleName;
$modModule = new $classname($db);
$parse     = new Parsedown();
if (!isset($showDashboard) || $showDashboard === true) {
    $dashboard = new SaturneDashboard($db, $moduleNameLowerCase);
}

$upload_dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1];

$hookmanager->initHooks([$moduleNameLowerCase . 'index', 'globalcard']); // Note that conf->hooks_modules contains array.

// Security check.
$permissiontoread = $user->rights->$moduleNameLowerCase->read;
saturne_check_access($permissiontoread, null, true);

/*
 * Actions
*/

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks.
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    if ($action == 'closenotice') {
        dolibarr_set_const($db, strtoupper($moduleName) . '_SHOW_PATCH_NOTE', 0, 'integer', 0, '', $conf->entity);
    }

    if ($action == 'adddashboardinfo' || $action == 'closedashboardinfo') {
        $data                = json_decode(file_get_contents('php://input'), true);
        $dashboardWidgetName = $data['dashboardWidgetName'];
        $confName            = strtoupper($moduleName) . '_DISABLED_DASHBOARD_INFO';
        $visible             = json_decode($user->conf->$confName);

        if ($action == 'adddashboardinfo') {
            unset($visible->$dashboardWidgetName);
        } else {
            $visible->$dashboardWidgetName = 0;
        }

        $tabparam[$confName] = json_encode($visible);

        dol_set_user_param($db, $conf, $user, $tabparam);
        $action = '';
    }

    if ($action == 'generate_csv') {
        parse_str(GETPOST('graph'), $data);

        if (!empty($data)) {
            $now   = dol_now();
            $value = $data['title'];

            $titleName = strip_tags($value);
            $titleName = str_replace(' ', '_', $titleName);
            $titleName = dol_sanitizeFileName(dol_strtolower($titleName));
            $fileName  = dol_print_date($now, 'dayxcard') . '_' . $titleName . '.csv';

            $labels  = $data['labels'];
            $dataset = $data['data'];

            $mode = 0; // Two-dimension graph
            $line = 1;

            $fp = fopen($upload_dir . '/temp/' . $fileName, 'w');

            // Empty line and title
            fputcsv($fp, []);
            fputcsv($fp, [$line => '', 2 => $value]);

            $header = [0 => ''];
            if (is_array($labels) && !empty($labels)) {
                if (is_array($dataset) && !empty($dataset)) {
                    $mode = 1;
                }
                foreach ($labels as $label) {
                    $line++;
                    $header[$line] = $label['label'];
                }
            }
            fputcsv($fp, $header);

            $line       = 0;
            $labelIndex = 1;

            if ($mode == 1 && !empty($dataset) && !empty($labels)) {
                foreach ($labels as $labelArray) {
                    foreach ($dataset as $values) {
                        if (!empty($values['y_combined_' . $labelArray['label']])) {
                            $i = 0;
                            foreach($values as $key => $value) {
                                $i++;
                                $content[$i] = $value;
                            }
                        } else if (!empty($values[$labelIndex])) {
                            $content[$line + 1] = $values[$labelIndex];
                        } else if (!empty($values[$line])) {
                            $content[$line + 1] = $values[1];
                        } else {
                            $content[$line + 1] = 0;
                        }

                        fputcsv($fp, $content);
                    }
                    $labelIndex++;
                    if (!empty($values['y_combined_' . $labelArray['label']])) {
                        break;
                    }
                }
                $line++;
            } else if (!empty($dataset)) {
                $content = [$line => ''];
                foreach ($dataset as $value) {
                    $line++;
                    $content[$line] = $value;
                }
                fputcsv($fp, $content);
            }

            fputcsv($fp, []);
            fclose($fp);

            $documentUrl = DOL_URL_ROOT . '/document.php';
            header("Location: " . $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&file=' . urlencode('temp/' . $fileName) . '&entity=' . $conf->entity);
            exit;
        } else {
            setEventMessages($langs->trans('ErrorMissingData'), [], 'errors');
        }
        $action = '';
    }
}

/*
 * View
 */

$title   = $langs->trans('ModuleArea', $moduleName);
$helpUrl = 'FR:Module_' . $moduleName;

saturne_header(0, '', $title . ' ' . $modModule->version, $helpUrl);

print load_fiche_titre($title . ' ' . $modModule->version, $morehtmlright, $moduleNameLowerCase . '_color.png@' . $moduleNameLowerCase);

$moduleJustUpdated   = strtoupper($moduleName) . '_JUST_UPDATED';
$moduleVersion       = strtoupper($moduleName) . '_VERSION';
$moduleShowPatchNote = strtoupper($moduleName) . '_SHOW_PATCH_NOTE';


if ($conf->global->$moduleVersion != $modModule->version) {
    $modModule->remove();
    $modModule->init();

    dolibarr_set_const($db, $moduleJustUpdated, 1, 'integer', 0, '', $conf->entity);
    dolibarr_set_const($db, $moduleShowPatchNote, 1, 'integer', 0, '', $conf->entity);
}

if ($conf->global->$moduleJustUpdated == 1) : ?>
    <div class="wpeo-notice notice-success">
        <div class="notice-content">
            <div class="notice-subtitle"><strong><?php echo $langs->trans('ModuleUpdate', $moduleName); ?></strong>
                <?php echo $langs->trans('ModuleHasBeenUpdatedTo', $moduleName, $modModule->version) ?>
            </div>
        </div>
    </div>
    <?php dolibarr_set_const($db, $moduleJustUpdated, 0, 'integer', 0, '', $conf->entity);
endif;

if ($conf->global->$moduleShowPatchNote > 0) : ?>
    <div class="wpeo-notice notice-info">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <div class="notice-content">
            <div class="notice-title"><?php echo $langs->trans('ModulePatchNote', $moduleName, $modModule->version); ?>
                <div class="show-patchnote wpeo-button button-square-40 button-blue wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('ShowPatchNote'); ?>">
                    <input hidden class="modal-options" data-modal-to-open="patch-note">
                    <i class="fas fa-list button-icon"></i>
                </div>
            </div>
        </div>
        <div class="notice-close notice-close-forever wpeo-tooltip-event" aria-label="<?php echo $langs->trans('DontShowPatchNote'); ?>" data-direction="left"><i class="fas fa-times"></i></div>
    </div>

    <div class="wpeo-modal wpeo-modal-patchnote" id="patch-note">
        <div class="modal-container wpeo-modal-event">
            <!-- Modal-Header -->
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('ModulePatchNote', $moduleName, $modModule->version);  ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>
            <!-- Modal Content-->
            <div class="modal-content">
                <?php $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . strtolower($modModule->editor_name) . '/' . (!empty($moreParams['specialModuleNameLowerCase']) ? $moreParams['specialModuleNameLowerCase'] : $moduleNameLowerCase) . '/releases/tags/' . $modModule->version);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, $moduleName);
                $output = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($output);
                $data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
                $data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
                $html = $parse->text($data->body);
                print $html;
                ?>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer">
                <div class="wpeo-button button-grey button-uppercase modal-close">
                    <span><?php echo $langs->trans('CloseModal'); ?></span>
                </div>
            </div>
        </div>
    </div>
<?php endif;

$parameters = [];
$reshook    = $hookmanager->executeHooks('saturneIndex', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
    print $hookmanager->resPrint;
}

if (!isset($showDashboard) || $showDashboard === true) {
    $dashboard->show_dashboard($moreParams ?? []);
}

// End of page
llxFooter();
$db->close();
