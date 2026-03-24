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
 * \file    lib/dolibarr.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions from Dolibarr to rewrite or overload in Saturne
 */

/**
 * Need rewrite getImageFileNameForSize form functions.lib because we need to use this function for medium/large size images
 * but this function have strict check on extName parameter (only '', '_small', '_mini' are allowed)
 * Moreover, we need to rewrite vignette due to the same reason
 *
 * @see getImageFileNameForSize
 * @see vignette
*/

/**
 * Return the filename of file to get the thumbs
 *
 * @param   string  $file           Original filename (full or relative path)
 * @param   string  $extName        Extension to differentiate thumb file name ('', '_small', '_mini', '_medium', '_large')
 * @param   string  $extImgTarget   Force image extension for thumbs. Use '' to keep same extension than original image (default).
 * @return  string                  New file name (full or relative path, including the thumbs/). May be the original path if no thumb can exists.
 */
function saturne_get_image_file_name_for_size($file, $extName, $extImgTarget = '')
{
    $dirName = dirname($file);
    if ($dirName == '.') {
        $dirName = '';
    }

    if (!in_array($extName, array('', '_small', '_mini', '_medium', '_large'))) {
        return 'Bad parameter extName';
    }

    $fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp|\.webp)$/i', '', $file); // We remove image extension, whatever is its case
    $fileName = basename($fileName);

    if (empty($extImgTarget)) {
        $extImgTarget = (preg_match('/\.jpg$/i', $file) ? '.jpg' : '');
    }
    if (empty($extImgTarget)) {
        $extImgTarget = (preg_match('/\.jpeg$/i', $file) ? '.jpeg' : '');
    }
    if (empty($extImgTarget)) {
        $extImgTarget = (preg_match('/\.gif$/i', $file) ? '.gif' : '');
    }
    if (empty($extImgTarget)) {
        $extImgTarget = (preg_match('/\.png$/i', $file) ? '.png' : '');
    }
    if (empty($extImgTarget)) {
        $extImgTarget = (preg_match('/\.bmp$/i', $file) ? '.bmp' : '');
    }
    if (empty($extImgTarget)) {
        $extImgTarget = (preg_match('/\.webp$/i', $file) ? '.webp' : '');
    }

    if (!$extImgTarget) {
        return $file;
    }

    $subdir = '';
    if ($extName) {
        $subdir = 'thumbs/';
    }

    return ($dirName ? $dirName . '/' : '') . $subdir . $fileName . $extName . $extImgTarget; // New filename for thumb
}

/**
 *    	Create a thumbnail from an image file (Supported extensions are gif, jpg, png and bmp).
 *      If file is myfile.jpg, new file may be myfile_small.jpg. But extension may differs if original file has a format and an extension
 *      of another one, like a.jpg file when real format is png.
 *
 *    	@param     string	$file           	Path of source file to resize
 *    	@param     int		$maxWidth       	Maximum width of the thumbnail (-1=unchanged, 160 by default)
 *    	@param     int		$maxHeight      	Maximum height of the thumbnail (-1=unchanged, 120 by default)
 *    	@param     string	$extName        	Extension to differentiate thumb file name ('_small', '_mini')
 *    	@param     int		$quality        	Quality after compression (0=worst so better compression, 100=best so low or no compression)
 *      @param     string	$outdir           	Directory where to store thumb
 *      @param     int		$targetformat     	New format of target (IMAGETYPE_GIF, IMAGETYPE_JPG, IMAGETYPE_PNG, IMAGETYPE_BMP, IMAGETYPE_WBMP ... or 0 to keep original format)
 *    	@return    string|int<0,0>				Full path of thumb or '' if it fails or 'Error...' if it fails, or 0 if it fails to detect the type of image
 */
