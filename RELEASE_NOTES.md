# [Saturne] [23.2.1] - Dolibarr 24 - Contrôles sur les branches de maintenance

Description : Cette version déclare le socle **compatible Dolibarr 24** et fait tourner la chaîne qualité sur les **branches de maintenance**, qui en étaient privées. Elle écrit aussi, dans le guide du socle, les deux règles du contrôle de paquet du Dolistore sur lesquelles les modules butaient à chaque livraison.

## Améliorations & corrections

### Compatibilité

* Le module déclare **Dolibarr 23 au minimum et 24 au maximum**. `need_dolibarr_version` est la borne qui compte : c'est elle qui autorise ou bloque l'activation.

### Intégration continue

* Le workflow qualité ne se déclenchait sur **aucune** des branches de maintenance : une pull request visant `23.0` ne lançait rien du tout, filtre de chemins ou pas. Les déclencheurs couvrent désormais ces branches par un motif, qui évite d'y revenir à chaque nouvelle ligne.

### Guide du socle

* Le `CLAUDE.md` porte désormais les deux règles du contrôle de paquet du Dolistore : le bootstrap `main.inc.php` **à deux tentatives** sur tout point d'entrée, et les classes du module incluses par `dol_include_once` plutôt que `DOL_DOCUMENT_ROOT`. Elles n'étaient écrites nulle part et revenaient à chaque nouveau fichier — l'exemple de `{module}.main.inc.php` du guide montrait lui-même la forme fautive.

## Comparaison des versions [23.2.0](https://github.com/Evarisk/Saturne/compare/23.2.0...23.2.1) et 23.2.1

* [#1663] [Doc] add: la règle du Dolistore sur le bootstrap des points d'entrée [`03eb798`](https://github.com/Evarisk/Saturne/commit/03eb798)
* [#1661] [Module] rework: bornes de version Dolibarr 23 minimum, 24 maximum [`ee59249`](https://github.com/Evarisk/Saturne/commit/ee59249)
* [#1658] [CI] fix: déclencher les contrôles sur les branches de maintenance [`2d332a8`](https://github.com/Evarisk/Saturne/commit/2d332a8)
