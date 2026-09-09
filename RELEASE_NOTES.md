# [Saturne] [23.1.0] - Listes personnalisables - Édition en ligne - Signature en masse

Description : Cette version fait de la liste générique un véritable outil de travail — colonnes redimensionnables et réordonnables par utilisateur, en-tête collant, édition en ligne, filtres en volet latéral et cartes d'indicateurs. Elle industrialise la signature avec des actions de masse, enrichit l'éditeur photo, et referme le chantier qualité : les cinq contrôles d'intégration continue passent enfin au vert.

## Nouvelles fonctionnalités et innovations

### Listes génériques

* Chaque utilisateur compose sa vue : **redimensionnement fluide des colonnes** et **réorganisation par glisser-déposer** via une poignée à six points, la disposition étant mémorisée par utilisateur et par liste.
* **En-tête de tableau collant** : les intitulés de colonnes restent visibles pendant le défilement, y compris la ligne de filtres classique.
* **Cartes d'indicateurs** au-dessus des listes filtrées, repliables et compactes, avec un mécanisme générique réutilisable par chaque module.
* **Barre de préréglages** avec des puces retirables, pour visualiser et lever les filtres actifs d'un geste.
* Nouveau hook `saturneListTopBanner` au-dessus du bandeau de titre, et le hook `saturnePrintFieldListLoopObject` reçoit désormais la ligne brute de la requête.

![Le sélecteur de colonnes ouvert sur une liste générique](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.1.0/.shots/23.1.0-liste-colonnes.png)

### Édition en ligne

* **Édition directe dans la liste**, toujours active sur les champs éditables : texte, sélecteurs et listes déroulantes, sans quitter la page ni ouvrir la fiche.
* Prise en charge des **extrafields** et validation optionnelle par expression régulière sur les champs texte.
* Le point d'entrée AJAX a été sécurisé et les objets en lecture seule ne sont plus modifiables, grâce au nouveau contrôle serveur `isModifiable()`.
* Le libellé de l'objet devient éditable directement depuis le bandeau de fiche.

<!-- 📸 Ajouter une screenshot ici -->

### Filtres

* **Volet latéral de filtres** avec compteur de filtres actifs et remise à zéro globale.
* **Bascule entre deux présentations** — ligne de filtres classique ou volet latéral — mémorisée par utilisateur.
* Retour du **filtre par tag de catégorie** sur les listes génériques.

### Signature

* **Signature automatique** des utilisateurs qui en ont fait la demande.
* **Action de masse** appliquant la signature électronique de l'utilisateur à plusieurs objets, et **page de signature en masse** pour signer au nom d'un seul participant.
* Lien direct vers la page de signature depuis les listes d'objets.

### Actions de masse

* **Valider**, proposée en option sur les listes d'objets.
* **Archiver et désarchiver** : action de masse avec confirmation, action de fiche `confirm_unarchive`, et méthode `setUnarchived()` ramenant un objet archivé à l'état validé.

### Médiathèque et éditeur photo

* **Éditeur photo séquentiel** lors d'un envoi multiple : les photos s'enchaînent sans quitter l'éditeur, avec redimensionnement de toutes les images et action « tout valider ».
* **Suppression d'une photo** directement depuis l'éditeur de la galerie, et affichage des outils configurable un par un.
* Section d'**envoi de documents** dans le bloc média, et **glisser-déposer** sur l'onglet des fichiers joints.
* Boutons distincts pour la **prise de vue** et pour la **galerie**, et option `hideNoPhoto` pour masquer le repli « pas de photo ».

<!-- 📸 Ajouter une screenshot ici -->

### Objets liables

* Nouvelle section d'administration des **éléments liables**, avec mesure de l'usage réel de chaque objet avant toute modification.
* **Synchronisation idempotente des extrafields**, aide de reconstruction des onglets et des hooks, et confirmation avant un changement de lien destructeur.

### Divers

* **API REST** : socle générique `SaturneApi`, dispatcher que chaque module peut étendre.
* **Graphes du tableau de bord cliquables**, renvoyant vers la liste correspondante.
* **Réorganisation par glisser-déposer** des éléments dans le menu des unités de travail.
* Emails de création de ticket fondés sur un **modèle d'email configurable**.
* `saturne_flatten_wysiwyg_blocks()` pour produire des sorties sans balises de bloc.