function saturne_vignette($file, $maxWidth = 160, $maxHeight = 120, $extName = '_small', $quality = 50, $outdir = 'thumbs', $targetformat = 0)
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

    global $langs;

    dol_syslog("vignette file=".$file." extName=".$extName." maxWidth=".$maxWidth." maxHeight=".$maxHeight." quality=".$quality." outdir=".$outdir." targetformat=".$targetformat);

    // Clean parameters
    $file = dol_sanitizePathName(trim($file));

    // Check parameters
    if (!$file) {
        // If the file has not been indicated
        return 'ErrorBadParameters';
    } elseif (image_format_supported($file) < 0) {
        dol_syslog('This file '.$file.' does not seem to be a supported image file name (bad extension).', LOG_WARNING);
        return 'ErrorBadImageFormat';
    } elseif (!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < -1) {
        // If max width is incorrect (not numeric, empty, or less than 0)
        dol_syslog('Wrong value for parameter maxWidth', LOG_ERR);
        return 'Error: Wrong value for parameter maxWidth';
    } elseif (!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < -1) {
        // If max height is incorrect (not numeric, empty, or less than 0)
        dol_syslog('Wrong value for parameter maxHeight', LOG_ERR);
        return 'Error: Wrong value for parameter maxHeight';
    }

    $filetoread = realpath(dol_osencode($file)); // Absolute canonical path of image

    if (!file_exists($filetoread)) {
        // If the file passed in parameter does not exist
        dol_syslog($langs->trans("ErrorFileNotFound", $filetoread), LOG_ERR);
        return $langs->trans("ErrorFileNotFound", $filetoread);
    }

    $infoImg = getimagesize($filetoread); // Get information like size and real format of image. Warning real format may be png when extension is .jpg
    $imgWidth = $infoImg[0]; 	// Width of image
    $imgHeight = $infoImg[1]; 	// Height of image

    // TODO LDR
    //if $infoImg[2] != extension of file $file, return a string 'Error: content of file has a format that differs of the format of its extension

    $ort = false;
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($filetoread);
        if ($exif && !empty($exif['Orientation'])) {
            $ort = $exif['Orientation'];
        }
    }

    if ($maxWidth == -1) {
        $maxWidth = $infoImg[0]; // If size is -1, we keep unchanged
    }
    if ($maxHeight == -1) {
        $maxHeight = $infoImg[1]; // If size is -1, we keep unchanged
    }

    // If the image is smaller than the maximum width and height, no thumbnail is created.
    if ($infoImg[0] < $maxWidth && $infoImg[1] < $maxHeight) {
        // On cree toujours les vignettes
        dol_syslog("File size is smaller than thumb size", LOG_DEBUG);
        //return 'Le fichier '.$file.' ne necessite pas de creation de vignette';
    }

    $imgfonction = '';
    switch ($infoImg[2]) {
        case IMAGETYPE_GIF:	    // 1
            $imgfonction = 'imagecreatefromgif';
            break;
        case IMAGETYPE_JPEG:    // 2
            $imgfonction = 'imagecreatefromjpeg';
            break;
        case IMAGETYPE_PNG:	    // 3
            $imgfonction = 'imagecreatefrompng';
            break;
        case IMAGETYPE_BMP:	    // 6
            // Not supported by PHP GD
            break;
        case IMAGETYPE_WBMP:	// 15
            $imgfonction = 'imagecreatefromwbmp';
            break;
        case IMAGETYPE_WEBP:	// 18
            $imgfonction = 'imagecreatefromwebp';
            break;
    }
    if ($imgfonction) {
        if (!function_exists($imgfonction)) {
            // Conversion functions not present in this PHP
            return 'Error: Creation of thumbs not possible. This PHP does not support GD function '.$imgfonction;
        }
    }

    // We create the directory containing the thumbnails
    $dirthumb = dirname($file).($outdir ? '/'.$outdir : ''); // Path to thumbnail folder
    dol_mkdir($dirthumb);

    // Variable initialization according to image extension
    $img = null;
    $extImg = null;
    switch ($infoImg[2]) {
        case IMAGETYPE_GIF:	    // 1
            $img = imagecreatefromgif($filetoread);
            $extImg = '.gif';
            break;
        case IMAGETYPE_JPEG:    // 2
            $img = imagecreatefromjpeg($filetoread);
            $extImg = (preg_match('/\.jpeg$/', $file) ? '.jpeg' : '.jpg');
            break;
        case IMAGETYPE_PNG:	    // 3
            $img = imagecreatefrompng($filetoread);
            $extImg = '.png';
            break;
        case IMAGETYPE_BMP:	    // 6
            // Not supported by PHP GD
            $extImg = '.bmp';
            break;
        case IMAGETYPE_WBMP:	// 15
            $img = imagecreatefromwbmp($filetoread);
            $extImg = '.bmp';
            break;
        case IMAGETYPE_WEBP:	// 18
            $img = imagecreatefromwebp($filetoread);
            $extImg = '.webp';
            break;
    }

    // Before PHP8, img was a resource, With PHP8, it is a GdImage
    // if (!is_resource($img) && class_exists('GdImage') && !($img instanceof GdImage)) {
    if (is_null($img) || $img === false) {
        dol_syslog('Failed to detect type of image. We found infoImg[2]='.$infoImg[2], LOG_WARNING);
        return 0;
    }

    $exifAngle = false;
    if ($ort && getDolGlobalString('MAIN_USE_EXIF_ROTATION')) {
        switch ($ort) {
            case 3: // 180 rotate left
                $exifAngle = 180;
                break;
            case 6: // 90 rotate right
                $exifAngle = -90;
                // changing sizes
                $trueImgWidth = $infoImg[1];
                $trueImgHeight = $infoImg[0];
                break;
            case 8:    // 90 rotate left
                $exifAngle = 90;
                // changing sizes
                $trueImgWidth = $infoImg[1]; // Largeur de l'image
                $trueImgHeight = $infoImg[0]; // Hauteur de l'image
                break;
        }
    }

    if ($exifAngle) {
        $rotated = false;

        if ($infoImg[2] === IMAGETYPE_PNG) { // In fact there is no exif on PNG but just in case
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $rotated = imagerotate($img, $exifAngle, imagecolorallocatealpha($img, 0, 0, 0, 127));
            imagealphablending($rotated, false);
            imagesavealpha($rotated, true);
        } else {
            $rotated = imagerotate($img, $exifAngle, 0);
        }

        // replace image with good orientation
        if (!empty($rotated) && isset($trueImgWidth) && isset($trueImgHeight)) {
            $img = $rotated;
            $imgWidth = $trueImgWidth;
            $imgHeight = $trueImgHeight;
        }
    }

    // Initialize thumbnail dimensions if larger than original
    if ($maxWidth > $imgWidth) {
        $maxWidth = $imgWidth;
    }
    if ($maxHeight > $imgHeight) {
        $maxHeight = $imgHeight;
    }

    $whFact = $maxWidth / $maxHeight; // Width/height factor for maximum label dimensions
    $imgWhFact = $imgWidth / $imgHeight; // Original width/height factor

    // Set label dimensions
    if ($whFact < $imgWhFact) {
        // If determining width
        $thumbWidth  = $maxWidth;
        $thumbHeight = $thumbWidth / $imgWhFact;
    } else {
        // If determining height
        $thumbHeight = $maxHeight;
        $thumbWidth  = $thumbHeight * $imgWhFact;
    }
    $thumbHeight = (int) round($thumbHeight);
    $thumbWidth = (int) round($thumbWidth);

    // Define target format
    if (empty($targetformat)) {
        $targetformat = $infoImg[2];
    }

    // Create empty image
    if ($targetformat == IMAGETYPE_GIF) {
        // Compatibilite image GIF
        $imgThumb = imagecreate($thumbWidth, $thumbHeight);
    } else {
        $imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
    }

    // Activate antialiasing for better quality
    if (function_exists('imageantialias')) {
        imageantialias($imgThumb, true);
    }

    // This is to keep transparent alpha channel if exists (PHP >= 4.2)
    if (function_exists('imagesavealpha')) {
        imagesavealpha($imgThumb, true);
    }

    // Variable initialization according to image extension
    // $targetformat is 0 by default, in such case, we keep original extension
    $extImgTarget = '';  // Default = same extension as original
    $trans_colour = false;
    $newquality = null;
    switch ($targetformat) {
        case IMAGETYPE_GIF:	    // 1
            $trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // The GIF format works differently
            imagecolortransparent($imgThumb, $trans_colour);
            $extImgTarget = '.gif';
            $newquality = 'NU';
            break;
        case IMAGETYPE_JPEG:    // 2
            $trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = (preg_match('/\.jpeg$/i', $file) ? '.jpeg' : '.jpg');
            $newquality = $quality;
            break;
        case IMAGETYPE_PNG:	    // 3
            imagealphablending($imgThumb, false); // For compatibility on certain systems
            $trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127); // Keep transparent channel
            $extImgTarget = '.png';
            $newquality = round(abs($quality - 100) * 9 / 100);
            break;
        case IMAGETYPE_BMP:	    // 6
            // Not supported by PHP GD
            $extImgTarget = '.bmp';
            $newquality = 'NU';
            break;
        case IMAGETYPE_WBMP:	// 15
            $trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = '.bmp';
            $newquality = 'NU';
            break;
        case IMAGETYPE_WEBP:	// 18
            $trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = '.webp';
            $newquality = $quality;
            break;
    }
    if (function_exists("imagefill") && $trans_colour !== false) {
        imagefill($imgThumb, 0, 0, $trans_colour);
    }

    dol_syslog("vignette: convert image from ($imgWidth x $imgHeight) to ($thumbWidth x $thumbHeight) as $extImg, newquality=$newquality");
    //imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insert resized base image
    imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insert resized base image

    $fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i', '', $file); // We remove any extension box
    $fileName = basename($fileName);
    //$imgThumbName = $dirthumb.'/'.getImageFileNameForSize(basename($file), $extName, $extImgTarget);   // Full path of thumb file
    $imgThumbName = saturne_get_image_file_name_for_size($file, $extName, $extImgTarget); // Full path of thumb file


    // Check if permission are ok
    //$fp = fopen($imgThumbName, "w");
    //fclose($fp);

    // Create image on disk
    switch ($targetformat) {
        case IMAGETYPE_GIF:	    // 1
            imagegif($imgThumb, $imgThumbName);
            break;
        case IMAGETYPE_JPEG:    // 2
            imagejpeg($imgThumb, $imgThumbName, $newquality); // @phan-suppress-current-line PhanTypeMismatchArgumentNullableInternal,PhanPossiblyUndeclaredVariable
            break;
        case IMAGETYPE_PNG:	    // 3
            imagepng($imgThumb, $imgThumbName, !is_numeric($newquality) ? -1 : (int) $newquality);  // @phan-suppress-current-line PhanPossiblyUndeclaredVariable
            break;
        case IMAGETYPE_BMP:	    // 6
            // Not supported by PHP GD
            break;
        case IMAGETYPE_WBMP:    // 15
            imagewbmp($imgThumb, $imgThumbName);
            break;
        case IMAGETYPE_WEBP:    // 18
            imagewebp($imgThumb, $imgThumbName, $newquality); // @phan-suppress-current-line PhanTypeMismatchArgumentNullableInternal,PhanPossiblyUndeclaredVariable
            break;
    }

    // Set permissions on file
    dolChmod($imgThumbName);

    // Free memory. This does not delete image.
    imagedestroy($img);
    imagedestroy($imgThumb);

    return $imgThumbName;
}

