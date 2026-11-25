# [Saturne] [23.9.2] - Page publique de signature plus sûre

Description : la page publique de signature, commune à tous les modules qui font signer par lien (sessions de formation, plans de prévention, contrôles…), applique désormais les mêmes règles que son affichage. **Mise à jour recommandée pour toutes les instances.**

## Améliorations & corrections

### Page publique de signature : contrôles renforcés

* Une signature déjà enregistrée ne peut plus être remplacée depuis le lien de signature. Pour faire signer à nouveau, la remise en attente depuis la fiche (retour en brouillon) fonctionne comme avant.
* Seul un signataire qui doit encore signer peut signer, et seulement sur un objet validé.
* Seule une signature tracée sur la page est acceptée.
* La page n'ouvre que l'objet du lien de signature et ne propose que les documents prévus pour cet objet.
* Rien ne se fait quand l'interface publique est désactivée.
* Les usages prévus ne changent pas : signature par lien, signature sur place depuis le tableau des participants, téléchargement du document signé par le signataire.

### Page publique de signature : affichage

* La page ne laisse plus d'avertissements dans le journal PHP quand l'interface publique est désactivée.

## Comparaison des versions [23.9.1](https://github.com/Evarisk/Saturne/compare/23.9.1...23.9.2) et 23.9.2

* [Signature] fix: contrôles de la page publique de signature [`64d23788`](https://github.com/Evarisk/Saturne/commit/64d23788)
* [Signature] fix: page publique de signature sans avertissement quand l'interface est fermée [`7f0c6c66`](https://github.com/Evarisk/Saturne/commit/7f0c6c66)
* [Signature] fix: statut validé de l'objet lu sans supposer sa constante [`8b477e8c`](https://github.com/Evarisk/Saturne/commit/8b477e8c)
