# [Saturne] [23.4.0] - La bibliothèque se remplit toute seule

Description : La bibliothèque de médias arrivée en 23.3.0 ne connaissait que les photos déposées depuis elle. Elle récupère désormais celles qui dorment déjà dans vos enregistrements — en un bouton, et sans doublon.

## 🚀 Pack nouveautés

### Récupérer toutes les photos

![Le bouton « Récupérer toutes les photos » dans la galerie](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.4.0/gallery_sync_23.4.0.png)

**Les photos déjà déposées dans vos fiches entrent dans la bibliothèque d'un seul clic.** Jusqu'ici, une installation en service depuis des années gardait sa bibliothèque vide : seules les photos ajoutées *après* la mise à jour y figuraient. Le bouton **« Récupérer toutes les photos »** fait le rattrapage.

* **Le compte rendu est détaillé** : photos ajoutées, homonymes retrouvées sous un autre nom, déjà présentes, et écartées.
* **Rien d'indésirable n'entre** : signatures, documents confidentiels et dossiers de travail sont écartés. Seuls les dossiers déclarés par le module sont parcourus.
* **Le rattrapage se fait par lots**, ce qui lui permet de traiter une grosse médiathèque sans buter sur le temps d'exécution.
* **Les homonymes sont reconnus** : une même photo déjà présente sous un autre nom n'est pas dupliquée.

### Ajouter depuis l'onglet « En attente »

![L'onglet des médias en attente](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.4.0/medias_pending_23.4.0.png)

* Le **bouton d'ajout** est désormais disponible depuis l'onglet « En attente », et pas seulement depuis la vue complète.
* Le **bouton de bibliothèque du bloc média** ouvre la galerie directement depuis une fiche.
* Après un envoi depuis un bloc média, **la galerie se relit** : la photo apparaît sans recharger la page.

### Éditeur photo

* La **suppression demande confirmation** dans une fenêtre du module, à la place de la boîte du navigateur.

## Améliorations & corrections

### Bibliothèque

* La liste des cibles d'affectation, le hook qui la nourrit, l'aperçu et le pied de l'onglet « En attente » restent en place au défilement.

### Tableau de bord

* Plus d'avertissement PHP 8 sur un graphique dont une série n'a pas de nom.

## Testez les nouveautés maintenant

**Allez tester la bibliothèque de médias sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.3.0](https://github.com/Evarisk/Saturne/compare/23.3.0...23.4.0) et 23.4.0

* #1725 [Medias] feat: bouton « Récupérer toutes les photos » et galerie relue après un envoi par un bloc
* #1725 [Medias] fix: homonymes, dossiers déclarés par le module, signatures et rattrapage par lots de la bibliothèque
* #1723 [Medias] feat: bibliothèque alimentée par les photos des enregistrements et rattrapage des photos existantes
* #1721 [Medias] fix: bouton Ajouter dans l'onglet En attente ouvert depuis un enregistrement
* #1719 [Medias] feat: confirmation de suppression dans l'éditeur photo à la place de window.confirm
* #1719 [Medias] feat: bouton bibliothèque du bloc média et ajout depuis l'onglet En attente
* #1719 [Medias] fix: liste des cibles, hook d'affectation, aperçu et pied collants de l'onglet En attente
* #1717 [Dashboard] fix: warning PHP 8 sur un graphique sans clé name
