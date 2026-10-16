# [Saturne] [23.4.1] - Tableaux de bord sans avertissement

Description : Version corrective. Un camembert du tableau de bord réduit à une seule part ne remplit plus le journal d'erreurs.

## Améliorations & corrections

### Tableau de bord

* **Plus d'avertissement PHP sur un camembert à une seule part.** Un graphique à deux séries qui n'avait plus qu'une part — par exemple « Temps global du mois » de DoliSIRH pour un salarié sans temps saisi dans le mois — recevait une seule couleur pour deux séries : six avertissements dans le journal à chaque affichage. Les couleurs manquantes sont désormais complétées. Le correctif vaut pour tous les modules qui s'appuient sur le tableau de bord de Saturne.

## Testez les nouveautés maintenant

**Allez tester les tableaux de bord sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.4.0](https://github.com/Evarisk/Saturne/compare/23.4.0...23.4.1) et 23.4.1

* #1734 [Dashboard] fix: une couleur par série pour un camembert réduit à une seule part
