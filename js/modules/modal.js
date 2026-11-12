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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/modules/modal.js
 * \ingroup saturne
 * \brief   JavaScript file modal for module Saturne.
 */

/**
 * Initialise l'objet "modal" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.modal = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.modal.init = function() {
	window.saturne.modal.event();
	window.saturne.modal.watchActiveModals();
};

/**
 * Passe le menu haut Dolibarr sous l'overlay tant qu'une modal est ouverte, par sa classe
 * "saturne-under-modal" (modules/modal/_modal.scss).
 *
 * Un sélecteur body:has(.wpeo-modal.modal-active) le faisait en CSS, mais Chromium invalide alors, à
 * chaque ouverture ou fermeture, tous les éléments visés par les règles :has() de la page, dont
 * chaque icône d'une longue liste : 40 ms de recalcul de style au lieu de 8 sur la liste des risques
 * Digirisk. La classe va sur le menu et non sur le body, dont le moindre changement de classe
 * recalcule toute la page (180 ms sur la même liste). Les modules ouvrent aussi leurs modals en posant
 * directement la classe modal-active : c'est elle qu'on surveille, pas openModal et closeModal.
 *
 * La même surveillance charge les images qu'une page diffère jusqu'à l'ouverture de leur modal
 * (loadDeferredImages) : à l'ouverture, et dès qu'une telle image arrive hors de toute modal ou dans
 * une modal déjà ouverte, quel que soit le code qui l'insère (liste rafraîchie en AJAX, bloc média
 * renvoyé après un envoi).
 *
 * @since   23.6.0
 * @version 23.8.0
 *
 * @return {void}
 */
window.saturne.modal.watchActiveModals = function() {
	const sync = function() {
		const open = document.querySelector('.wpeo-modal.modal-active') !== null;
		document.querySelectorAll('.side-nav-vert').forEach(nav => nav.classList.toggle('saturne-under-modal', open));
	};

	const loadReachable = function(root) {
		const images = root.matches('img[data-deferred-src]') ? [root] : root.querySelectorAll('img[data-deferred-src]');
		images.forEach(function(image) {
			const modal = image.closest('.wpeo-modal');
			if (!modal || modal.classList.contains('modal-active')) {
				window.saturne.modal.loadDeferredImages(image);
			}
		});
	};

	new MutationObserver(function(records) {
		const modals = records.filter(record => record.type === 'attributes' && record.target.classList.contains('wpeo-modal')).map(record => record.target);
		if (modals.length) {
			sync();
			modals.filter(modal => modal.classList.contains('modal-active')).forEach(window.saturne.modal.loadDeferredImages);
		}
		records.filter(record => record.type === 'childList').forEach(function(record) {
			record.addedNodes.forEach(function(node) {
				if (node.nodeType === Node.ELEMENT_NODE) {
					loadReachable(node);
				}
			});
		});
	}).observe(document.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['class']});

	sync();
	loadReachable(document.body);
};

/**
 * Charge les images différées d'un conteneur.
 *
 * Une modal fermée reste dans la mise en page sous une opacité nulle : le navigateur tient donc ses
 * images pour visibles et loading="lazy" ne les retient pas. Une page qui en affiche beaucoup (une par
 * risque sur l'onglet Risques de Digirisk) les écrit avec data-deferred-src au lieu de src (option
 * defer_images de saturne_show_medias_linked() et de saturne_render_media_block()), et elles ne
 * partent qu'ici, appelée par watchActiveModals.
 *
 * @since   23.8.0
 * @version 23.8.0
 *
 * @param  {Element|jQuery} container Conteneur dont les images différées sont à charger, ou l'image elle-même
 * @return {void}
 */
window.saturne.modal.loadDeferredImages = function(container) {
	$(container).find('img[data-deferred-src]').addBack('img[data-deferred-src]').each(function() {
		this.setAttribute('src', this.getAttribute('data-deferred-src'));
		this.removeAttribute('data-deferred-src');
	});
};

