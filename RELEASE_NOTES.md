# [Saturne] [23.6.0] - Activation des modules en un clic - Registre d'objets sans custom/

Description : Version corrective. Sur une instance neuve, un module Evarisk s'active désormais du premier coup avec tout ce dont il dépend, et Saturne ne suppose plus que les modules sont installés dans le dossier `custom/`.

**Avant d'installer cette version, mettez à jour DigiQuali en 23.2.1 et DoliCar en 23.1.2** (ou plus récent). Ces versions savent lire le nouveau format des chemins du registre d'objets. À côté de Saturne 23.6.0, DigiQuali 23.2.0 ou antérieur s'arrête sur une erreur dans certains écrans, par exemple l'impression d'étiquettes QR depuis la liste des tâches ou des expéditions.

## Améliorations & corrections

### Installation des modules

* **Un module s'active du premier coup sur une instance neuve.** Dolibarr installe un module avant ses dépendances : DigiQuali échouait sur « La table llx_saturne_object_standard n'existe pas », Digirisk perdait les horaires du médecin du travail, DoliMeet et DoliLetter rataient leur liaison aux projets, ReedCRM ses onglets Pocket. Saturne fournit maintenant aux modules un moyen d'activer leurs dépendances d'abord ; DigiQuali 23.2.1 et DoliCar 23.1.2 s'en servent déjà, les autres modules suivront dans leurs prochaines versions.
* **Plus de doublon de menu** (« Menu entry … /ecm/index.php already exists ») à l'activation d'un module qui dépend à la fois de Saturne et de la GED.

### Registre d'objets

* **Modules installés ailleurs que dans `custom/`** : les chemins des objets de Saturne ne portent plus ce préfixe en dur, un module placé dans un autre dossier de modules se charge normalement.

### Listes

* **Liste générique des objets du cœur à statut** (propositions, commandes, projets, tickets, expéditions…) : le statut s'affiche, et les 50 avertissements PHP par page disparaissent.
* **Liste des projets plus rapide** : les statuts d'opportunité sont lus une seule fois par page au lieu d'une fois par ligne.

### Agenda

* **Titre de l'onglet Événements adaptable** : un module peut donner à chaque objet son propre titre ; « Creation » est traduit.

### Ergonomie

* **Sélecteur de responsable** : la liste des utilisateurs ne s'affiche plus en texte brut sur la page à l'ouverture.
* **Menu latéral** : sous l'élément courant, les flèches de l'arborescence sont dans le bon sens et le premier clic ouvre la branche.
* **Onglet Fichiers joints** : le nombre et la taille des fichiers sont traduits, la taille s'affiche dans une unité lisible.

### Journal PHP

* **Plus de dépréciation PHP 8** à chaque chargement de la bibliothèque des médias.

## Testez les nouveautés maintenant

**Allez tester les modules Evarisk sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.5.0](https://github.com/Evarisk/Saturne/compare/23.5.0...23.6.0) et 23.6.0

* #1777 [Module] fix: add saturne_activate_module_dependencies() for init() [`#1778`](https://github.com/Evarisk/Saturne/pull/1778)
* #1779 [Module] fix: refresh conf before and during dependency activation to avoid duplicate modECM::init() [`#1780`](https://github.com/Evarisk/Saturne/pull/1780)
* #1766 [Object] rework: retirer le prefixe custom/ des entrees du registre d'objets [`#1767`](https://github.com/Evarisk/Saturne/pull/1767)
* #1739 [List] fix: statut des objets du coeur a fk_statut, et trace SATURNE_HOOK [`#1740`](https://github.com/Evarisk/Saturne/pull/1740)
* #1764 [List] fix: lire les statuts d'opportunite une fois par requete [`#1765`](https://github.com/Evarisk/Saturne/pull/1765)
* #1774 [Agenda] add: object specific title through an AgendaTitle<Element> lang key [`#1775`](https://github.com/Evarisk/Saturne/pull/1775)
* #1772 [JS] fix: bind the lazy user select fill on the select only [`#1773`](https://github.com/Evarisk/Saturne/pull/1773)
* #1768 [Menu] fix: expand only the current unit's own chevron in the left menu [`#1769`](https://github.com/Evarisk/Saturne/pull/1769)
* #1770 [Documents] fix: translate attached files count and size labels [`#1771`](https://github.com/Evarisk/Saturne/pull/1771)
* #1762 [Medias] fix: drop unusable default of $modulepart in saturne_show_medias_linked() [`#1763`](https://github.com/Evarisk/Saturne/pull/1763)
