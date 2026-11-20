# [Saturne] [23.9.0] - Opportunités dans l'ordre du dictionnaire - Vignettes et tâches sans avertissement

Description : Les statuts d'opportunité suivent enfin l'ordre et les libellés de votre dictionnaire. À la validation, tous les documents d'un objet rejoignent le dossier de sa référence définitive. Les modèles ODT traduisent leurs intitulés, et les vignettes PNG comme l'en-tête des tâches ne laissent plus d'avertissement dans les journaux.

## Nouvelles fonctionnalités et innovations

### Médias : types de fichiers proposés au choix

* Une page peut désormais choisir les types de fichiers que propose son bouton d'ajout de documents, par exemple pour accepter aussi les vidéos. Par défaut, rien ne change : documents bureautiques, PDF, archives et e-mails, sans l'appareil photo du téléphone.

## Améliorations & corrections

### Listes

* Les statuts d'opportunité de la liste des projets, de sa colonne et de son filtre, suivent l'**ordre du dictionnaire**, et non plus l'ordre de création.
* Un statut **renommé dans le dictionnaire**, « Qualification » ou « Négociation » par exemple, garde son nouveau nom partout : listes et fiches des projets, widgets, statistiques.
* Le filtre ne propose que les statuts actifs. Un projet qui porte encore un statut désactivé le garde dans sa liste déroulante.

### Documents

* À la validation d'un objet, son dossier provisoire `(PROV…)` est bien renommé à sa référence définitive. Les fichiers joints, les documents générés en brouillon (feuille d'émargement d'une session DoliMeet comprise) et l'index des documents suivent. Rien n'est écrasé.
* Les modèles ODT remplissent leurs balises de traduction `{lang_Cle}` et `{__(Cle)__}`, comme les modèles du cœur de Dolibarr : un intitulé s'affiche dans la langue du document au lieu de la balise brute.
* Un module mis à jour sans réactivation ne s'arrête plus sur une page blanche à la génération d'un document quand le réglage de numérotation du modèle manque : la numérotation standard prend le relais.
* L'événement de validation d'un certificat porte sa référence définitive, et non plus la provisoire.

### Tâches

* L'en-tête d'une tâche affiche ses libellés de statut et ne laisse plus d'avertissement PHP.

### Médias

* Les vignettes PNG entrelacées ou à métadonnées malformées s'affichent sans laisser d'avertissement « libpng warning » dans les journaux. Un PNG réellement illisible reste signalé.

## Comparaison des versions [23.8.0](https://github.com/Evarisk/Saturne/compare/23.8.0...23.9.0) et 23.9.0

* [#1920] [Object] fix: dossiers des modèles déclarés pour l'objet (llx_document_model) suivis aussi [`39461dd5`](https://github.com/Evarisk/Saturne/commit/39461dd5)
* [#1923] [Documents] fix: balises de traduction {lang_Cle} et {__(Cle)__} des modèles ODT remplies [`eeb4ec1b`](https://github.com/Evarisk/Saturne/commit/eeb4ec1b)
* [#1920] [Object] fix: validate() fait suivre aussi les documents générés en brouillon (<type>document/(PROVn)) [`87d8d2ea`](https://github.com/Evarisk/Saturne/commit/87d8d2ea)
* [#1924] [Certificate] fix: événement de validation d'un certificat libellé avec la référence définitive [`13164fc7`](https://github.com/Evarisk/Saturne/commit/13164fc7)
* [#1921] [Documents] fix: repli sur mod_<type>_standard quand la constante de numérotation d'un modèle manque [`d625c593`](https://github.com/Evarisk/Saturne/commit/d625c593)
* [#1928] [List] fix: opportunity statuses in the order and with the labels of the dictionary [`d83244f8`](https://github.com/Evarisk/Saturne/commit/d83244f8)
* [#1918] [Object] fix: validate() déplace le dossier (PROVn) de l'objet vers sa référence définitive [`51b0f847`](https://github.com/Evarisk/Saturne/commit/51b0f847)
* [#1916] [Task] fix: en-tête d'une tâche sans avertissement (libellés de statut, projet) [`ac0cc751`](https://github.com/Evarisk/Saturne/commit/ac0cc751)
* [#1904] [Medias] fix: mute every libpng warning of a readable PNG thumbnail [`0d95b65f`](https://github.com/Evarisk/Saturne/commit/0d95b65f)
* [#1914] [Medias] add: file_accept option for the media block document picker [`e20cebdd`](https://github.com/Evarisk/Saturne/commit/e20cebdd)
* [#1904] [Medias] fix: mute the libpng warning of interlaced PNG thumbnails [`e5b5176d`](https://github.com/Evarisk/Saturne/commit/e5b5176d)
