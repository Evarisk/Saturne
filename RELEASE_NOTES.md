# [Saturne] [23.2.0] - Transfert d'entité et documents fiabilisés

Description : Cette version apporte l'export et l'import d'une entité vers une installation Dolibarr mono-entité, une page de maintenance pour les documents restés sans fichier, et des modèles d'email pour les tickets. Elle rétablit surtout la génération de documents sur Dolibarr 24, qui était complètement bloquée, et corrige une série de défauts qui laissaient des lignes mortes en base ou cassaient les modèles PDF des modules.

## Nouvelles fonctionnalités et innovations

### Transfert d'entité

* Export d'une entité complète — tables, champs personnalisés et documents — et import dans un Dolibarr mono-entité. Les modules absents de l'installation cible sont signalés avant l'import plutôt que découverts après.
* L'export suit les périmètres `core` et `full`, lit la liste des modules réellement actifs sur l'entité exportée, et l'import supporte une cible dont les colonnes diffèrent de la source.

### Maintenance

* Nouvelle page d'administration listant les documents dont la ligne en base ne désigne aucun fichier. Une génération interrompue avant que le fichier soit nommé laissait une ligne morte qui avait consommé une référence du compteur ; ces lignes se suppriment maintenant depuis l'interface.
* La page annonce explicitement la portée du nettoyage : toutes les entités si vous êtes super-administrateur sur l'entité maître, l'entité courante sinon.

### Tickets

* Modèles d'email pour les messages de clôture et de prise en charge d'un ticket.

## Améliorations & corrections

### Génération de documents

* **Dolibarr 24 : la génération de documents est réparée.** Le cœur y refuse tout modèle stocké hors de `documents/ecm` et `documents/doctemplates`, ce qui est le cas de tous les modèles livrés avec les modules, et ne transmet plus ses paramètres au générateur. Toute génération répondait `BadDirForTemplateFile` ou perdait son objet source.
* Le chemin du modèle choisi est désormais vérifié contre les répertoires de modèles déclarés par le module. Un fichier quelconque du serveur ne peut plus servir de modèle.
* Une génération qui échoue ne laisse plus de ligne orpheline en base.
* Un objet appartenant à une autre entité ne perd plus son répertoire de sortie : la génération retombe sur l'entité courante au lieu d'écrire nulle part.

### Modèles PDF

* Les aides PDF passent en `protected`, ce qui cassait trois modèles de modules ; `drawTable()` et le découpage des pages remontent dans `SaturneDocumentModel`, où les modules peuvent les réutiliser.
* Déclaration de la propriété `$height` sur `SaturneDocumentModel`.

### Interface

* Les infobulles posent leur libellé en texte au lieu de le concaténer dans du HTML, et la variante multiligne reste bornée à la fenêtre.
* Les listes génériques ne partagent plus leurs colonnes entre objets différents.
* Un tableau de bord sans graphique masqué ne déclenche plus d'avertissement.
* Correction des avertissements PHP 8 dans l'affichage des médias.

### Traductions

* Le domaine `errors` est chargé avant l'affichage : les messages d'événement ne perdent plus leurs paramètres, ils s'affichaient jusqu'ici amputés.
* `ErrorFileNotFound` du cœur n'est plus masqué par une clé du socle.

### Qualité et intégration continue

* La chaîne de build des assets passe de gulp 4 à sass et esbuild, avec un ordre de concaténation du JavaScript indépendant de la machine.
* Les assets sont vérifiés sur les pull requests, et les contrôles qualité se déclenchent désormais sur toute pull request, y compris sur la branche de maintenance.

## Comparaison des versions [23.1.1](https://github.com/Evarisk/Saturne/compare/23.1.1...23.2.0) et 23.2.0