/**
 * La méthode contenant tous les événements pour la modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.modal.event = function() {
	$( document ).on( 'click', '.modal-close, .modal-active:not(.modal-container):not(.modal-close-only-with-button)', window.saturne.modal.closeModal );
	$( document ).on( 'click', '.modal-open', window.saturne.modal.openModal );
	$( document ).on( 'click', '.modal-refresh', window.saturne.modal.refreshModal );
};

/**
 * Open Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.saturne.modal.openModal = function () {
    let modalOptions = $(this).find('.modal-options');
	let modalToOpen  = modalOptions.attr('data-modal-to-open');

	let fromId      = modalOptions.attr('data-from-id');
	let fromType    = modalOptions.attr('data-from-type');
	let fromSubtype = modalOptions.attr('data-from-subtype');
	let fromSubdir  = modalOptions.attr('data-from-subdir');
  let fromModule  = modalOptions.attr('data-from-module');
  let photoClass  = modalOptions.attr('data-photo-class');
  let fromField   = modalOptions.attr('data-from-field');

	let urlWithoutTag = '';
	if (document.URL.match(/#/)) {
		urlWithoutTag = document.URL.split(/#/)[0];
	} else {
		urlWithoutTag = document.URL;
	}
	history.pushState({ path:  document.URL}, '', urlWithoutTag);

	// Open modal media gallery.
	$('#'+modalToOpen).attr('data-from-id', fromId);
	$('#'+modalToOpen).attr('data-from-type', fromType);
	$('#'+modalToOpen).attr('data-from-subtype', fromSubtype);
  $('#'+modalToOpen).attr('data-from-subdir', fromSubdir);
  $('#'+modalToOpen).attr('data-photo-class', photoClass);
  $('#'+modalToOpen).attr('data-from-field', fromField);
  // A media block opens the gallery on its own: a regular opener must not inherit its target
  $('#'+modalToOpen).removeAttr('data-from-block');
  if (window.saturne.mediaPending) {
    window.saturne.mediaPending.syncCurrentTarget($('#'+modalToOpen));
  }

  window.saturne.modal.loadLazyImages();

  if (fromModule) {
    if (typeof window.saturne.modal.addMoreOpenModalData === 'function') {
        window.saturne.modal.addMoreOpenModalData(modalToOpen, $(this));
    }
  }

	$('#'+modalToOpen).find('.wpeo-button').attr('value', fromId);
	$('#'+modalToOpen).addClass('modal-active');

  if (modalToOpen === 'media_gallery' && window.saturne.mediaGallery) {
    window.saturne.mediaGallery.reloadIfChanged();
  }

	$('.notice').addClass('hidden');
};

/**
 * Close Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.saturne.modal.closeModal = function ( event ) {
	if ($('input:focus').length < 1 && ($('textarea:focus').length < 1)) {
		if ($(event.target).hasClass('modal-active') || $(event.target).hasClass('modal-close') || $(event.target).parent().hasClass('modal-close')) {
			$(this).closest('.modal-active').removeClass('modal-active');
			$('.clicked-photo').attr('style', '');
			$('.clicked-photo').removeClass('clicked-photo');
			$('.notice').addClass('hidden');
		}
	}
};

/**
 * Refresh Modal.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.saturne.modal.refreshModal = function () {
	window.location.reload();
};

/**
 * Lazy loads images inside the modal
 *
 * Loops through all `.photo` elements with a `data-src` attribute,
 * sets the `src` attribute to trigger image loading,
 * and marks them as loaded by adding `data-loaded="true"`
 *
 * This function should be called:
 * - When the modal is opened
 * - After dynamically adding new images with `data-src` to the modal or other functions
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.saturne.modal.loadLazyImages = function () {
  $('.photo[data-src]').each(function () {
    const $img = $(this);
    if (!$img.attr('data-loaded')) {
      $img.attr('src', $img.data('src'));
      $img.attr('data-loaded', 'true');
    }
  });
};
