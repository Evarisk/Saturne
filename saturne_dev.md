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

- <b>#NuméroIssue [Object/Element] add/fix: commit message</b>
- Exemple 1 : #100 [Signature] add: signature update action
- Exemple 2 : #101 [Lib] fix: wrong method call
# <img src="https://github.com/Evarisk/Saturne/blob/develop/img/example_of_commit.png?raw=true" width="1000"/>

## CSS OU JS

Pour simplifier l'utilisation et la compréhension du CSS/JS, nous avons utilisé un minifier avec la librairie npm Gulpfile.
Par conséquent, il ne faut pas oublier de lancer le terminal et d'exécuter la commande npm i (si c'est la première utilisation) ou npm start.
- Exemple : C:\wamp64\www\dolibarr\htdocs\custom\saturne> npm start

## Utilisation du Framework
### Objets Générique
#### Objet (SaturneObject)

Pour implémenter un objet sur un module il faut se référer à la classe SaturneObject.
Cette dernière dispose d'un CRUD générique étendu de CommonObject + les fonctions utilitaires
- getNomUrl
- fetchAll
- etc

#### Documents (SaturneDocuments)

Pour implémenter un objet document sur un module il faut se référer à la classe SaturneDocuments.
Cette dernière dispose d'un CRUD générique étendu de CommonObject + les fonctions utilitaires suivantes :
- generateDocument

#### Signature (SaturneSignature)

Pour implémenter un objet signature sur un module il faut se référer à la classe SaturneSignature.
Cette dernière dispose d'un CRUD générique étendu de CommonObject + les fonctions utilitaires suivantes :
- setSignatory
- fetchSignatory
- fetchSignatories
- checkSignatoriesSignatures
- deleteSignatoriesSignatures
- deletePreviousSignatories

#### Schedules (SaturneSchedules)

Pour implémenter un objet horaires sur un module il faut se référer à la classe SaturneSchedules.
Cette dernière dispose d'un CRUD générique étendu de CommonObject

#### Dashboard (SaturneDashboard)

Pour implémenter un objet tableau de bord sur un module il faut se référer à la classe SaturneDashboard.
Cette dernière dispose des fonctions utilitaires suivantes :
- load_dashboard - Charger les infos du tableau de bord
- show_dashboard - Afficher le tableau de bord
