# [Saturne] [23.8.0] - Sommaire des pages de configuration - Recherche et filtres dans les fichiers joints

Description : Les pages de configuration des modules Evarisk ont désormais un **sommaire** qui mène droit à la bonne section, la boîte des **fichiers joints** se trie, se filtre par type et se fouille par nom, et les listes **signalent les statuts masqués** par leur filtre par défaut. Les pages riches en photos s'ouvrent plus vite, et les modules fonctionnent dans une racine alternative à `custom/`.

## Nouvelles fonctionnalités et innovations

### Pages de configuration : un sommaire à côté du contenu

* Sur les pages de configuration qui comptent au moins deux sections, un **sommaire** s'affiche à gauche du contenu. Un clic amène à la section, et la section à l'écran reste surlignée pendant le défilement.
* Chaque entrée porte son icône. Les modèles de numérotation et les réglages de chaque objet apparaissent en sous-entrées.
* Chaque section a son adresse : un lien envoyé à un collègue ouvre la page directement sur la bonne section.
* Quand la place manque à côté, le sommaire se range au-dessus du contenu. Rien à régler : 34 pages de configuration des modules Evarisk en profitent d'office.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.8.0/configuration-sommaire.png" alt="Configuration du Document unique de Digirisk avec son sommaire à gauche : données des risques, des évaluations, des évaluateurs, des signalisations, synthèse publique">

### Fichiers joints : tri, recherche et filtres par type

* La boîte des fichiers joints se trie par **nom, taille ou date**. Le tri choisi est retenu pour chaque type de document.
* Une **recherche** sur le nom du fichier, sans tenir compte des majuscules ni des accents. La touche Échap l'efface.
* Des **filtres par type** (ODT, PDF, ZIP, images, liens) affichent le nombre de fichiers de chaque type.
* La liste s'ouvre sur les 10 derniers fichiers, avec un bouton « Afficher les N autres fichiers ». Le nombre total s'affiche à côté du titre, et la boîte ne déborde plus sur téléphone.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.8.0/documents-filtres.png" alt="Fichiers joints du Document unique : 52 fichiers, recherche, filtres ODT 16, PDF 31, ZIP 5, tri par date et bouton « Afficher les 42 autres fichiers »">

### Listes : les statuts masqués sont signalés

* Quand le filtre de statut par défaut cache une partie de la liste (les archivés, par exemple), une **pastille** à côté de l'entonnoir le dit : « Archivé masqué ». Un clic affiche tous les statuts, en gardant les autres filtres et le tri.
* « Supprimer ce filtre » sur la colonne des statuts vide bien le filtre, au lieu de ne garder que les brouillons.
* Le menu de filtre d'une colonne reste dans la fenêtre, même sur la dernière colonne.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.8.0/statuts-masques.png" alt="Liste des questions DigiQuali : la pastille « Archivé masqué » à côté de l'entonnoir du titre">

### Listes : verrouillage en masse et tri sur plusieurs critères

* Nouvelle action de masse **Verrouiller** : les objets cochés sont verrouillés en une fois, avec les mêmes effets que depuis leur fiche. Ceux qui ne peuvent pas encore l'être (non signés, sans verdict, brouillons…) sont ignorés et signalés, sans interrompre le lot. Elle s'affiche dans les modules qui l'activent : les contrôles DigiQuali et les navettes DoliSIRH, dans leurs prochaines versions.
* Une colonne peut proposer **plusieurs critères de tri** dans son menu, par exemple la date de la prochaine relance ou le nombre de relances en retard pour les opportunités de ReedCRM. Le critère actif s'affiche sous le titre de la colonne.

## Améliorations & corrections

### Performances

* Les photos des fenêtres fermées ne se chargent qu'à l'ouverture de la fenêtre, et le navigateur les garde en cache. L'aperçu de la galerie utilise des vignettes réduites. Les pages chargées de photos, comme l'onglet Risques de Digirisk, s'ouvrent nettement plus vite.

