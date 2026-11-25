# [Saturne] [23.10.1] - Page publique de signature et participants plus sûrs - Modèles de documents retrouvés

Description : la page publique de signature contrôle l'entité de son lien, l'envoi d'un lien de signature à un participant ne touche plus à l'utilisateur connecté, et la page Documents de l'administration retrouve les modèles qu'un module range avec ceux d'un autre document. **Mise à jour recommandée pour toutes les instances.**

## Améliorations & corrections

### Page publique de signature : contrôles renforcés

* L'entité du lien de signature est contrôlée avant toute lecture de la configuration.

### Participants

* Envoyer le lien de signature à un participant ne modifie plus l'utilisateur connecté pour la suite de la page.
* Le mail de demande de signature envoyé à un participant utilisateur est rattaché à son destinataire dans l'agenda, et le tableau des participants compte maintenant ses mails.

### Administration > Documents

* Les modèles qu'un module range avec ceux d'un autre document s'affichent de nouveau, avec leurs interrupteurs : feuille de présence et certificat de réalisation de DoliMeet, et dans Digirisk les listings des risques avec actions correctives, avec photos, et environnemental avec actions.

### Listes

* La fenêtre du filtre de colonne reste ouverte quand une sélection de texte est relâchée en dehors.

## Testez les nouveautés maintenant

**Allez tester la signature d'une session de formation sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.10.0](https://github.com/Evarisk/Saturne/compare/23.10.0...23.10.1) et 23.10.1

* [Signature] fix: la page publique de signature lit l'entité du lien comme un entier [`14153f1b`](https://github.com/Evarisk/Saturne/commit/14153f1b)
* [Attendants] fix: l'envoi d'un mail à un participant charge le participant dans un objet User dédié [`35061f4d`](https://github.com/Evarisk/Saturne/commit/35061f4d)
* [#1985] [List] fix: la fenêtre du filtre de colonne reste ouverte quand une sélection de texte est relâchée dehors [`62215d39`](https://github.com/Evarisk/Saturne/commit/62215d39)
* [#1979] [Admin] fix: la page Documents liste les modèles qu'un module range avec ceux d'un autre document [`c0e46412`](https://github.com/Evarisk/Saturne/commit/c0e46412)
* [#1978] [Signature] fix: le mail de demande de signature à un utilisateur est rattaché à son destinataire [`c98d4471`](https://github.com/Evarisk/Saturne/commit/c98d4471)
