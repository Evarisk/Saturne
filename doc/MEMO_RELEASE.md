# Memo — Processus de release

## Prérequis (déjà en place)

- `Evarisk/Saturne` — `.github/workflows/release.yml` avec `workflow_call`
- Chaque module enfant — `.github/workflows/release.yml` minimal qui appelle Saturne
- `RELEASE_NOTES_TEMPLATE.md` à la racine de chaque repo (ou `~/.config/claude/release-template.md` en local)
- `RELEASE_NOTES.md` dans `.gitattributes` avec `export-ignore`

---

## Workflow release (à répéter pour chaque version)

### 1. Générer les release notes avec Claude Code

Depuis le repo du module, sur la branche `develop` :

```bash
# Pour un module avec RELEASE_NOTES_TEMPLATE.md dans le repo (ex: Saturne)
claude "Generate release notes for version X.X.X based on git log since tag X.X.X. Use RELEASE_NOTES_TEMPLATE.md as format reference. Write in French, group by functional category, add screenshot placeholders for visual features. Save to RELEASE_NOTES.md"

# Pour un module enfant sans template dans le repo (ex: Digirisk, ReedCRM)
claude "Generate release notes for version X.X.X based on git log since tag X.X.X. Use ~/.config/claude/release-template.md as format reference. Write in French, group by functional category, add screenshot placeholders for visual features. Save to RELEASE_NOTES.md"
```

### 2. Relire et ajuster

- Vérifier les catégories fonctionnelles
- Remplacer les `<!-- 📸 Ajouter une screenshot ici -->` par de vraies captures, **anonymisées** :
  la base locale contient de vrais noms (personnes, tiers, entités). Les images s'hébergent sur une
  branche `assets/release-X.X.X` du fork et se citent par leur URL `raw.githubusercontent.com`,
  jamais dans le dépôt : le module part en zip chez les clients
- Corriger les liens GitHub si nécessaire (vérifier org/repo dans les URLs)
- Terminer par l'appel à tester sur la démo (section « Testez les nouveautés maintenant » du modèle)

### 3. Vidéo de présentation (grosses nouveautés seulement)

La vidéo (30 à 60 s) est **réservée aux grosses nouveautés** : c'est l'étape la plus lourde, et une
release ordinaire doit pouvoir sortir toutes les une à deux semaines. Sans vidéo, retirer la section
« La nouveauté en vidéo » des notes et passer à l'étape 4. Exemple de vidéo : DigiBoard 23.2.0,
https://youtu.be/-5ckexXShQo.

Tout se fait dans le dépôt privé **`Evarisk/release-video`** (Remotion), dont le README détaille la
recette :

1. **Captures** — un jeu `scripts/shots/<module>-<X.X.X>.mjs` : les pages à montrer et les zones à
   mettre en avant. Les écrans sont anonymisés avant la prise de vue et les zones relevées
   automatiquement : la caméra et les cadres de la vidéo s'y posent sans réglage à la main.
2. **Contrôle d'anonymisation** — `node scripts/verify-anon.mjs <jeu>` doit être vert, contexte de
   chaque alerte relu. **Aucune vidéo ne sort sans ce contrôle.**
3. **Scènes** — un fichier `src/release/releases/<module>-<X.X.X>.tsx` assemble les scènes du kit
   (titre, une scène par nouveauté, améliorations, appel à la démo). Reprendre les textes de
   `RELEASE_NOTES.md`.
4. **Rendu** — images fixes de contrôle (`scripts/stills.mjs`), puis la vidéo et sa vignette
   (composition `Poster-…`).
5. **Mise en ligne** — sur la chaîne YouTube Evarisk.
6. **Notes de version** — ajouter la vignette, cliquable vers la vidéo, en tête de `RELEASE_NOTES.md`
   (section « La nouveauté en vidéo » du modèle).

Le lien doit être dans `RELEASE_NOTES.md` **avant le tag** : le workflow publie le contenu du fichier,
et une modification faite seulement sur la page de release est écrasée à la publication.

La base de dev est souvent partagée entre plusieurs instances : un module activé pour les captures
doit être désactivé après (`node scripts/module.mjs reset <modNom>`).

### 4. Commiter sur develop

```bash
git add RELEASE_NOTES.md
git commit -m "#ISSUE [Mod] chore: release notes X.X.X"
git push
```

### 5. Merger develop → main

```bash
git checkout main
git merge develop
git push
```

### 6. Tagger et pousser

```bash
git tag X.X.X
git push --tags
```

### 7. Vérifier la CI

Aller sur `github.com/ORG/REPO` → **Actions** → workflow `Release` doit être vert.

---

## Résultat attendu

- Release GitHub créée avec le contenu de `RELEASE_NOTES.md`
- Zip `module_NOM-X.X.X.zip` attaché à la release
- Dossier racine du zip : `nom_du_module/`
- Pour une grosse nouveauté : vignette de la vidéo en tête des notes, qui ouvre la vidéo sur YouTube
- Appel à tester sur la démo en fin de notes

---

## Modules configurés

| Module | Repo | Organisation |
|--------|------|-------------|
| Saturne | Evarisk/Saturne | Evarisk |
| Digirisk | Evarisk/Digirisk | Evarisk |
| ReedCRM | Eoxia/reedcrm | Eoxia |

---

## En cas de problème

- **Tag sur la mauvaise branche** — le workflow échoue avec `Tag must be based on main`
- **`RELEASE_NOTES.md` absent** — la CI génère les notes automatiquement depuis les commits GitHub
- **Workflow enfant ne trouve pas Saturne** — vérifier que `Evarisk/Saturne` est public
