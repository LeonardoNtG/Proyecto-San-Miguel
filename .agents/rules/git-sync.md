# Git Synchronization Protocol for Production Branch

## Context & Team Workflow
- Multiple developers work concurrently on the `Production` branch in this repository.
- Remote repository: `https://github.com/LeonardoNtG/Proyecto-San-Miguel` (`origin/Production`).

## Mandatory Protocol
1. **Always sync before editing**: Before starting work on tasks or making code edits, check for remote updates using:
   ```bash
   git fetch origin Production
   ```
2. **Pull incoming changes**: If `origin/Production` is ahead of local `HEAD`, pull the latest commits using:
   ```bash
   git pull --rebase --autostash origin Production
   ```
3. **Handle conflicts proactively**: Ensure working copy changes are preserved cleanly with `--autostash` or stash management to prevent divergent branch history and file drift.
