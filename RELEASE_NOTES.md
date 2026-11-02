# [Saturne] [23.7.0] - Export Excel des listes et des tableaux de bord - Nouveau design pour tous les modules

Description : Toutes les listes et tous les tableaux de bord des modules Evarisk s'exportent désormais en un **classeur Excel prêt à analyser**, et le **design moderne** de Digirisk s'étend à DigiQuali, DoliMeet, DoliCar et aux autres modules. Une vue d'ensemble des modèles d'e-mail montre enfin quel réglage envoie quel modèle, et la médiathèque accepte le glisser-déposer.

## 🎬 La nouveauté en vidéo

<a href="https://github.com/Evarisk/Saturne/releases/download/23.7.0/saturne-23.7.0.mp4"><img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/video_poster_23.7.0.jpeg" alt="Exports Excel et nouveau design, en vidéo (59 s)"></a>

*Cliquez sur l'image pour voir la vidéo (59 s).*

## Nouvelles fonctionnalités et innovations

### Export Excel de toutes les listes

* Un bouton **Export Excel** en haut de chaque liste des modules : contrôles, modèles et questionnaires DigiQuali, sessions DoliMeet, cartes grises DoliCar, diffusions DoliLetter, plans de prévention, permis de feu et enquêtes Digirisk… Aucun réglage à faire.
* Le classeur reprend **toutes les lignes filtrées, toutes pages comprises**, avec les colonnes choisies à l'écran, dans leur ordre, et le tri de la liste.
* Un onglet **Sommaire** rappelle les filtres appliqués, le nombre de lignes, la première et la dernière création et la répartition par statut. Suivent l'onglet **Liste**, puis une **répartition par colonne** (modèle, contrôleur, verdict, tiers, projet…) et un onglet **Par mois**.
* Dans Excel, les dates et les nombres sont de vraies dates et de vrais nombres, prêts pour un filtre ou un tableau croisé. Les références et les objets liés sont des **liens vers leur fiche**.
* Les gros exports, de plusieurs milliers de lignes, vont au bout sans être coupés par la limite de temps du serveur.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/export-liste.png" alt="Liste des contrôles DigiQuali avec le bouton Export Excel en haut à droite">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/classeur-sommaire.png" alt="Onglet Sommaire du classeur : filtres, chiffres clés et répartition par statut">

### Export des seules lignes cochées

* Nouvelle action de masse **« Export Excel de la sélection »** sur ces mêmes listes : le même classeur, réduit aux lignes cochées. Le sommaire indique le nombre de lignes sélectionnées.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/export-selection.png" alt="Trois contrôles cochés et l'action de masse « Export Excel de la sélection »">

### Export Excel des tableaux de bord

* Un bouton **Export Excel** en haut de chaque tableau de bord télécharge **tout le tableau de bord en un seul classeur**, au lieu d'un fichier CSV par graphique.
* Le Sommaire reprend les filtres et les chiffres clés des encarts. Chaque graphique devient un onglet aux **couleurs de sa légende**, avec pour chaque catégorie le lien vers la liste filtrée correspondante. Chaque liste du tableau de bord a aussi son onglet.
* Les encarts fermés et les graphiques vides ne sont pas exportés, comme à l'écran.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/export-tableau-de-bord.png" alt="Tableau de bord DigiQuali et son bouton Export Excel">

### Nouveau design pour tous les modules

* Le **thème moderne** arrivé dans Digirisk 23.6.0 s'applique désormais à **tous les modules Evarisk** : fond gris-bleu, cartes blanches arrondies, onglets en pastilles, statuts teintés et fenêtres floutées.
* Les pages publiques et les applications mobiles gardent leur propre design. Les pages natives de Dolibarr ne changent pas.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/theme-avant.png" width="49%" alt="Fiche d'un contrôle DigiQuali avant"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/theme-apres.png" width="49%" alt="La même fiche avec le nouveau design">

### Vue d'ensemble des modèles d'e-mail

