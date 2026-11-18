<?php

if ($action == 'pdfGeneration') {
    global $langs;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $moduleNameUpperCase = dol_strtoupper($moduleName);
    $filename = GETPOST('file');

    $file = $upload_dir . '/' . $filename;

    // Open and load template
    require_once ODTPHP_PATH . 'odf.php';
    try {
        $odfHandler = new odf(
            $file,
            array(
                'PATH_TO_TMP'     => $conf->$moduleNameLowerCase->dir_temp,
                'ZIP_PROXY'       => getDolGlobalString('MAIN_ODF_ZIP_PROXY', 'PclZipProxy'), // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
                'DELIMITER_LEFT'  => '{',
                'DELIMITER_RIGHT' => '}'
            )
        );
    } catch (Exception $e) {
        $error = $e->getMessage();
        dol_syslog($e->getMessage(), LOG_INFO);

        return -1;
    }

    require_once __DIR__ . '/../../../lib/documents.lib.php';

    // A document named before #1885 can carry a character the conversion command breaks on ("&",
    // quote...): LibreOffice reads nothing and the PDF never appears. It is converted from a copy
    // written under a safe name next to it, the copy is removed afterwards
    $fileInfos   = pathinfo($filename);
    $safeName    = saturne_sanitize_document_file_name($fileInfos['basename']);
    $convertFile = $upload_dir . '/' . $fileInfos['dirname'] . '/' . $safeName;
    $pdfName     = pathinfo($safeName, PATHINFO_FILENAME) . '.pdf';

    $manualPdfGenerationConf = $moduleNameUpperCase . '_MANUAL_PDF_GENERATION';

    // Write new file
    if (! empty($conf->global->MAIN_ODT_AS_PDF) && $conf->global->$manualPdfGenerationConf > 0) {
        try {
            $odfHandler->exportAsAttachedPDF($safeName === $fileInfos['basename'] ? $file : $convertFile);

            // LibreOffice exits with code 0 even when it could not load the source file,
            // so the PDF has to be checked before announcing a success
            if (!file_exists($upload_dir . '/' . $fileInfos['dirname'] . '/' . $pdfName)) {
                throw new Exception('ODT to PDF conversion did not generate any file : ' . $pdfName);
            }

            $documentUrl = DOL_URL_ROOT . '/document.php';
            setEventMessages($langs->trans("FileGenerated") . ' - ' . '<a href=' .  $documentUrl . '?modulepart=' . $moduleNameLowerCase . '&amp;file=' . urlencode($fileInfos['dirname'] . '/' . $pdfName) . '&entity=' . $conf->entity . '"' . '>' . $pdfName . '</a>', null);
        } catch (Exception $e) {
            $error = $e->getMessage();
            setEventMessages($langs->transnoentities('FileCouldNotBeGeneratedInPDF') . '<br>' . $langs->transnoentities('CheckDocumentationToEnablePDFGeneration'), null, 'errors');
            dol_syslog($e->getMessage(), LOG_INFO);
        }

        if ($safeName !== $fileInfos['basename'] && file_exists($convertFile)) {
            dol_delete_file($convertFile);
        }
    } else {
        try {
            $odfHandler->saveToDisk($file);
        } catch (Exception $e) {
            $error = $e->getMessage();
            dol_syslog($e->getMessage(), LOG_INFO);
            return -1;
        }
    }
}