### Sécurité

* Les liens et appels AJAX de Saturne portent le jeton anti-CSRF.
* Le type des médias envoyés est vérifié plus strictement.
* Les droits sont lus comme dans Dolibarr : plus d'avertissement PHP pour un utilisateur qui n'a pas le droit.

### Signatures

* Quand l'e-mail de demande de signature ne part pas, la page des participants dit à qui et pourquoi (adresse refusée, identifiant refusé, serveur injoignable), au lieu de l'erreur brute en anglais. Le détail technique et le lien vers la configuration des e-mails sont réservés aux administrateurs.

### Documents

* Un « & » dans le nom d'un document n'empêche plus sa conversion en PDF.
* Chaque bouton de téléchargement d'un modèle ODT télécharge son propre fichier.

### Exports

* Un attribut supplémentaire d'objet lié est gardé tant qu'un modèle d'export enregistré l'utilise. Ces exports ne lèvent plus un avertissement par ligne ni ne sortent une colonne vide sans titre, et retrouvent leur mise en page d'origine.

### Agenda

* L'onglet Agenda des objets se filtre et se trie sans erreur fatale, et les filtres ID, propriétaire et état sont enfin appliqués.

### Listes et fiches

* L'édition en ligne et la fenêtre de survol d'un objet sont traduites.
* Une liste triable dont l'enregistrement échoue retire son indicateur de chargement et affiche l'erreur.
* La confirmation « Verrouiller » ne s'affiche que sur les listes qui proposent le verrouillage en masse.
* La liste d'un filtre de colonne passe par-dessus la fenêtre, avec une seule ligne vide.
* Les étiquettes du thème moderne tiennent sur une ligne.
* Une fiche dont le module ne déclare pas ses onglets s'affiche sans onglet, au lieu d'une erreur fatale.

### Compatibilité

* Saturne et ses modules fonctionnent dans une racine alternative à `custom/` : liens, redirections, appels AJAX, recherche des modules, modèles ODT et dossiers temporaires.

## Testez les nouveautés maintenant

