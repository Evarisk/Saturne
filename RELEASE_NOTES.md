# [Saturne] [23.1.1] - Pages d'administration réparées

Description : Version corrective. Elle rétablit les pages d'administration des modules bâtis sur Saturne, en ajoutant les deux fonctions d'aide qu'elles appelaient sans qu'elles existent, et supprime les avertissements PHP 8 restants à la génération d'un document.

## Améliorations & corrections

### Administration

* Ajout de `saturne_check_admin_write_access()` et `saturne_constant_onoff()`, appelées par les pages d'administration de Digirisk mais absentes du socle : les sept pages de configuration du module étaient inaccessibles.
* Éteindre un réglage écrit désormais un `0` au lieu de supprimer la constante. Une constante supprimée était recréée à sa valeur par défaut à la prochaine activation ou mise à jour du module : le réglage se rallumait tout seul.
* Un utilisateur sans droit d'administration voit maintenant l'état d'un réglage, sous la forme d'un interrupteur désactivé, au lieu d'un interrupteur qui ne répondait pas.
* Le repli sans javascript des interrupteurs (liens `set_` / `del_`) est pris en charge et écrit lui aussi un `0`.

### Documents

* Correction des avertissements PHP 8 à la génération d'un document.

## Comparaison des versions [23.1.0](https://github.com/Evarisk/Saturne/compare/23.1.0...23.1.1) et 23.1.1