* Nouvel onglet **« Vue d'ensemble des modèles »** dans *Configuration > E-mails*, à côté de la liste de Dolibarr : les modèles sont **groupés par élément** (tickets, contrats, factures…), avec leurs libellés traduits.
* La colonne **« Utilisé par »** montre quel réglage envoie chaque modèle. Des badges signalent le modèle par défaut, les modèles privés, ceux qui joignent un document et les doublons.
* L'**aperçu** affiche le sujet, le contenu mis en forme et les variables utilisées. Recherche instantanée, filtre des modèles actifs ou utilisés, activation et désactivation sur place ; le crayon ouvre l'édition de Dolibarr.
* Les modèles que Dolibarr ne propose pas à l'envoi sont regroupés à part, avec la raison.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/modeles-email.png" alt="Vue d'ensemble des modèles d'e-mail : un modèle ouvert, son aperçu et les réglages qui l'utilisent">

### Médiathèque

* **Glisser-déposer** : des images déposées sur la galerie ou sur la page *Bibliothèque de médias* rejoignent la bibliothèque, avec la même barre de progression qu'un ajout par le bouton « + ». Les fichiers qui ne sont pas des images sont écartés et signalés.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.7.0/mediatheque-depot.png" alt="Page Bibliothèque de médias pendant un glisser-déposer : « Déposez vos images ici pour les ajouter à la bibliothèque »">

### Administration des modules

* **Droit « Modifier la configuration »** : un module peut confier ses réglages à un utilisateur qui n'est pas administrateur de Dolibarr (DigiQuali le propose). Avec le seul droit de lecture, les réglages restent visibles mais désactivés.
* **Mises à jour protégées** : un module qui demande une version plus récente de Saturne ou de Dolibarr n'est plus mis à jour à moitié. Son accueil l'indique et attend la mise à jour du socle.
* Le bouton **Configuration** d'un module dans le menu de gauche peut ouvrir directement sa page de réglages principale : le Document unique pour Digirisk.

## Améliorations & corrections

### Documents

* **Logo de la société** : il garde ses proportions dans les documents ODT et PDF et s'ajuste à son cadre. Sans logo, plus de balise `{mycompany_logo}` brute dans le document ni d'avertissement PHP.
* Les modèles ODT sont choisis dans la **langue du document**.
* **PDF** : la dernière ligne d'une cellule de tableau n'est plus perdue.

### Signatures et présences

* Le lien public de signature est vérifié avant l'ouverture de la page.
* Un signataire au statut inconnu s'affiche « Inconnu », sans avertissement PHP. Les anciens signataires « absents » de DoliMeet sont convertis vers la colonne de présence.
* Ajouter des participants fonctionne même quand aucun utilisateur ni contact n'est encore inscrit.
* La suppression d'un fichier de signature temporaire ne retire plus la protection du dossier `documents/temp`.

### Tickets

* La variable `__TICKET_TAGS__` est remplie dans les e-mails de création de ticket.

### Interface

* Le menu du haut passe sous une fenêtre ouverte, sur tous les navigateurs.
* Les fenêtres des modules ne sont plus déformées par les styles d'autres modules.
* Le pied d'une fenêtre (boutons de validation) ne déborde plus de son cadre, et la note de mise à jour garde sa mise en page.
* Les pastilles de statut en double sont retirées, et le thème reçoit les derniers correctifs faits dans Digirisk.
* Les feuilles de style et scripts sont rechargés après une mise à jour, sans vider le cache du navigateur.
* Le QR code d'installation de l'application mobile se lit mieux, même abîmé ou imprimé petit.

### Compatibilité

* Saturne fonctionne installé ailleurs que dans le dossier `custom/`, et retrouve les modèles de numérotation rangés dans un sous-dossier.
* Les droits des vues Agenda et Horaires sont lus de la même façon que dans Dolibarr.
* Un objet sans statut n'arrête plus l'affichage de son libellé.
* Sécurité : l'accès public aux images est limité aux images des pages publiques.

## Testez les nouveautés maintenant