**Allez tester le sommaire des pages de configuration et les filtres des fichiers joints sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.7.0](https://github.com/Evarisk/Saturne/compare/23.7.0...23.8.0) et 23.8.0

* [#1906] [LinkedObject] fix: keep a linked object extrafield that a saved export model still uses [`ba459c0c`](https://github.com/Evarisk/Saturne/commit/ba459c0c)
* [#1907] [Signature] fix: message clair quand l'email de demande de signature ne part pas [`25204bcf`](https://github.com/Evarisk/Saturne/commit/25204bcf)
* [#1902] [Lib] fix: saturne_get_fiche_head() shows no tabs instead of a fatal without prepare head function [`0cce7fe4`](https://github.com/Evarisk/Saturne/commit/0cce7fe4)
* [#1900] [Setup] feat: list of the sections beside the content of the setup pages [`32d6b6ce`](https://github.com/Evarisk/Saturne/commit/32d6b6ce)
* [#1898] [Assets] chore: rebuild the bundles [`fc52cdef`](https://github.com/Evarisk/Saturne/commit/fc52cdef)
* [#1898] [List] feat: sort a column on several criteria from its menu (sortkeys) [`02af3c34`](https://github.com/Evarisk/Saturne/commit/02af3c34)
* [#1896] [Medias] fix: renforcement de la validation des types de médias à l'upload [`4ebe7508`](https://github.com/Evarisk/Saturne/commit/4ebe7508)
* [#1894] [SCSS] fix: keep the tags of the modern theme on one line [`acf970ad`](https://github.com/Evarisk/Saturne/commit/acf970ad)
* [#1892] [List] fix: liste du filtre de colonne par-dessus la fenêtre, une seule ligne vide [`e04b7bd9`](https://github.com/Evarisk/Saturne/commit/e04b7bd9)
* [#1889] [Médias] fix: les photos des modales fermées attendent leur ouverture, et sont gardées en cache [`d6058b0d`](https://github.com/Evarisk/Saturne/commit/d6058b0d)
* [#1887] [Documents] feat: sort, search and type filters in the attached files box [`9c02ed1c`](https://github.com/Evarisk/Saturne/commit/9c02ed1c)
* [#1885] [Document] fix: un « & » dans le nom du document empêche la conversion ODT vers PDF [`ea2ec8f2`](https://github.com/Evarisk/Saturne/commit/ea2ec8f2)
* [#1881] [JS] fix: a failed save of a sortable table removes its loader and shows the error [`0ead8b88`](https://github.com/Evarisk/Saturne/commit/0ead8b88)
* [#1879] [Agenda] fix: filtres et tri de l'agenda sans erreur fatale, filtres ID, propriétaire et état appliqués [`9931e148`](https://github.com/Evarisk/Saturne/commit/9931e148)
* [#1877] [Documents] fix: chaque bouton de téléchargement d'un gabarit ODT télécharge son propre fichier [`69fffe56`](https://github.com/Evarisk/Saturne/commit/69fffe56)
* [#1874] [Module] fix: modules cherchés dans toutes les racines, pas à côté de Saturne [`47acee19`](https://github.com/Evarisk/Saturne/commit/47acee19)
* [#1874] [JS] fix: URL des appels AJAX de Saturne résolue côté serveur [`2d79ca2b`](https://github.com/Evarisk/Saturne/commit/2d79ca2b)
* [#1874] [Module] fix: liens et redirections sans custom/ en dur [`be4f0307`](https://github.com/Evarisk/Saturne/commit/be4f0307)
* [#1873] [Documents] fix: modèles ODT et dossiers temp/ retrouvés quand le module est dans une racine alternative [`7ec884cd`](https://github.com/Evarisk/Saturne/commit/7ec884cd)
* [#1871] [Liste] fix: edition en ligne traduite (aria-label, messages, calendrier) [`bd3b24d0`](https://github.com/Evarisk/Saturne/commit/bd3b24d0)
* [#1868] [Rights] fix: hasRight() au lieu des droits lus en direct, plus de warnings sans droit [`3f75c42a`](https://github.com/Evarisk/Saturne/commit/3f75c42a)
* [#1867] [List] fix: confirmation « Verrouiller » seulement sur les listes qui ont opté pour le verrouillage en masse [`5ec5c680`](https://github.com/Evarisk/Saturne/commit/5ec5c680)
* [#1865] [List] feat: statuts masqués par le filtre de statut signalés, popover de filtre dans la fenêtre [`616fad2f`](https://github.com/Evarisk/Saturne/commit/616fad2f)
* [#1863] [Object] fix: popup de survol traduit, langues chargées dans l'appel AJAX [`80f4fbc2`](https://github.com/Evarisk/Saturne/commit/80f4fbc2)
* [#1861] [List] feat: action de masse Verrouiller sur les listes d'objets [`17c60317`](https://github.com/Evarisk/Saturne/commit/17c60317)
* [#1859] [Saturne] fix: URL de Saturne par dol_buildpath, sans custom/ en dur [`a84265d1`](https://github.com/Evarisk/Saturne/commit/a84265d1)
* [#1857] [Signature] fix: signature_url nullable comme les autres colonnes facultatives [`cb14ba19`](https://github.com/Evarisk/Saturne/commit/cb14ba19)
* [#1855] [CI] ci: actions GitHub en Node 24 (checkout@v7, setup-node@v7, cache@v6, upload-artifact@v7, action-gh-release@v3) [`01011e52`](https://github.com/Evarisk/Saturne/commit/01011e52)
* [#1716] [CSRF] fix: ajouter le token CSRF aux liens et appels AJAX [`375234fc`](https://github.com/Evarisk/Saturne/commit/375234fc)
