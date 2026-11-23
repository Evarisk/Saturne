# [Saturne] [23.9.1] - Fichiers des objets validés retrouvés - Pages d'administration sans erreur

Description : Les fichiers joints et les documents de brouillon restés sous la référence provisoire `(PROV…)` d'un objet déjà validé reviennent sur sa fiche, y compris quand on active un module seul. Les pages d'administration ouvertes par un lien direct ne s'arrêtent plus sur une erreur.

## Améliorations & corrections

### Documents des objets validés

* Les fichiers d'un objet validé avant Saturne 23.9.0 et restés dans son dossier provisoire `(PROV…)` sont **rapatriés dans le dossier de sa référence définitive**, avec leur index, sans jamais rien écraser : pièces jointes et documents générés en brouillon réapparaissent sur la fiche.
* La reprise tourne à la mise à jour d'un module, à l'activation de Saturne **et à l'activation d'un module seul depuis Accueil > Modules**, une seule fois par module et par entité. Elle ne prend que quelques millisecondes.
* Elle retrouve aussi les objets des modules dont l'élément ne donne pas directement le nom de la classe (feuilles d'émargement de DoliLetter, listes d'appels et adresses de ReedCRM).

### Administration

* Les pages de configuration des objets, de l'application mobile et d'information, ouvertes seules (lien direct, favori), refusent proprement un module absent au lieu d'une erreur fatale ou d'un onglet vide.
* La page de configuration des objets ne laisse plus d'avertissement PHP quand on l'ouvre sans page d'origine.

### Divers

* Les événements d'agenda créés par Saturne affichent leur icône : l'image demandée n'existait pas.
* Une page qui annule son action (par exemple l'annulation d'un formulaire sur la fiche d'une enveloppe DoliLetter) ne fait plus tomber les modules Saturne en erreur fatale.

## Comparaison des versions [23.9.0](https://github.com/Evarisk/Saturne/compare/23.9.0...23.9.1) et 23.9.1

* [#1943] [Object] fix: reprise des dossiers (PROVn) des objets dont l'élément ne donne pas le nom de la classe [`aa04e5c5`](https://github.com/Evarisk/Saturne/commit/aa04e5c5)
* [#1940] [Trigger] fix: picto saturne_color@saturne, l'image saturne@saturne n'existe pas [`3ad661a8`](https://github.com/Evarisk/Saturne/commit/3ad661a8)
* [#1939] [Object] fix: reprise des dossiers (PROVn) appelable depuis init() d'un module activé depuis Accueil > Modules [`29438898`](https://github.com/Evarisk/Saturne/commit/29438898)
* [#1937] [Hook] fix: doActions accepte $action nul [`48dfd2ec`](https://github.com/Evarisk/Saturne/commit/48dfd2ec)
* [#1935] [Admin] fix: $langs déclaré avant le contrôle des paramètres (phpstan) [`5a7f0f32`](https://github.com/Evarisk/Saturne/commit/5a7f0f32)
* [#1935] [Admin] fix: object.php sans référent ne lève plus de warning [`2abba2d3`](https://github.com/Evarisk/Saturne/commit/2abba2d3)
* [#1935] [Admin] fix: object.php, pwa.php et information.php refusent un module absent au lieu d'une fatale [`df766104`](https://github.com/Evarisk/Saturne/commit/df766104)
* [#1933] [Object] fix: prototype absent écarté avant de cloner [`90aef62f`](https://github.com/Evarisk/Saturne/commit/90aef62f)
* [#1933] [Object] fix: reprise des dossiers (PROVn) restés orphelins après la validation [`2016dc79`](https://github.com/Evarisk/Saturne/commit/2016dc79)
