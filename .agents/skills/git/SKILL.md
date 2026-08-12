---
name: git
description: "Use this skill for all Git operations, repository management, version control workflows, branch management, merging, rebasing, committing, pushing, pulling, resolving merge conflicts, stashing, tag management, and troubleshooting Git errors. Triggers whenever performing git commands, resolving git conflicts, writing commit messages (e.g. Conventional Commits), setting up upstream branches, managing PRs, or handling non-fast-forward/detached HEAD states."
license: MIT
---

# Git Workflow & Best Practices

This skill provides comprehensive instructions for Git operations, commit standards, branch strategies, conflict resolution, and safety guidelines within this repository.

## 1. Commit Standards & Messaging

### Conventional Commits
All commit messages MUST follow the Conventional Commits specification:
`<type>[optional scope]: <description>`

Common types:
- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Formatting or whitespace changes with no code execution impact
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `perf`: A code change that improves performance
- `test`: Adding missing tests or correcting existing tests
- `chore`: Changes to build, tooling, dependencies, or configuration

Examples:
- `feat(auth): implement passkey registration flow`
- `fix(printing): resolve paper size calculation overflow`
- `chore(deps): update composer lock file`

### Guidelines for Commits
- Keep commits atomic (one logical change per commit).
- Write in present tense, imperative mood in descriptions ("add feature" not "added feature").
- Do not commit secrets, `.env` files, temporary artifacts, or local build caches.
- Always check `git status` before committing to ensure no unwanted files are staged.

## 2. Branching & Workflow Strategy

- **`main`**: The primary branch representing deployable code.
- **Feature & Fix Branches**: Create short-lived branches for isolated work:
  - `feature/<short-description>`
  - `fix/<short-description>`
  - `refactor/<short-description>`

### Creating and Switching Branches
```bash
git checkout -b feature/my-feature
```

## 3. Pulling, Rebasing, & Syncing Remote

### Avoid Unnecessary Merge Commits
- Prefer rebasing local commits onto the target remote branch to maintain a clean linear history:
  ```bash
  git fetch origin
  git rebase origin/main
  # or
  git pull origin main --rebase
  ```
- If remote has changed before pushing:
  ```bash
  git fetch origin
  git rebase origin/main
  git push origin <branch-name>
  ```

### Setting Upstream Tracking
When pushing a new branch for the first time, set the upstream tracking branch:
```bash
git push -u origin <branch-name>
```

## 4. Resolving Merge & Rebase Conflicts

1. Run `git status` to identify conflicting files.
2. Open conflicting files and search for conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`).
3. Resolve conflicts carefully by combining or selecting valid code.
4. Test the code after resolving conflict markers.
5. Stage resolved files: `git add <resolved-file>`.
6. Continue rebase/merge:
   - For rebase: `git rebase --continue`
   - For merge: `git commit`
7. If a rebase becomes tangled, abort safely: `git rebase --abort`.

## 5. Stashing Work

Use stashing when switching branches with uncommitted work:
- Stash changes: `git stash push -m "descriptive message"`
- List stashes: `git stash list`
- Apply and drop latest stash: `git stash pop`
- Apply specific stash: `git stash apply stash@{N}`

## 6. Safety & Troubleshooting

- **Never Force Push (`git push -f`) to `main`** unless explicitly instructed by the repository maintainer and verified safe. Use `--force-with-lease` if force push is required on personal feature branches.
- **Detached HEAD**: If you find yourself in a detached HEAD state, create a temporary branch before switching: `git branch temp-work`.
- **Ignore Rules**: Ensure `.gitignore` properly ignores vendor directories, node_modules, `.env`, `.phpunit.cache`, temporary logs, and OS artifacts (`.DS_Store`).
