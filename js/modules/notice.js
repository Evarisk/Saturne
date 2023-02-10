
/**
 * Initialise l'objet "notice" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.4.0
 * @version 1.4.0
 */
window.saturne.notice = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.notice.init = function() {
	window.saturne.notice.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.notice.event = function() {
	$(document).on('click', '.notice-close', window.saturne.notice.closeNotice);
};

/**
 * Clique sur une des user de la liste.
 *
 * @since   1.4.0
 * @version 1.4.0
 *
 * @return {void}
 */
window.saturne.notice.closeNotice = function() {
	$(this).closest('.notice').fadeOut(function () {
		$(this).closest('.notice').addClass("hidden");
	});

	if ($(this).hasClass('notice-close-forever')) {
		let token = $(this).closest('.notice').find('input[name="token"]').val();
		let querySeparator = '?';

		document.URL.match(/\?/) ? querySeparator = '&' : 1;

		$.ajax({
			url: document.URL + querySeparator + 'action=closenotice&token='+token,
			type: "POST",
		});
	}
};