**Allez tester les exports Excel et le nouveau design sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.6.0](https://github.com/Evarisk/Saturne/compare/23.6.0...23.7.0) et 23.7.0

* [#1844] [CI] ci: contrôle « API Saturne publiée » réutilisable par la CI des modules [`9c51231b`](https://github.com/Evarisk/Saturne/commit/9c51231b)
* [#1846] [CI] ci: retrait du workflow Generate Auto Changelog [`367a73d4`](https://github.com/Evarisk/Saturne/commit/367a73d4)
* [#1847] [Modal] fix: modal footer overflowing its container [`99017420`](https://github.com/Evarisk/Saturne/commit/99017420)
* [#1843] [Index] feat: ne pas mettre a jour un module qui demande un Saturne plus recent [`06bf89ff`](https://github.com/Evarisk/Saturne/commit/06bf89ff)
* [#1841] [PDF] feat: drawImageInBox() et drawCompanyLogo(), image et logo ajustés à leur cadre [`4f17a6dd`](https://github.com/Evarisk/Saturne/commit/4f17a6dd)
* [#1839] [Modal] fix: reset the .modal-content properties other stylesheets set [`ba1258b4`](https://github.com/Evarisk/Saturne/commit/ba1258b4)
* [#1836] [Export] feat: action de masse, export Excel des lignes cochées [`0b98cb31`](https://github.com/Evarisk/Saturne/commit/0b98cb31)
* [#840] [PWA] fix: QR code de la PWA en correction d'erreur H au lieu de L [`33b1de2b`](https://github.com/Evarisk/Saturne/commit/33b1de2b)
* [#1832] [Export] feat: gros exports Excel, limite de temps de PHP et styles des cellules réutilisés [`f84e4a5a`](https://github.com/Evarisk/Saturne/commit/f84e4a5a)
* [#1833] [SCSS] fix: règles de pastilles de statut en double retirées du thème partagé [`c61598f1`](https://github.com/Evarisk/Saturne/commit/c61598f1)
* [#1760] [SCSS] fix: correctifs du thème Digirisk postérieurs au 20/10 reportés dans le thème partagé [`7f1a7894`](https://github.com/Evarisk/Saturne/commit/7f1a7894)
* [#1830] [Signature] fix: convertir les anciens signataires absents vers la colonne de présence [`f9a05082`](https://github.com/Evarisk/Saturne/commit/f9a05082)
* [#1825] [Dashboard] feat: export Excel d'un tableau de bord entier [`db720c22`](https://github.com/Evarisk/Saturne/commit/db720c22)
* [#1826] [Signature] fix: libellé d'un signataire au statut inconnu, sans avertissement PHP [`143c4f2d`](https://github.com/Evarisk/Saturne/commit/143c4f2d)
* [#1824] [Export] feat: export Excel générique de toutes les listes Saturne [`979cf822`](https://github.com/Evarisk/Saturne/commit/979cf822)
* [#1816] [Ticket] fix: __TICKET_TAGS__ filled in the ticket creation emails [`e7908631`](https://github.com/Evarisk/Saturne/commit/e7908631)
* [#1819] [Lib] fix: find numbering modules filed one folder deeper [`96d45b6f`](https://github.com/Evarisk/Saturne/commit/96d45b6f)
* [#1817] [Email] feat: saturneEmailTemplateSettings hook, modules declare the settings designating a template [`2880f437`](https://github.com/Evarisk/Saturne/commit/2880f437)
* [#1812] [Export] feat: classe générique d'export Excel multi-onglets [`343324f2`](https://github.com/Evarisk/Saturne/commit/343324f2)
* [#1815] [Document] fix: modèles ODT dans la langue du document [`a0922c77`](https://github.com/Evarisk/Saturne/commit/a0922c77)
* [#1813] [Menu] feat: landing page of the config entry per module, risk assessment document tab for digirisk [`7f7c8cff`](https://github.com/Evarisk/Saturne/commit/7f7c8cff)
* [#1808] [Email] feat: email templates overview grouped by element, with preview and the settings using them [`b44f522a`](https://github.com/Evarisk/Saturne/commit/b44f522a)
* [#1809] [Medias] fix: limit the public image wrapper to the images of the public pages [`fbac65c6`](https://github.com/Evarisk/Saturne/commit/fbac65c6)
* [#1809] [Signature] fix: validate the public signature link before loading the page [`e3313963`](https://github.com/Evarisk/Saturne/commit/e3313963)
* [#1806] [Signature] fix: remove_file keeps documents/temp/index.php [`153b2fab`](https://github.com/Evarisk/Saturne/commit/153b2fab)
* [#1804] [PDF] fix: drawTable measures its rows with the border it draws, the last line of a cell was dropped [`4be7e026`](https://github.com/Evarisk/Saturne/commit/4be7e026)
* [#1802] [Medias] feat: drag and drop images into the media library [`6d58ad98`](https://github.com/Evarisk/Saturne/commit/6d58ad98)
* [#1800] [Assets] fix: version the head assets of every page through the llxHeader hook [`7fa2cf18`](https://github.com/Evarisk/Saturne/commit/7fa2cf18)
* [#1798] [Modal] fix: the top menu goes under an open modal without body:has() [`cbfcafe3`](https://github.com/Evarisk/Saturne/commit/cbfcafe3)
* [#1796] [Index] fix: skip the module auto-update when Dolibarr is too old, report a failed init() [`34146285`](https://github.com/Evarisk/Saturne/commit/34146285)
* [#1794] [Saturne] fix: s'inclure par des chemins relatifs au fichier, pas par le nom du dossier [`09c9d33b`](https://github.com/Evarisk/Saturne/commit/09c9d33b)
* [#1791] [Doc] docs: inclure Saturne par dol_include_once / dol_buildpath dans les exemples du CLAUDE.md [`e31f7100`](https://github.com/Evarisk/Saturne/commit/e31f7100)
* [#1790] [Document] fix: plus de balise {mycompany_logo} brute dans un ODT quand la société n'a pas de logo [`db16d854`](https://github.com/Evarisk/Saturne/commit/db16d854)
* [Document] fix: no getimagesize() warning when the company has no logo [`b7bcfd4f`](https://github.com/Evarisk/Saturne/commit/b7bcfd4f)
* [#1743] [Audio] docs: onUploadComplete changes in 23.7.0, not 23.5.0 [`f6c78bbd`](https://github.com/Evarisk/Saturne/commit/f6c78bbd)
* [#1788] [Objet] fix: getLibStatut() ne plante plus sur un statut null [`46b45617`](https://github.com/Evarisk/Saturne/commit/46b45617)
* [#1786] [Doc] docs: vidéo de release réservée aux grosses nouveautés [`393dc2c2`](https://github.com/Evarisk/Saturne/commit/393dc2c2)
* [#1781] [Attendants] fix: default to an empty exclusion list when no user or contact is added yet [`cb7c10f3`](https://github.com/Evarisk/Saturne/commit/cb7c10f3)
* [#1760] [Header] feat: classe saturne-theme sur les pages connectées [`8d0969c2`](https://github.com/Evarisk/Saturne/commit/8d0969c2)
* [#1760] [SCSS] feat: thème moderne partagé du back-office [`1984e26a`](https://github.com/Evarisk/Saturne/commit/1984e26a)
* [#1743] [Audio] feat: saturne:audio-uploaded event once a voice note is saved [`37cc71e0`](https://github.com/Evarisk/Saturne/commit/37cc71e0)
* [#1731] [View] fix: droits des vues agenda et horaires lus par hasRight() [`20bc54b8`](https://github.com/Evarisk/Saturne/commit/20bc54b8)
* [#2332] [Admin] feat: droit adminpage->write pour modifier la configuration d'un module sans etre administrateur [`8de2b3c7`](https://github.com/Evarisk/Saturne/commit/8de2b3c7)
* [Document] fix: keep company logo aspect ratio in ODT documents [`4b16c174`](https://github.com/Evarisk/Saturne/commit/4b16c174)
