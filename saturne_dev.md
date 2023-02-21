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
### Bonne pratique à respecter pour ajouter une fonction.

- Création d'un fichier fonction pour objet
- Les fonctions doivent être appelées saturne_name_of_function
- Ajouter la documentation de la fonction

## Convention de commit d'Evarisk

Pour faire un commit sur les repositories d'Evarisk il faut respecter la convention suivante :

#NuméroIssue [Object/Element] add/fix: commit message
- Ex: #100 [Signature] add: signature update action
- Ex: #101 [Lib] fix: wrong method call for use this function

## CSS OU JS

Pour simplifier l'utilisation et la compréhension du CSS/JS on a utilisé un minifier avec la libraire npm gulpfile.
Par conséquent, il ne faut pas oublier de lancer le terminal et d'éxécuter la commande npm i (si c'est la première utilisation) ou npm start.
- Ex: C:\wamp64\www\dolibarr-16.0.3\htdocs\custom\saturne> npm start
