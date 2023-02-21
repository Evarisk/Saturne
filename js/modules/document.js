/**
 * Initialise l'objet "document" ainsi que la méthode "init" obligatoire pour la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.saturne.document = {};

/**
 * La méthode appelée automatiquement par la bibliothèque Saturne.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.init = function() {
	window.saturne.document.event();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.event = function() {
	$( document ).on( 'click', '#builddoc_generatebutton', window.saturne.document.displayLoader );
	$( document ).on( 'click', '.pdf-generation', window.saturne.document.displayLoader );
};

/**
 * Display loader on generation document.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.saturne.document.displayLoader = function(  ) {
	window.saturne.loader.display($(this).closest('.div-table-responsive-no-min'));
};
