cd /c/Project/jtechel
git add -A

if ! git diff --cached --quiet; then
  git commit -m "Auto-commit at $(date '+%Y-%m-%d %H:%M:%S')"
fi
