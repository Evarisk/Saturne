# [Saturne] [23.10.0] - Listes plus compactes - Liens publics dans la bonne société

Description : Les en-têtes des listes rétrécissent : la poignée qui déplace une colonne n'apparaît plus qu'au survol, au-dessus du titre, et une liste trop large défile dans son cadre sans élargir la page. En multi-sociétés, les liens publics (questionnaires, signatures, émargements) s'ouvrent dans la société qu'ils désignent. La génération des documents, la fusion de tiers et plusieurs pages gagnent en fiabilité.

## Nouvelles fonctionnalités et innovations

### Listes : des en-têtes aussi étroits que leurs colonnes

* La poignée qui sert à **déplacer une colonne** ne réserve plus de place à gauche de chaque titre. Elle apparaît au survol de l'en-tête, couchée **au-dessus du titre**, dans la marge haute.
* Les en-têtes reprennent la largeur de leurs cellules : une liste de quinze colonnes gagne environ 300 pixels de large et tient plus souvent dans l'écran.
* Toutes les listes du thème Saturne en profitent, dans tous les modules : DigiQuali, Digirisk, DoliCar, DoliMeet, DoliSIRH, EasyURL, DoliLetter, ReedCRM…

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/refs/heads/assets/release-23.10.0/liste-poignee-colonnes.png" alt="Liste des contrôles DigiQuali : au survol de l'en-tête « Libellé », la poignée de déplacement apparaît couchée au-dessus du titre ; les en-têtes Réf., Objet contrôlé, Libellé, Modèle et Contrôleur sont aussi étroits que leurs colonnes">

## Améliorations & corrections

### Listes

* Une liste plus large que l'écran **défile dans son propre cadre** : la page reste à la taille de la fenêtre, et le menu, le titre et les boutons restent visibles.

### Pages publiques et signature

* Sur une instance **multi-sociétés**, un lien public s'ouvre dans la société qu'il désigne, même quand le module n'est pas activé dans la société principale. Auparavant, le lien renvoyait vers la page des modules.
* La page publique de signature d'une **session de formation** DoliMeet génère la feuille d'émargement et la remet enfin au stagiaire.
* Dans le tableau des participants, les demandes de signature envoyées par mail sont comptées correctement, sans avertissement PHP.
* La page publique de signature applique les mêmes règles que la fiche : une signature enregistrée ne peut plus être remplacée depuis le lien, seul un signataire attendu peut signer, et seulement sur un objet validé (déjà livré en 23.9.2).

### Documents

* Deux documents générés en même temps ne se gênent plus : chaque génération a ses propres fichiers temporaires, supprimés à la fin, même en cas d'erreur. Fini les images manquantes et les avertissements « unlink » quand deux générations se croisaient.

### Tiers

* À la **fusion de deux tiers**, les objets des modules Saturne (sessions DoliMeet, certificats, entreprises extérieures Digirisk…) suivent le tiers conservé, au lieu de rester rattachés au tiers supprimé.

### Configuration et droits

* Un utilisateur qui n'a que la lecture de la configuration voit des interrupteurs **grisés**, avec un curseur interdit, au lieu d'interrupteurs qui semblaient actifs.
* Les pages de configuration de Saturne ne laissent plus d'avertissements PHP pour un utilisateur qui n'a pas le droit de les lire.

### Stabilité

* L'accueil de Saturne ne s'arrête plus sur une erreur de mémoire.
* Plus d'avertissement PHP sur les événements que d'autres modules envoient avec un objet particulier (webobserver, ExternalAccess).
* La fenêtre de badge ne laisse plus d'avertissement PHP à l'affichage.
* Un onglet d'un module n'est plus remplacé sans prévenir par un onglet de Saturne placé au même rang : celui de Saturne prend la place libre suivante.

## Testez les nouveautés maintenant

**Allez tester les nouvelles listes, plus compactes, sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.9.2](https://github.com/Evarisk/Saturne/compare/23.9.2...23.10.0) et 23.10.0

* [#1975] [Attendants] fix: mails de signature comptés une fois par tableau, sans « Undefined array key » ligne 118 [`d2491859`](https://github.com/Evarisk/Saturne/commit/d2491859)
* [#1974] [Signature] fix: la page publique génère et sert la feuille d'émargement d'une session de formation [`c4699f2e`](https://github.com/Evarisk/Saturne/commit/c4699f2e)
* [#1968] [Public] fix: une page publique s'ouvre dans l'entité de son lien en multi-sociétés [`f09a0317`](https://github.com/Evarisk/Saturne/commit/f09a0317)
* [Signature] fix: statut validé de l'objet lu sans supposer sa constante [`53a75d6a`](https://github.com/Evarisk/Saturne/commit/53a75d6a)
* [Signature] fix: page publique de signature sans avertissement quand l'interface est fermée [`d42b07d0`](https://github.com/Evarisk/Saturne/commit/d42b07d0)
* [Signature] fix: contrôles de la page publique de signature [`7c06bfe7`](https://github.com/Evarisk/Saturne/commit/7c06bfe7)
* [#1966] [SCSS] fix: interrupteur désactivé grisé, curseur interdit [`1db3268f`](https://github.com/Evarisk/Saturne/commit/1db3268f)
* [#1964] [Document] fix: restes d'une génération interrompue repérés par leur vraie date [`d977f053`](https://github.com/Evarisk/Saturne/commit/d977f053)
* [#1964] [Document] fix: fichiers temporaires propres à chaque génération, plus de temp/ vidé en fin de génération [`b76e7906`](https://github.com/Evarisk/Saturne/commit/b76e7906)
* [#1962] [Component] fix: modale de badge sans « Undefined variable $supplierData » [`f804de40`](https://github.com/Evarisk/Saturne/commit/f804de40)
* [#1960] [Object] fix: onglet du module écrasé sans rien dire par un onglet de Saturne sur le même index [`2910e2b0`](https://github.com/Evarisk/Saturne/commit/2910e2b0)
* [#1957] [List] feat: poignée de déplacement des colonnes dans la marge haute de l'en-tête, en-têtes aussi étroits que leurs cellules [`9c4473ca`](https://github.com/Evarisk/Saturne/commit/9c4473ca)
* [#1956] [List] fix: une liste plus large que l'écran défile dans son cadre, la page tient dans la fenêtre [`6f2dd560`](https://github.com/Evarisk/Saturne/commit/6f2dd560)
* [#1954] [Trigger] fix: plus d'avertissement sur un événement dont l'objet n'est pas un CommonObject [`645bc324`](https://github.com/Evarisk/Saturne/commit/645bc324)
* [#1952] [Dashboard] fix: l'accueil de Saturne ne se rappelle plus sans fin (fatale mémoire) [`69d14767`](https://github.com/Evarisk/Saturne/commit/69d14767)
* [#1950] [Admin] fix: droit de lecture de la configuration lu par hasRight(), sans avertissement pour un utilisateur qui ne l'a pas [`7086c567`](https://github.com/Evarisk/Saturne/commit/7086c567)
* [#1945] [Hook] fix: move the Saturne objects of an absorbed thirdparty on merge [`886915b1`](https://github.com/Evarisk/Saturne/commit/886915b1)