## Améliorations & corrections

### Performance

* Les extrafields sont **chargés en une seule requête** dans `fetchAll` au lieu d'une par ligne, ce qui supprime le N+1 le plus coûteux des pages de liste.
* Nouveau `saturne_select_users()` avec liste d'utilisateurs mise en cache par requête ; les utilisateurs ne sont plus listés que dans le premier sélecteur.
* La médiathèque ne liste ni ne mesure plus les fichiers inutilement.

### Vue liste

* Le sélecteur de colonnes s'affichait derrière l'en-tête collant ; le tri par colonne passe désormais par le formulaire de recherche et conserve filtres et contexte.
* Un champ restait bloqué en lecture après une sauvegarde réussie de l'édition en ligne.
* Le bouton « + » avait disparu de toutes les listes, et la disposition enregistrée des colonnes n'était pas appliquée sur le gabarit partagé.
* Les colonnes virtuelles ne provoquent plus d'erreur « Unknown column » dans la clause de recherche, les lignes groupées sont comptées correctement, et un tri invalide ne casse plus l'affichage.
* Le saut direct vers un enregistrement unique n'affiche plus une page blanche.
* Les lignes gardent une hauteur d'une ligne même lorsqu'une cellule contient du HTML enrichi, et la loupe d'aperçu masquée par la remise à zéro des cellules est de retour.

### Médiathèque

* Envoi d'une série de photos sans passer par la fenêtre de l'éditeur, ouverture de l'appareil photo sur le champ dédié, et restriction du champ documents aux documents.
* Une vignette est servie à la place de l'original, un favori supprimé cède la place au média suivant au lieu de masquer la photo, et le titre affiché redevient honnête.

### Documents et modèles

* Un modèle PDF n'affiche plus les templates ODT du modèle voisin sur la page de configuration, et les modèles d'un document frère y sont enfin visibles.
* La constante de modèle par défaut est construite sur le type d'objet, l'aperçu et la bascule « défaut » fonctionnent, et l'entité courante sert de repli pour les liens de document.
* Les guillemets sont retirés des noms de fichiers générés, qui cassaient la conversion ODT vers PDF, et le répertoire temporaire est créé avant copie.
* Le compteur des modèles de numérotation personnalisés était lu au mauvais offset, et l'appel mort à `strftime()` est supprimé.
* La page de signature sert le PDF généré au lieu d'un `.odt` inexistant, et résout correctement le modèle par défaut.

### Fiches, menus et interface

* Un enregistrement introuvable renvoie vers la liste de son type plutôt que vers une erreur fatale, avec une destination surchargeable par le module appelant.
* L'arborescence ne se déplie automatiquement que sur les pages concernées, et le menu gauche réduit ne déborde plus sur le contenu.
* Le logo du module n'est plus grisé sur l'accueil, l'en-tête des fenêtres modales reste au-dessus du menu supérieur de Dolibarr, et les tableaux du tableau de bord défilent sans emporter la page entière.

### Socle et compatibilité

* Saturne **déclare les modules dont il dépend** — ECM, Agenda, FCKeditor et Catégories — au lieu de laisser chaque module les redéclarer.
* Correction de plusieurs avertissements PHP 8 : propriétés dynamiques dépréciées, propriétés typées jamais reçues, variables non initialisées dans l'arborescence et le tableau de bord.
* Parité complète des fichiers de langue en_US, clés mortes et doublons retirés.
* La compatibilité annoncée est resserrée sur **Dolibarr 23**.

### Intégration continue

* Les **cinq contrôles qualité passent au vert** sur `develop` : jshint, phpcs, phan, phpstan et phpunit.
* Passage de `phpcbf` sur les 1412 violations PSR-12 automatiquement corrigeables, et respect des types déclarés aux appels signalés par phan.
* Un `alert()` de débogage oublié dans le popover de filtre a été retiré de la production.

## Comparaison des versions [23.0.0](https://github.com/Evarisk/Saturne/compare/23.0.0...23.1.0) et 23.1.0
