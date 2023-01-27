/**
 * Initialise l'objet "keyEvent" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.keyEvent = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.init = function() {
	window.saturne.keyEvent.event();
};

/**
 * La méthode contenant tous les événements pour le migration.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.event = function() {
	$( document ).on( 'keydown', window.saturne.keyEvent.modalActions );
	$( document ).on( 'keyup', '.url-container' , window.saturne.keyEvent.checkUrlFormat );
}

/**
 * Action modal close & validation with key events
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.modalActions = function( event ) {
	if ( 'Escape' === event.key  ) {
		$(this).find('.modal-active .modal-close .fas.fa-times').first().click();
	}

	if ( 'Enter' === event.key )  {
		event.preventDefault()
		if (!$('input, textarea').is(':focus')) {
			$(this).find('.modal-active .modal-footer .wpeo-button').not('.button-disable').first().click();
		} else {
			$('textarea:focus').val($('textarea:focus').val() + '\n')
		}
	}
};

/**
 * Check url format of url containers
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.keyEvent.checkUrlFormat = function( event ) {
	var urlRegex = /[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?/gi;
	if ($(this).val().match(urlRegex)) {
		$(this).attr('style', 'border: solid; border-color: green')
	} else if ($('input:focus').val().length > 0) {
		$(this).attr('style', 'border: solid; border-color: red')
	}
};
