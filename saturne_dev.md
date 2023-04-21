# <img src="https://github.com/Evarisk/Saturne/blob/1.0.0/img/saturne_color.png?raw=true" width="64" /> Saturne Dev

## Convention de nommage
### La convention de nommage est basée sur PHP PSR12

- Class             <span style="color:red">PascalCase</span>
- Variables         <span style="color:#FFE436">camelCase</span>
- Classes functions <span style="color:#FFE436">camelCase</span>
- Lib Functions     <span style="color:#0da2ff">snake_case</span>
- Object attributes <span style="color:#0da2ff">snake_case</span>
- Actions           <span style="color:#0da2ff">snake_case</span>
- Filenames         <span style="color:#0da2ff">snake_case</span>

## Fonctions de la librairie
### Bonnes pratiques à respecter pour ajouter une fonction.

- Création d'un fichier fonction pour l'objet
- Les fonctions doivent être appelées saturne_name_of_function
- Ajouter la documentation de la fonction

## Convention de commit d'Evarisk

Pour faire un commit sur les repositories d'Evarisk il faut respecter la convention suivante :

#NuméroIssue [Object/Element] add/fix: commit message
- Ex: #100 [Signature] add: signature update action
- Ex: #101 [Lib] fix: wrong method call

## CSS OU JS

Pour simplifier l'utilisation et la compréhension du CSS/JS, nous avons utilisé un minifier avec la libraire npm gulpfile.
Par conséquent, il ne faut pas oublier de lancer le terminal et d'exécuter la commande npm i (si c'est la première utilisation) ou npm start.
- Ex: C:\wamp64\www\dolibarr-16.0.3\htdocs\custom\saturne> npm start

## Utilisation du Framework
### Objects Générique
#### Object (SaturneObject)

Pour implémenter un object sur un module il faut se référer à la classe SaturneObject.
Cette dernière dispose d'un CRUD générique étendu de CommonObject + des fonctions utilitaires
- getNomUrl
- FetchAll
- etc

#### Documents (SaturneDocuments)

Pour implémenter un object documents sur un module il faut se référer à la classe SaturneDocuments.
Cette dernière dispose d'un CRUD générique étendu de CommonObject + des fonctions utilitaires suivantes :
- GenerateDocument

#### Signature (SaturneSignature)

Pour implémenter un object signature sur un module il faut se référer à la classe SaturneSignature.
Cette dernière dispose d'un CRUD générique étendu de CommonObject + des fonctions utilitaires suivantes :
- setSignatory
- fetchSignatory
- fetchSignatories
- checkSignatoriesSignatures
- deleteSignatoriesSignatures
- deletePreviousSignatories

A venir: (builder d'objets) + exemple

#### Schedules (SaturneSchedules)

Pour implémenter un object horaires sur un module il faut se référer à la classe SaturneSchedules.
Cette dernière dispose d'un CRUD générique étendu de CommonObject

A venir: (builder d'objets) + exemple

#### Dashboard (SaturneDashboard)

Pour implémenter un object tableau de bord sur un module il faut se référer à la classe SaturneDashboard.
Cette dernière dispose des fonctions utilitaires suivantes :
- load_dashboard - Charger les infos du tableau de board
- show_dashboard - Afficher le tableau de board

A venir: (builder d'objets) + exemple