<?php

// Load Saturne unified environment (which internally loads Dolibarr main.inc.php and populates $conf->saturne)
require_once __DIR__ . '/../../saturne.main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../../lib/saturne.lib.php';
require_once __DIR__ . '/../../lib/dolibarr.lib.php';

global $conf, $user, $langs;

$action = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'alpha');

// Routing API inputs
$moduleToUse = GETPOST('module', 'alpha') ? GETPOST('module', 'alpha') : 'saturne';
$subDir = GETPOST('subdir', 'alpha');
$filePrefix = GETPOST('prefix') ? GETPOST('prefix') : ''; // Allowing all chars for prefixes
$rightString = GETPOST('rights', 'restricthtml');

// Check dynamically passed rights if present
if (!empty($rightString)) {
    // Example format expected: 'fraispro,ndf,creer' -> checks $user->rights->fraispro->ndf->creer
    $rightsArr = explode(',', $rightString);
    $hasRight = false;
    
    if (count($rightsArr) == 1 && !empty($user->rights->{$rightsArr[0]})) $hasRight = true;
    elseif (count($rightsArr) == 2 && !empty($user->rights->{$rightsArr[0]}->{$rightsArr[1]})) $hasRight = true;
    elseif (count($rightsArr) == 3 && !empty($user->rights->{$rightsArr[0]}->{$rightsArr[1]}->{$rightsArr[2]})) $hasRight = true;
    
    if (!$hasRight) {
        if ($subaction == 'save_image_override') { header('Content-Type: application/json'); echo json_encode(['status' => 'error', 'msg' => 'Access denied']); exit; }
        print '<div style="color: #e74c3c;">Accès refusé. Droits insuffisants.</div>';
        exit;
    }
}

// Build destination dynamic path
if (empty($conf->$moduleToUse->dir_output)) {
    // Fallback if target module has no dir_output (edge case during tests without activation)
    $destinationPath = $conf->ecm->dir_output . '/' . $moduleToUse . (empty($subDir) ? '' : '/' . $subDir);
} else {
    $destinationPath = $conf->$moduleToUse->dir_output . (empty($subDir) ? '' : '/' . $subDir);
}

// Ensure dir exists using unified security manager
$resMkdir = saturne_manage_storage_dir($destinationPath);
if ($resMkdir < 0) {
    http_response_code(500);
    print 'Saturne-1006';
    exit;
}

if ($subaction == 'save_image_override') {
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $base64 = isset($_POST['base64']) ? $_POST['base64'] : '';
    
    header('Content-Type: application/json');
    if (empty($filename)) { echo json_encode(['status' => 'error', 'msg' => 'Nom du fichier manquant']); exit; }
    if (empty($base64)) { echo json_encode(['status' => 'error', 'msg' => 'Données image manquantes']); exit; }
    
    // In override, file already exists, prefix is naturally part of the filename fetched from canvas.
    
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);
        if ($data !== false) {
             $bytes = file_put_contents($destinationPath . '/' . $filename, $data);
             if ($bytes === false) {
                 echo json_encode(['status' => 'error', 'msg' => 'Echec ecriture fichier sur disque']); exit;
             }
             // regenerate thumb visually
             $confWidthSmall  = !empty($conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL : 120;
             $confHeightSmall = !empty($conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL : 120;
             saturne_vignette($destinationPath . '/' . $filename, $confWidthSmall, $confHeightSmall, '_small');
             echo json_encode(['status' => 'ok']);
             exit;
        } else { echo json_encode(['status' => 'error', 'msg' => 'Echec decryptage base64']); exit; }
    } else { echo json_encode(['status' => 'error', 'msg' => 'Format base64 non reconnu ou entete manque']); exit; }
}

if ($action == 'add_audio') {
    if (!empty($_FILES['audio']['tmp_name'])) {
        $fileName = (!empty($filePrefix) ? $filePrefix : '') . dol_print_date(dol_now(), 'dayhourlog') . '_' . rand(1, 9999) . '_audio.wav';
        move_uploaded_file($_FILES['audio']['tmp_name'], $destinationPath . '/' . $fileName);
        print '<div id="recording-indicator" style="font-size:11px; margin-left: 5px; color: #2ecc71;">' . $langs->trans('Saved') . '</div>';
    } else { print '<div id="recording-indicator" style="font-size:11px; margin-left: 5px; color: #e74c3c;">Erreur upload</div>'; }
    exit;
}

