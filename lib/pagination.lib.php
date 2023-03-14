<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/pagination.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions for Saturne Pagination
 */

/**
 *      Load array of pages to display
 *
 *      @param      integer				$pagesCounter	Number of pages
 *      @param      array				$page_array		Array with all available pages
 *      @param      integer				$offset			Selected page
 *      @return     array				$page_array		Pages number array
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
 *      @param      integer				$pagesCounter	Number of pages
 *      @param      array				$page_array		Array with all available pages
 *      @param      integer				$offset			Selected page
 *      @return     string				$return 		Pages html content	Pages number array
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
