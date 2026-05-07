# [Saturne] [23.0.0] - Médiathèque enrichie - Filtres avancés sur les listes

Description : Cette version refond complètement la médiathèque (éditeur photo réutilisable, enregistrement et bibliothèque audio), introduit un système de filtres avancés sur la vue liste générique et ajoute la compatibilité avec Dolibarr 22.

## Nouvelles fonctionnalités et innovations

### Médiathèque

* Nouveau bloc média réutilisable (`saturne_render_media_block`) avec un onglet d'administration dédié et des traductions complètes — chaque module peut désormais afficher la médiathèque via un seul appel.
* L'éditeur photo basé sur canvas est remplacé par le composant `photo-editor-modal` issu de ReedCRM : recadrage, dessin, ajout de texte et flou désormais disponibles partout.
* Navigation précédent / suivant dans l'éditeur photo : on peut parcourir toute une galerie sans la quitter, avec un badge d'index et un remplacement automatique de la photo en cours d'édition.
* Bibliothèque audio complète : enregistrement, lecture, modale de sélection et suppression — gérée intégralement via attributs `data-*` (zéro JS inline).
* Upload des photos auto-déclenché en AJAX via `mediaBlock.js`, avec validation MIME côté serveur et messages d'erreur via jnotify.

<!-- 📸 Ajouter une screenshot ici -->

### Filtres avancés sur les listes

* Nouveau système de filtres sur la vue liste générique : ajout, retrait (croix dédiée), recherche globale (`searchall`) et recherche sur un champ unique fonctionnent désormais de manière cohérente.
* Les paramètres de recherche non visibles sont préservés lors du tri/pagination grâce à l'utilisation d'un `contextpage` unique.
* Hook ajouté sur la liste pour permettre aux modules enfants d'injecter leurs propres filtres.
* Badges de filtres alignés sur le rendu Dolibarr standard (couleurs, spans des catégories).

<!-- 📸 Ajouter une screenshot ici -->

### Compatibilité Dolibarr 22

* `SaturneObject` : remplacement de `dolBuildUrl` par `http_build_query` pour rester compatible avec Dolibarr 22 (la fonction `dolBuildUrl` ayant été retirée).

---

## Améliorations & corrections

### Médiathèque

* MIME type des images vérifié à l'upload, avec un message d'erreur explicite via jnotify.
* La modale audio se ferme correctement au clic extérieur et se rafraîchit après chaque enregistrement.
* Suppression d'audio depuis la modale via attributs `data-*` (plus d'inline `onclick`).
* Vignette de galerie correctement encapsulée dans `.saturne-media-gallery` pour que le rafraîchissement post-upload fonctionne.
* Bouton OK distinct du bouton Save : sauvegarder une photo dans l'éditeur ne ferme plus involontairement la modale.
* Bouton « OK » avec coche verte, barre d'outils sur une seule ligne, navigation déplacée à l'intérieur du canvas — refonte complète du visuel de l'éditeur photo.
* Tous les styles inline (PHP et JS) ont été migrés vers SCSS pour faciliter la personnalisation par module.
* Conflit de loader corrigé : utilisation de `butAction` au lieu de `wpeo-button` sur les boutons d'upload.
* Garde `!empty()` ajoutée sur les préférences `$user->conf` de la galerie pour éviter les notices PHP.
* Helpers de jeton d'upload et fonction `get_media_files` documentés (PHPDoc utilisateur).

### Vue liste

* Champ calendrier à nouveau visible dans les filtres.
* Badge correctement affiché lorsqu'un seul `searchall` est utilisé.
* Recherche `searchall` sur un seul champ fonctionnelle.
* Recherche inversée sur les champs corrigée.
* Inclusion JS/CSS multiples supprimée — `maxwidthsearch` correctement appliqué.
* CSS et JS inline retirés des templates.
* Champs et traductions de filtres correctement remontés.

### Tableau de bord

* Affichage de 4 graphiques par ligne (au lieu d'une grille incohérente).
* Filtre multi-critères sur les graphiques corrigé (résultats vides quand plusieurs filtres étaient combinés).

### Schéma SQL

* Ajout des tables d'extrafields manquantes pour `saturne_object_documents`, signature et schedules.
* Fichiers SQL d'extrafields obsolètes retirés ; `isextrafieldmanaged` passé à `0` quand inutile — schéma simplifié.

### Signature

* Détails de l'email (sujet, destinataires) journalisés dans `actioncomm` à l'envoi pour faciliter le suivi.

### Pipeline CI/CD

* Workflow `release.yml` finalisé avec gestion correcte de `.gitattributes` (export-ignore) et compilation automatique des assets minifiés sur push.
* Workflow Phan / PHPStan stabilisé après plusieurs itérations.
* `cross-env` utilisé pour les builds Gulp (compatibilité Windows / Linux).
* Suppression du CSS non minifié du dépôt.
* Paramètre `module` correctement passé pour permettre la compilation d'autres modules que Saturne.

## Comparaison des versions [22.1.0](https://github.com/Evarisk/Saturne/compare/22.1.0...23.0.0) et 23.0.0

* [#1346] [MediaLib] fix: guard $user->conf media gallery prefs with !empty [`#1347`](https://github.com/Evarisk/Saturne/pull/1347)
* [#1342] [Signature] fix: log email details in actioncomm on send_email [`#1343`](https://github.com/Evarisk/Saturne/pull/1343)
* [#1337] [MediaLib] feat: photo editor modal, audio library, media block [`#1338`](https://github.com/Evarisk/Saturne/pull/1338)
* [#1339] [SQL] fix: missing extrafields tables and cleanup [`#1340`](https://github.com/Evarisk/Saturne/pull/1340) [`#1341`](https://github.com/Evarisk/Saturne/pull/1341)
* [SaturneObject] fix: replace dolBuildUrl with http_build_query for Dolibarr 22 compat [`4b17d693`](https://github.com/Evarisk/Saturne/commit/4b17d693)
* [List] fix: use unique contextpage and preserve non-visible search params [`7c9a2269`](https://github.com/Evarisk/Saturne/commit/7c9a2269)
* [#1319] [List] add: new filter on saturne list [`#1320`](https://github.com/Evarisk/Saturne/pull/1320)
* [#1321] [Graph] fix: bugged filter when multi filter [`#1322`](https://github.com/Evarisk/Saturne/pull/1322)
* [#1323] [CSS] fix: warning scss [`0ed974c4`](https://github.com/Evarisk/Saturne/commit/0ed974c4)
* [#127] [CI] add: release workflow and gitattributes [`#1314`](https://github.com/Evarisk/Saturne/pull/1314)
