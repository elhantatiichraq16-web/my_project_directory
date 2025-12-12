#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/elhantatiichraq16-web/my_project_directory.git"
BRANCH="main"
BACKUP_BRANCH="backup-before-restore-$(date +%Y%m%d%H%M%S)"

echo "==> Vérification du dépôt git"
if [ ! -d .git ]; then
  echo "Erreur : ce répertoire ne semble pas être un dépôt git."
  echo "Clonez d'abord le dépôt et placez-vous dans le répertoire :"
  echo "  git clone $REPO_URL"
  echo "  cd $(basename "$REPO_URL" .git)"
  exit 1
fi

echo "==> Récupération des refs distantes"
git fetch origin

echo "==> Création d'une branche de sauvegarde locale et push vers origin: $BACKUP_BRANCH"
# Try to base the backup on the remote branch if it exists
if git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
  git checkout -b "$BACKUP_BRANCH" "origin/$BRANCH"
else
  git checkout -b "$BACKUP_BRANCH"
fi
git push origin "$BACKUP_BRANCH"

echo "==> Recherche du commit le plus récent avant '1 month ago' sur origin/$BRANCH"
TARGET_COMMIT=$(git rev-list -n 1 --before="1 month ago" "origin/$BRANCH" || true)

if [ -z "$TARGET_COMMIT" ]; then
  echo "Aucun commit trouvé avant '1 month ago' sur origin/$BRANCH. Abort."
  exit 1
fi

echo "Commit cible trouvé : $TARGET_COMMIT"
git show --stat --oneline "$TARGET_COMMIT"

read -r -p "Est-ce bien la version désirée ? (y/N) " confirm
if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
  echo "Annulation par l'utilisateur. Rien n'a été modifié."
  exit 0
fi

echo "==> Passage sur la branche $BRANCH et synchronisation"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Application de git revert --no-commit $TARGET_COMMIT..HEAD"
# Utilise --no-commit pour regrouper les reverts en un seul commit
set +e
git revert --no-commit "$TARGET_COMMIT..HEAD"
REVERT_EXIT=$?
set -e

# Détecte conflits (fichiers en état non résolu)
if [ -n "$(git ls-files -u)" ]; then
  echo
  echo "Il y a des conflits pendant le revert. Vous devez les résoudre manuellement."
  echo "Étapes recommandées :"
  echo "  1) Résoudre les fichiers en conflit (ouvrir, corriger, puis git add <fichier>)"
  echo "  2) Faire un commit final : git commit -m \"Revert to state at $TARGET_COMMIT (restore ~1 month ago)\""
  echo "  3) Pousser : git push origin $BRANCH"
  echo
  echo "Le script s'arrête maintenant pour vous laisser résoudre les conflits."
  exit 2
fi

if [ $REVERT_EXIT -ne 0 ]; then
  echo "git revert a retourné un code non nul mais sans conflits détectés. Veuillez vérifier l'état:"
  git status --porcelain --branch
  echo "Si tout est en ordre, faites :"

  echo "  git commit -m \"Revert to state at $TARGET_COMMIT (restore ~1 month ago)\""
  echo "  git push origin $BRANCH"
  exit 3
fi

echo "==> Aucun conflit détecté. Création du commit de revert unique."
git commit -m "Revert to state at $TARGET_COMMIT (restore ~1 month ago)"

echo "==> Poussée des changements sur origin/$BRANCH"
git push origin "$BRANCH"

echo "Terminé : la branche $BRANCH a été ramenée (via revert) à l'état du commit $TARGET_COMMIT."
echo "Une branche de sauvegarde a été créée : $BACKUP_BRANCH (poussée sur origin)."
