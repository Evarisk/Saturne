# [Saturne] [23.3.0] - La bibliothèque de médias - Signature électronique refondue

Description : Toutes vos photos au même endroit, et chacune à sa place. Cette version dote le socle d'une véritable bibliothèque de médias — consultation, affectation, retouche — et refond le bloc de signature électronique de la fiche utilisateur.

## 🎬 La nouveauté en vidéo

[![La bibliothèque de médias en vidéo (37 s)](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.3.0/video_poster_23.3.0.jpeg)](https://github.com/Evarisk/Saturne/releases/download/23.3.0/saturne-23.3.0.mp4)

*Cliquez sur l'image pour lancer la vidéo (37 s).*

## 🚀 Pack nouveautés

### La bibliothèque de médias

![La bibliothèque de médias](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.3.0/medias_library_23.3.0.png)

**Les photos déposées dans vos modules se consultent enfin depuis une page unique.** Jusqu'ici, un média vivait dans l'objet auquel il était rattaché et nulle part ailleurs : pour savoir ce qui avait été déposé, il fallait ouvrir les fiches une par une.

* **Tout est là, et lisible** : nom, date, poids, et les affectations de chaque photo — groupements, unités de travail — directement en face d'elle.
* **Vue liste ou vue galerie**, au choix, avec un aperçu qui s'affiche sans quitter la page.
* **La galerie a été repensée** dans l'esprit des galeries modernes : sélection multiple par coche, barre de progression nommée pendant l'envoi, et le poids maximal autorisé rappelé en cas de refus.

### Les médias en attente

![Les médias en attente d'affectation](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.3.0/medias_pending_23.3.0.png)

* **Un onglet réunit les photos qui ne sont rattachées à rien.** Elles ne se perdent plus au fond d'un dossier : on les affecte à un groupement ou à une unité de travail depuis cette page.
* **Affectation rapide à la galerie courante**, sans passer par la fiche.
* Les médias se **partagent vers les notes de frais** depuis la galerie, avec confirmation à l'écran.

### L'éditeur photo

* **Une baguette d'analyse par IA** est proposée sur chaque photo, depuis le bloc média comme depuis l'éditeur.
* **L'éditeur reste entier et validable sur tablette**, y compris zoomée ou tenue en paysage — le bouton de validation sortait de l'écran.

### La signature électronique de la fiche utilisateur

![Le bloc de signature électronique](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.3.0/user_signature_23.3.0.png)

* **Le bloc a été repensé**, et le tracé est désormais cadré au plus près de ce qui a été dessiné.
* **La signature est partagée entre les entités** : signée une fois, elle suit l'utilisateur partout — et **lui seul** peut la modifier.
* La date de signature s'affiche **dans le fuseau horaire de celui qui la lit**.

## Améliorations & corrections

### Filtres

* Le calendrier des filtres de date passe **au-dessus** du panneau de filtres, et la navigation de mois en mois fonctionne.

### Médias

* Plus de rotation sans fin au changement d'affichage, et les appels asynchrones de la bibliothèque sont sécurisés.
* L'éditeur ne s'initialise plus quand aucune fenêtre modale n'est présente dans la page.

### Tickets

* Les **tags du ticket** sont exposés aux modèles de courriel.

### Transfert d'entité

* À l'import, les chemins de l'entité d'origine pointent vers l'installation d'arrivée.

### Divers

* Le registre des objets résout ses chemins de classe et de bibliothèque par `dol_buildpath()`.
* Les conditions d'affichage des menus échappent correctement le nom du module.
* Plus d'avertissement PHP 8 à la génération ODT quand le nom du modèle est masqué.
* Le changelog reprend le nom attendu par Dolibarr, `ChangeLog.md`.

## Testez les nouveautés maintenant

**Allez tester la bibliothèque de médias sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.1](https://github.com/Evarisk/Saturne/compare/23.2.1...23.3.0) et 23.3.0

* #1710 [Medias] add: baguette d'analyse IA des photos dans le bloc média et l'éditeur photo
* #1707 [Filter] fix: calendrier des filtres de date au-dessus du popover et navigation par mois
* #1701 [Medias] fix: loader infini lors du basculement d'affichage et sécurisation des requêtes AJAX
* #1698 [Signature] fix: date de signature de la fiche utilisateur au fuseau de l'utilisateur
* #1696 [JS] feat: cadrer la signature de la fiche utilisateur sur le tracé
* #1694 [Signature] style: refonte du bloc de signature électronique de la fiche utilisateur
* #1692 [Signature] fix: signature partagée entre les entités et réservée à son propriétaire
* #1690 [Menu] fix: échapper le nom du module dans les conditions de menu
* #1682 [Object] fix: résoudre class_path et lib_path du registre par dol_buildpath()
* #1680 [Media] fix: ne pas initialiser l'éditeur sans modale dans la page
* #1678 [EntityTransfer] fix: chemins de l'entité source pointés vers l'installation cible
* #1676 [Medias] add: page liste de la bibliothèque, médias en attente et affectation
* #1673 [Mod] fix: renommer le changelog en ChangeLog.md
* #1671 [Ticket] feat: exposer les tags du ticket aux modèles d'email
* #1669 [Medias] rework: refonte de l'ergonomie de la galerie
* #2442 [Media] fix: éditeur photo entier et validable sur tablette zoomée ou en paysage
* #5348 [Medias] feat: bouton d'affectation rapide à la galerie courante et partage vers les notes de frais
