<?php

/**
 *      Load array of pages to display
 *
 *      @param      integer				$pagesCounter
 *      @param      array				$page_array
 *      @param      integer				$offset
 *      @return     array				Pages number array
 *
 */
function saturne_load_pagination($pagesCounter, $page_array, $offset) {

	if (!is_array($page_array) || empty($page_array)) {
		$offset = $offset ?: 1;
		$page_array = [];
		$page_array[] = '<i class="fas fa-arrow-left"></i>';

		if ($pagesCounter > 4) {
			if ($offset > 2) {
				$page_array[] = '...';
			}

			if ($offset == 1) {
				$page_array[] = $offset;
				$page_array[] = $offset + 1;
				$page_array[] = $offset + 2;
				$page_array[] = $offset + 3;
			} else if ($offset > 1 && $offset < $pagesCounter) {
				if ($offset == $pagesCounter - 1) {
					$page_array[] = $offset - 2;
				}
				$page_array[] = $offset - 1;
				$page_array[] = $offset;
				$page_array[] = $offset + 1;
				if ($offset == 2) {
					$page_array[] = $offset + 2;
				}
			}  else if ($offset == $pagesCounter) {
				$page_array[] = $offset - 3;
				$page_array[] = $offset - 2;
				$page_array[] = $offset - 1;
				$page_array[] = $offset;
			}

			if ($pagesCounter > 3 && $offset < $pagesCounter - 1) {
				$page_array[] = '...';
			}
		} else {
			for ($i = 1; $i <= $pagesCounter; $i++) {
				$page_array[] = $i;
			}
		}

		$page_array[] = '<i class="fas fa-arrow-right"></i>';
	}

	return $page_array;
}

/**
 *      Show pages based on loaded pages array
 *
 *      @param      integer				$pagesCounter
 *      @param      array				$page_array
 *      @param      integer				$offset
 *      @return     string				Pages html content
 *
 */
function saturne_show_pagination($pagesCounter, $page_array, $offset) {
	$offset = $offset ?: 1;
	$return = '<ul class="wpeo-pagination">';
	$return .= '<input hidden id="pagesCounter" value="'. ($pagesCounter) .'">';
	$return .= '<input hidden id="containerToRefresh" value="media_gallery">';
	$return .= '<input hidden id="currentOffset" value="'. ($offset ?: 1) .'">';

	foreach ($page_array as $pageNumber) {

		$return .= '<li class="pagination-element ' . ($pageNumber == $offset ? 'pagination-current' : ($pageNumber == 1 && !$offset ? 'pagination-current' : '')) . '">';

		if ($pageNumber == '...') {
			$return .= '<span>'. $pageNumber .'</span>';
		} else if ($pageNumber == '<i class="fas fa-arrow-left"></i>') {
			$return .= '<a class="select-page arrow arrow-left" value="' . max(($offset - 1), 1) . '"><i class="fas fa-arrow-left"></i></a>';
		} else if ($pageNumber == '<i class="fas fa-arrow-right"></i>') {
			$return .= '<a class="select-page arrow arrow-right" value="' . min(($offset + 1), $pagesCounter) . '"><i class="fas fa-arrow-right"></i></a>';
		} else {
			$return .= '<a class="select-page" value="' . $pageNumber . '">' . $pageNumber . '</a>';
		}

		$return .= '</li>';
	}

	$return .= '</ul>';
	return $return;
}
