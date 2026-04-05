if (typeof window.saturne === 'undefined') window.saturne = {};

window.saturne.ERROR_DICTIONARY = {
    // ---- Catégorie : API & Réseau (Série 1000) ----
    'Saturne-1001': {
        category: 'Réseau',
        title: 'Erreur JS / Réseau',
        userMessage: 'Impossible de joindre le serveur. Vérifiez votre connexion internet.',
        file: 'media.js',
        line: 0,
        contextKey: 'network_connection_error'
    },
    'Saturne-1002': {
        category: 'Upload',
        title: 'Erreur Serveur (Configuration PHP)',
        userMessage: 'Le fichier est trop volumineux. La taille configurée sur le serveur bloque l\'envoi (ex: post_max_size / upload_max_filesize).',
        file: 'media.js',
        line: 783,
        contextKey: 'upload_size_limit_exceeded'
    },
    'Saturne-1003': {
        category: 'Sauvegarde',
        title: 'Erreur Serveur (Dolibarr HTTP)',
        userMessage: 'Erreur lors de l\'enregistrement de l\'image sur le serveur. Impossible de finaliser l\'opération.',
        file: 'media.js',
        line: 785,
        contextKey: 'ajax_save_error'
    },
    'Saturne-1004': {
        category: 'Format',
        title: 'Erreur JS (Parser JSON)',
        userMessage: 'Le format de la photo ou les données renvoyées sont invalides.',
        file: 'media.js',
        line: 777,
        contextKey: 'ajax_success_parser_error'
    }
};

window.saturne.showError = function(errorCode, extraMessage = '') {
    const errorDef = window.saturne.ERROR_DICTIONARY[errorCode];
    const message = errorDef ? errorDef.userMessage : "Une erreur inconnue est survenue (" + errorCode + ").";
    const fullMessage = extraMessage ? message + " " + extraMessage : message;
    
    const errorTitle = errorDef && errorDef.title ? errorDef.title : 'Erreur Technique';
    
    // Create or show an error toast/banner
    let banner = $('#saturne-error-banner');
    if (banner.length === 0) {
        banner = $('<div id="saturne-error-banner" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background-color: #e74c3c; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 15px; max-width: 500px; transform: translateY(150px); opacity: 0; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);"><i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #ffcccc;"></i><div style="flex-grow: 1;"><div style="font-weight: bold; font-size: 14px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;"></div><div class="saturne-error-msg" style="font-size: 13px; line-height: 1.4;"></div></div><div style="display: flex; gap: 12px; align-self: flex-start; margin-top: 2px;"><i class="far fa-copy saturne-error-copy" style="cursor: pointer; opacity: 0.8; font-size: 16px; margin-top: 1px;" title="Copier le message d\'erreur"></i><i class="fas fa-times saturne-error-close" style="cursor: pointer; opacity: 0.8; font-size: 18px;" title="Fermer"></i></div></div>');
        $('body').append(banner);
        
        $(document).on('click', '.saturne-error-close', function() {
            $('#saturne-error-banner').css({'transform': 'translateY(150px)', 'opacity': '0'});
        });
        
        $(document).on('click', '.saturne-error-copy', function() {
            const errTitle = $('#saturne-error-banner').find('div > div:first-child').text();
            const errMsg = $('#saturne-error-banner').find('.saturne-error-msg').text();
            const fullText = errTitle + '\\n' + errMsg;
            
            const icon = $(this);
            const copySuccess = () => {
                icon.removeClass('fa-copy').addClass('fa-check').css('color', '#ffcccc');
                setTimeout(() => icon.removeClass('fa-check').addClass('fa-copy').css('color', ''), 2000);
            };
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(fullText).then(copySuccess);
            } else {
                const textarea = document.createElement("textarea");
                textarea.value = fullText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                copySuccess();
            }
        });
    }
    
    banner.find('.saturne-error-msg').text(fullMessage);
    banner.find('div > div:first-child').text(errorTitle + ' - ' + (errorDef ? errorDef.category : 'Erreur') + ' (' + errorCode + ')');
    
    // Trigger animation
    setTimeout(() => {
        banner.css({'transform': 'translateY(0)', 'opacity': '1'});
    }, 10);
    
    // Auto-hide after 8 seconds
    setTimeout(() => {
        if (banner.css('opacity') === '1') {
            banner.css({'transform': 'translateY(150px)', 'opacity': '0'});
        }
    }, 8000);
};
