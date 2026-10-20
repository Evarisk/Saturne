# [Saturne] [23.5.0] - URL courtes publiques - QR codes

Description : Vos modules peuvent désormais partager une page publique par un lien court, qui s'ouvre sans connexion et tient dans un QR code ou un SMS. Rien à installer, aucun serveur externe.

## 🎬 La nouveauté en vidéo

[![URL courtes et QR codes en vidéo (34 s)](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.5.0/video_poster_23.5.0.jpeg)](https://github.com/Evarisk/Saturne/releases/download/23.5.0/saturne-23.5.0.mp4)

*Cliquez sur l'image pour voir la vidéo (34 s).*

## Nouvelles fonctionnalités et innovations

### URL courtes et QR codes

* **Un lien court qui s'ouvre sans connexion.** Saturne fournit des URL courtes de la forme `https://mon-dolibarr.fr/custom/saturne/s/?k7m2xp` : la personne qui scanne le QR code ou clique dans le SMS arrive directement sur la page visée, sans écran de connexion. Environ 46 caractères au lieu de 140, donc un QR code plus petit et plus lisible sur une étiquette.
* **Utilisé par DigiQuali et EasyURL.** DigiQuali s'en sert pour le lien de l'historique public des contrôles d'un lot et pour ses planches de QR codes ; EasyURL propose une nouvelle méthode « Saturne » qui raccourcit les liens sans serveur YOURLS.
* **Des codes faciles à recopier.** 6 caractères, sans les lettres et chiffres qui se confondent (pas de i, l, o, 0 ni 1).

<img src="https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.5.0/scan.png" alt="Page publique ouverte par une URL courte, sur un téléphone" width="300">

### Gestion des redirections

* **Toutes les URL courtes au même endroit.** L'onglet *Configuration > Saturne > Redirections* liste chaque lien avec sa destination et son QR code, prêt à imprimer. La liste est paginée et se recherche par code ou par destination : elle reste utilisable avec des milliers de liens.
* **Une racine encore plus courte, si votre serveur le permet.** Une ligne dans le `.htaccess` de Dolibarr, ou un domaine court dédié, et les liens deviennent `https://mon-dolibarr.fr/s?k7m2xp`. Sans réglage, la racine par défaut fonctionne partout. Les liens déjà créés restent valables.
* **Pas de QR code cassé par erreur.** Supprimer une URL courte demande une confirmation, qui rappelle qu'un QR déjà imprimé avec elle ne fonctionnera plus.

![Onglet Redirections : racine courte et liste des URL courtes avec leur QR code](https://raw.githubusercontent.com/nicolas-eoxia/Saturne/assets/release-23.5.0/admin.png)

## Améliorations & corrections

### URL courtes

* **Les liens survivent à un changement de domaine.** La destination d'une URL courte vers votre Dolibarr est enregistrée relativement à son adresse : une migration d'hébergement ou un changement de nom de domaine ne casse plus les QR codes imprimés.
* **Un lien mort reste clair.** Un code inconnu ou supprimé affiche une page lisible sur téléphone, « Ce lien n'est pas ou plus valide », dans la langue du navigateur.
* **L'ancienne redirection est remplacée.** Elle passait par l'accueil de Dolibarr, donc par l'écran de connexion, et ne fonctionnait que si un `.htaccess` existait déjà au-dessus de `htdocs`.

### Listes

* **Le filtre de l'en-tête figé reste propre.** Dans une liste générique avec en-tête figé, la liste déroulante d'origine d'un filtre n'apparaît plus sous le sélecteur amélioré.

### Documents

* **Plus d'avertissement PHP à la création d'un document sans objet parent** (export de l'outil EasyURL, par exemple) : trois lignes de moins dans le journal d'erreurs à chaque export.

### Projets

* **Plus de trace de débogage dans le journal** lors de la modification en ligne d'un projet.

## Testez les nouveautés maintenant

**Allez tester les URL courtes et les QR codes sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.4.1](https://github.com/Evarisk/Saturne/compare/23.4.1...23.5.0) et 23.5.0

* #1741 [Hook] fix: remove debug error_log left in project inline edit hook [`#1742`](https://github.com/Evarisk/Saturne/pull/1742)
* #1745 [Redirection] feat: URL courte publique pour les QR codes, sans connexion ni .htaccess [`#1746`](https://github.com/Evarisk/Saturne/pull/1746)
* #1747 [Documents] fix: warnings PHP 8 dans SaturneDocuments::create() sans objet parent [`#1748`](https://github.com/Evarisk/Saturne/pull/1748)
* #1749 [List] fix: keep the select hidden by select2 inside sticky header cells [`#1750`](https://github.com/Evarisk/Saturne/pull/1750)
* #1751 [Redirection] feat: URL courtes plus courtes, code de 6 caractères non ambigus, racine configurable [`#1752`](https://github.com/Evarisk/Saturne/pull/1752)
* #1753 [Redirection] feat: admin paginée avec recherche et confirmation avant suppression ; 404 lisible sur mobile [`#1754`](https://github.com/Evarisk/Saturne/pull/1754)
* #1755 [Redirection] fix: destination enregistrée relativement à Dolibarr, les QR survivent à un changement de domaine [`#1756`](https://github.com/Evarisk/Saturne/pull/1756)