/**
 *	Get title line of an array
 *
 *	@param	?string		$name			Translation key of field to show or complete HTML string to show
 *	@param	int<0,2>	$thead	 		0=To use with standard table format, 1=To use inside <thead><tr>, 2=To use with <div>
 *	@param	string		$file			Url used when we click on sort picto
 *	@param	string		$field			Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
 *	@param	string		$begin       	("" by default)
 *	@param	string		$moreparam		Add more parameters on sort url links ("" by default)
 *	@param  string		$moreattrib		Add more attributes on th ("" by default). To add more css class, use param $prefix.
 *	@param  ?string		$sortfield	 	Current field used to sort (Ex: 'd.datep,d.id')
 *	@param  ?string		$sortorder		Current sort order (Ex: 'asc,desc')
 *  @param	string		$prefix	 		Prefix for css. Use space after prefix to add your own CSS tag, for example 'mycss '.
 *  @param	int<0,1>	$disablesortlink	1=Disable sort link
 *  @param	?string		$tooltip 		Tooltip
 *  @param	int<0,1> 	$forcenowrapcolumntitle		No need to use 'wrapcolumntitle' css style
 *	@return	string
 */
function saturne_get_title_field_of_list($name, $thead = 0, $file = "", $field = "", $begin = "", $moreparam = "", $moreattrib = "", $sortfield = "", $sortorder = "", $prefix = "", $disablesortlink = 0, $tooltip = '', $forcenowrapcolumntitle = 0, $linkcustomclass = "")
{
	global $langs, $form;
	//print "$name, $file, $field, $begin, $options, $moreattrib, $sortfield, $sortorder<br>\n";

	if ($moreattrib == 'class="right"') {
		$prefix .= 'right '; // For backward compatibility
	}

	$sortorder = strtoupper((string) $sortorder);
	$out = '';
	$sortimg = '';

	$tag = 'th';
	if ($thead == 2) {
		$tag = 'div';
	}

	$tmpsortfield = explode(',', (string) $sortfield);
	$sortfield1 = trim($tmpsortfield[0]); // If $sortfield is 'd.datep,d.id', it becomes 'd.datep'
	$tmpfield = explode(',', $field);
	$field1 = trim($tmpfield[0]); // If $field is 'd.datep,d.id', it becomes 'd.datep'

	if (!getDolGlobalString('MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE') && empty($forcenowrapcolumntitle)) {
		$prefix = 'wrapcolumntitle '.$prefix;
	}

	//var_dump('field='.$field.' field1='.$field1.' sortfield='.$sortfield.' sortfield1='.$sortfield1);
	// If field is used as sort criteria we use a specific css class liste_titre_sel
	// Example if (sortfield,field)=("nom","xxx.nom") or (sortfield,field)=("nom","nom")
	$liste_titre = 'liste_titre';
	if ($field1 && ($sortfield1 == $field1 || $sortfield1 == preg_replace("/^[^\.]+\./", "", $field1))) {
		$liste_titre = 'liste_titre_sel';
	}

	$tagstart = '<'.$tag.' class="'.$prefix.$liste_titre.'" '.$moreattrib;
	//$out .= (($field && empty($conf->global->MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE) && preg_match('/^[a-zA-Z_0-9\s\.\-:&;]*$/', $name)) ? ' title="'.dol_escape_htmltag($langs->trans($name)).'"' : '');
	$tagstart .= ($name && !getDolGlobalString('MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE') && empty($forcenowrapcolumntitle) && !dol_textishtml($name)) ? ' title="'.dolPrintHTMLForAttribute($langs->trans($name)).'"' : '';
	$tagstart .= '>';

	if (empty($thead) && $field && empty($disablesortlink)) {    // If this is a sort field
		$options = preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', (is_scalar($moreparam) ? $moreparam : ''));
		$options = preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options = preg_replace('/&+/i', '&', $options);
		if (!preg_match('/^&/', $options)) {
			$options = '&'.$options;
		}

		$sortordertouseinlink = '';
		if ($field1 != $sortfield1) { // We are on another field than current sorted field
			if (preg_match('/^DESC/i', $sortorder)) {
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			} else { // We reverse the var $sortordertouseinlink
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		} else { // We are on field that is the first current sorting criteria
			if (preg_match('/^ASC/i', $sortorder)) {	// We reverse the var $sortordertouseinlink
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			} else {
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		}
		$sortordertouseinlink = preg_replace('/,$/', '', $sortordertouseinlink);
		$out .= '<a class="reposition ' . $linkcustomclass . '" href="'.$file.'?sortfield='.urlencode($field).'&sortorder='.urlencode($sortordertouseinlink).'&begin='.urlencode($begin).$options.'"';
		//$out .= (getDolGlobalString('MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE') ? '' : ' title="'.dol_escape_htmltag($langs->trans($name)).'"');
		$out .= '>';
	}
	if ($tooltip) {
		// You can also use 'TranslationString:keyfortooltiponclick:tooltipdirection' for a tooltip on click or to change tooltip position.
		if (strpos($tooltip, ':') !== false) {
			$tmptooltip = explode(':', $tooltip);
		} else {
			$tmptooltip = array($tooltip);
		}
		$out .= $form->textwithpicto($langs->trans((string) $name), $langs->trans($tmptooltip[0]), (empty($tmptooltip[2]) ? '1' : $tmptooltip[2]), 'help', '', 0, 3, (empty($tmptooltip[1]) ? '' : 'extra_'.str_replace('.', '_', $field).'_'.$tmptooltip[1]));
	} else {
		$out .= $langs->trans((string) $name);
	}

	if (empty($thead) && $field && empty($disablesortlink)) {    // If this is a sort field
		$out .= '</a>';
	}

	if (empty($thead) && $field) {    // If this is a sort field
		$options = preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', (is_scalar($moreparam) ? $moreparam : ''));
		$options = preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options = preg_replace('/&+/i', '&', $options);
		if (!preg_match('/^&/', $options)) {
			$options = '&'.$options;
		}

		if (!$sortorder || ($field1 != $sortfield1)) {
			// Nothing
		} else {
			if (preg_match('/^DESC/', $sortorder)) {
				$sortimg .= '<span class="nowrap">'.img_up("Z-A", 0, 'paddingright').'</span>';
			}
			if (preg_match('/^ASC/', $sortorder)) {
				$sortimg .= '<span class="nowrap">'.img_down("A-Z", 0, 'paddingright').'</span>';
			}
		}
	}

	$tagend = '</'.$tag.'>';

    $resizeHandler = '<div class="resize-handle"></div>';

	$out = $tagstart.$sortimg.$out.$tagend.$resizeHandler;

	return $out;
}

