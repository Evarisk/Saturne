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
- Remplacer les `<!-- 📸 Ajouter une screenshot ici -->` par de vraies captures
- Corriger les liens GitHub si nécessaire (vérifier org/repo dans les URLs)

### 3. Commiter sur develop

```bash
git add RELEASE_NOTES.md
git commit -m "#ISSUE [Mod] chore: release notes X.X.X"
git push
```

### 4. Merger develop → main

```bash
git checkout main
git merge develop
git push
```

### 5. Tagger et pousser

```bash
git tag X.X.X
git push --tags
```

### 6. Vérifier la CI

Aller sur `github.com/ORG/REPO` → **Actions** → workflow `Release` doit être vert.

---

## Résultat attendu

- Release GitHub créée avec le contenu de `RELEASE_NOTES.md`
- Zip `module_NOM-X.X.X.zip` attaché à la release
- Dossier racine du zip : `nom_du_module/`

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