if ($subaction == 'add_img') {
    $objectSubType = GETPOST('objectSubType', 'aZ09');
    if (empty($objectSubType)) $objectSubType = 'media_dyn'; // Fallback
    
    if (!empty($_FILES['img']['error']) || empty($_FILES['img']['tmp_name'])) {
        $errCode = isset($_FILES['img']['error']) ? $_FILES['img']['error'] : 'MISSING';
        $errMsg = 'Erreur inconnue';
        switch ($errCode) {
            case UPLOAD_ERR_INI_SIZE:   $errMsg = 'Le fichier dépasse la limite upload_max_filesize de votre serveur (' . ini_get('upload_max_filesize') . ').'; break;
            case UPLOAD_ERR_FORM_SIZE:  $errMsg = 'Le fichier dépasse la contrainte de taille côté client.'; break;
            case UPLOAD_ERR_PARTIAL:    $errMsg = 'Le fichier n\'a été que partiellement téléchargé.'; break;
            case UPLOAD_ERR_NO_FILE:    $errMsg = 'Aucun fichier n\'a été téléchargé.'; break;
            case UPLOAD_ERR_NO_TMP_DIR: $errMsg = 'Dossier temporaire manquant sur votre serveur.'; break;
            case UPLOAD_ERR_CANT_WRITE: $errMsg = 'Échec de l\'écriture du fichier sur le disque du serveur.'; break;
            case UPLOAD_ERR_EXTENSION:  $errMsg = 'Une extension PHP a stoppé l\'upload du fichier.'; break;
            case 'MISSING':             $errMsg = 'Le fichier (tmp_name) est bloqué et absent, potentiellement dû à une limite post_max_size atteinte (' . ini_get('post_max_size') . ') !'; break;
        }
        
        // Wrap the error so JS can display it gracefully in place of the camera button
        print '<div class="linked-medias ' . htmlspecialchars($objectSubType) . '">';
        print '  <p style="margin-top: 10px; color: #e74c3c; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Échec de l\'upload PHP. Code: ' . $errCode . ' <br><span style="font-size:12px; font-weight:normal;">' . $errMsg . '</span></p>';
        print '</div>';
    } else {
        $decodedImage = file_get_contents($_FILES['img']['tmp_name']);
        
        $fileName = (!empty($filePrefix) ? $filePrefix : '') . dol_print_date(dol_now(), 'dayhourlog') . '_' . rand(1, 9999) . '_media.jpeg';
        
        $bytes = @file_put_contents($destinationPath . '/' . $fileName, $decodedImage);
        if ($bytes === false) {
            http_response_code(500);
            print 'Saturne-1007';
            exit;
        }
        
        $confWidthSmall  = !empty($conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_WIDTH_SMALL : 120;
        $confHeightSmall = !empty($conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL) ? $conf->global->SATURNE_MEDIA_MAX_HEIGHT_SMALL : 120;
        $resVignette = saturne_vignette($destinationPath . '/' . $fileName, $confWidthSmall, $confHeightSmall, '_small');
        
        if (empty($resVignette) || (is_string($resVignette) && strpos($resVignette, 'Error') !== false)) {
            http_response_code(500);
            print 'Saturne-1008';
            exit;
        }
        
        // Generate thumbnail HTML natively using the primary UI renderer to guarantee container classes match
        print saturne_render_media_block($moduleToUse, $subDir, $filePrefix, $rightString, ['show_photo'=>true, 'show_audio'=>true]);
        
        exit;
    }
}

if ($subaction == 'add_audio') {
    $objectSubType = GETPOST('objectSubType', 'aZ09') ?: 'media_dyn_audio';
    
    if (!empty($_FILES['audio']['error']) || empty($_FILES['audio']['tmp_name'])) {
        http_response_code(400);
        print 'Saturne-1500';
        exit;
    }
    
    $decodedAudio = file_get_contents($_FILES['audio']['tmp_name']);
    $fileName = (!empty($filePrefix) ? $filePrefix : '') . dol_print_date(dol_now(), 'dayhourlog') . '_' . rand(1, 9999) . '_audio.wav';
    
    $bytes = @file_put_contents($destinationPath . '/' . $fileName, $decodedAudio);
    if ($bytes === false) {
        http_response_code(500);
        print 'Saturne-1007';
        exit;
    }
    
    print saturne_render_media_block($moduleToUse, $subDir, $filePrefix, $rightString, ['show_photo'=>false, 'show_audio'=>true]);
    exit;
}

if ($subaction == 'delete_media') {
    $filename = GETPOST('filename', 'restricthtml');
    
    if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        http_response_code(400);
        print 'Saturne-1501';
        exit;
    }
    
    $fullPath = $destinationPath . '/' . $filename;
    
    if (file_exists($fullPath)) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        dol_delete_file($fullPath, 0, 0, 1);
        
        // Clean up associated thumbnails/metadata for images just in case it's used generically
        dol_delete_file($fullPath . '.meta', 0, 0, 1);
        $pi = pathinfo($filename);
        if (isset($pi['filename']) && isset($pi['extension'])) {
            $smallFile = $destinationPath . '/' . $pi['filename'] . '_small.' . $pi['extension'];
            if (file_exists($smallFile)) {
                dol_delete_file($smallFile, 0, 0, 1);
            }
        }
        
        http_response_code(200);
        print 'OK';
    } else {
        http_response_code(404);
        print 'Saturne-1009';
    }
    exit;
}
