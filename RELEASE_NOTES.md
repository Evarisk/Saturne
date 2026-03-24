# [Saturne] [22.1.0] - Vue liste générique - Pipeline qualité CI/CD

Description : Cette version introduit une vue liste réutilisable par tous les modules, améliore la gestion des formulaires avec SaturneForm, et met en place un pipeline de qualité complet (analyse statique, tests unitaires, lint).

## Nouvelles fonctionnalités et innovations

### Vue liste générique

* Nouvelle vue liste commune à tous les modules Saturne : plus besoin de recréer une liste de zéro dans chaque module, une seule implémentation partagée gère le tri, la pagination et les hooks.
* Les champs éditables directement dans le tableau (dates, textes) sont désormais fiables : correction des décalages de valeur lors de la saisie inline.
* Les alias de colonnes personnalisées sont correctement pris en compte dans l'ordre de tri.
* L'en-tête du tableau (`<thead>`) est maintenant inclus dans le template de liste, ce qui améliore l'accessibilité et la cohérence visuelle.
* Nouvelle fonction `saturne_get_title_field_of_list` pour récupérer dynamiquement le champ titre d'une liste.

<!-- 📸 Ajouter une screenshot ici -->

### Formulaires et saisie

* **SaturneForm** : nouvelle classe PHP pour centraliser la création de boutons et de modales de confirmation dans les formulaires — les modules n'ont plus à gérer ces éléments manuellement.
* Les champs de type texte long dans les formulaires bénéficient de nouvelles classes CSS pour un rendu plus homogène.
* Les listes de modèles de mail affichent désormais les champs supplémentaires (extrafields) configurés sur l'objet, directement dans le bloc d'informations.
* Un compteur de caractères restants apparaît automatiquement sous chaque champ limité en longueur (`maxlength`).

<!-- 📸 Ajouter une screenshot ici -->

### Médias liés

* La fonction d'affichage des médias liés à un objet accepte maintenant des paramètres de filtre supplémentaires, permettant d'affiner les médias affichés selon le contexte.

### Pipeline qualité CI/CD

* Mise en place d'un pipeline GitHub Actions complet : analyse statique PHP (PHPStan niveau max + Phan), tests unitaires (PHPUnit), contrôle de style (PHPCS / JSHint) et compilation des assets (Gulp) — chaque étape bloque la fusion en cas d'erreur.
* Ajout d'un workflow de release automatisé.
* Configuration CLAUDE.md avec les conventions de développement du projet.

---

## Améliorations & corrections

### Documents

* Les fichiers marqués comme favoris n'apparaissaient plus dans la vue document Saturne — corrigé.
* Génération ODT : clé manquante pour la substitution des variables d'objet — corrigé.

### Navigation et onglets

* La fonction `saturne_object_prepare_head` gérait incorrectement les cas où `parentType` et `objectType` sont distincts — corrigé.

### Affichage et interface

* Le bloc patchnote s'affichait même lorsque la configuration le désactivait — corrigé.
* Le filtre de statut dans la liste générique ignorait les statuts à valeur `0` — corrigé.

### Médias

* La galerie de médias dans les questionnaires ciblait le mauvais sélecteur CSS — corrigé.
* La fonction `saturne_get_thumb_name` renvoyait un nom de miniature incorrect dans certains cas — refactorisée.

### Compatibilité PHP 8

* Correction de deux avertissements PHP 8 dans les classes de bibliothèque.

---

## Comparaison des versions [22.0.0](https://github.com/Evarisk/Saturne/compare/22.0.0...22.1.0) et 22.1.0

* [#1287] [List] add: generic saturne list [`#1300`](https://github.com/Evarisk/Saturne/pull/1300)
* [#1287] [List] fix: contenteditable date [`#1304`](https://github.com/Evarisk/Saturne/pull/1304)
* [#1287] [List] fix: need otheralias for custom field [`f2b9cd0d`](https://github.com/Evarisk/Saturne/commit/f2b9cd0d)
* [#1287] [List] fix: menu + hook [`dd5c4405`](https://github.com/Evarisk/Saturne/commit/dd5c4405)
* [#1292] [ListTemplate] add: thead for list template [`db34bf72`](https://github.com/Evarisk/Saturne/commit/db34bf72)
* [#1293] [Lib] add: saturne_get_title_field_of_list [`#1294`](https://github.com/Evarisk/Saturne/pull/1294)
* [#1305] [JS] add: counter for all maxlength field [`#1306`](https://github.com/Evarisk/Saturne/pull/1306)
* [#1305] [List] fix: status > 0 [`7cd22342`](https://github.com/Evarisk/Saturne/commit/7cd22342)
* [#680] [SaturneForm] add: class for manage button and formconfirm [`5588e1a3`](https://github.com/Evarisk/Saturne/commit/5588e1a3)
* [#680] [SaturneForm] add: improve SaturneForm [`#681`](https://github.com/Evarisk/Saturne/pull/681)
* [#1288] [Action] add: extrafield list inside content info [`25a60233`](https://github.com/Evarisk/Saturne/commit/25a60233) [`#1289`](https://github.com/Evarisk/Saturne/pull/1289)
* [#1290] [CSS] add: class for text field form [`#1291`](https://github.com/Evarisk/Saturne/pull/1291)
* [#1301] [ShowMediaLinked] add: more params filter [`#1302`](https://github.com/Evarisk/Saturne/pull/1302)
* [#1309] [CI] add: gulp CI build [`#1310`](https://github.com/Evarisk/Saturne/pull/1310)
* [#127] [CI] add: CI phan/phpstan/phpunit [`6bb05674`](https://github.com/Evarisk/Saturne/commit/6bb05674)
* [#127] [CI] add: release [`536d3982`](https://github.com/Evarisk/Saturne/commit/536d3982)
* [#127] [Claude] add: claude.md ruleset [`#1313`](https://github.com/Evarisk/Saturne/pull/1313) [`#1314`](https://github.com/Evarisk/Saturne/pull/1314)
* [#1307] [Action] fix: favorite not appear in saturne document [`#1308`](https://github.com/Evarisk/Saturne/pull/1308)
* [#1301] [ShowMediaLinked] fix: fatal [`#1303`](https://github.com/Evarisk/Saturne/pull/1303)
* [—] [ODT] fix: missing key for object and use get_substitutionarray_each_var_object [`236008db`](https://github.com/Evarisk/Saturne/commit/236008db)
* [—] [Lib] fix: saturne_object_prepare_head for parentType and objectType [`1b61d077`](https://github.com/Evarisk/Saturne/commit/1b61d077)
* [#1273] fix: patchnote with conf [`721dcd37`](https://github.com/Evarisk/Saturne/commit/721dcd37)
* [#1285] [Lib] fix: saturne_get_thumb_name [`#1286`](https://github.com/Evarisk/Saturne/pull/1286)
* [#1278] [Media] fix: JS need to target question__list-medias [`b80d8f2f`](https://github.com/Evarisk/Saturne/commit/b80d8f2f)
* [#1128] [Lib] fix: php 8 warning [`3c672a36`](https://github.com/Evarisk/Saturne/commit/3c672a36)
* [—] [Mod] fix: php8 fix warning [`23f651b2`](https://github.com/Evarisk/Saturne/commit/23f651b2)
* [#127] [CI] fix: quality.yml / PHPCS / JSHint / package.json [`#1314`](https://github.com/Evarisk/Saturne/pull/1314)
* [#127] [Mod] fix: need git attributes files for eol [`5f6465a4`](https://github.com/Evarisk/Saturne/commit/5f6465a4)
