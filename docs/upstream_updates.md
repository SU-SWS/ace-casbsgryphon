# Upstream Updates

This document describes how to pull upstream changes from the [Stanford Profile](https://github.com/SU-SWS/stanford_profile) into the CASBS Gryphon project. The profile is managed as a git subtree under `docroot/profiles/custom/stanford_profile`.

## Prerequisites

- A working local environment (see the main [README](../README.md) for setup)
- A clean git working tree (no uncommitted changes)
- Acquia Cloud API credentials configured in `drush/local.drush.yml`

## Step-by-step process

### 1. Start from a fresh state

Pull the latest from the `12.x` branch:

```bash
git checkout 12.x
git pull origin 12.x
```

### 2. Create a feature branch

```bash
git checkout -b upstream-update/<tag>
```

Replace `<tag>` with the profile tag you're pulling in (e.g., `12.2.1`).

### 3. Make sure your working tree is clean

`git subtree pull` will refuse to run if there are uncommitted changes. Stash or commit any local work first:

```bash
git status
# If there are changes:
git stash --include-untracked
```

### 4. Update the `pull-profile` script tag

In `composer.json`, find the `pull-profile` script and update the tag reference to the desired version:

```json
"git subtree pull --prefix=docroot/profiles/custom/stanford_profile --squash stanford_profile tags/<new-tag> || true",
```

You can find available tags at https://github.com/SU-SWS/stanford_profile/tags

### 5. Run the pull-profile script

```bash
lando composer pull-profile
```

This script does the following:

1. Removes the existing profile directory (`rm -rf`)
2. Restores it from the current git index (`git checkout`)
3. Adds the `stanford_profile` remote (if not already present)
4. Pulls the specified tag as a squashed subtree merge
5. Restores the CASBS-specific overrides of `composer.json` and `stanford_profile.info.yml` (these files are maintained locally and should not be overwritten by upstream)

You will be prompted to enter a merge commit message. The default message is fine.

### 6. Review the changes

Before committing anything further, carefully review what changed. In VS Code, the Source Control panel provides a helpful tree view of all modified files.

**Pay particular attention to:**

- **`config/sync/split/casbs/`** -- This directory contains CASBS-specific config split overrides (custom content types like `casbs_book`, CASBS-specific person fields, JSON API config, role permissions, etc.). Any upstream changes that touch the base configs these patches apply to could cause conflicts or unexpected behavior.
- **User roles and permissions** -- Check for changes to `user.role.*.yml` files. CASBS has custom permission patches (e.g., `site_editor`, `layout_builder_user`, `decoupled_site_users`).
- **JSON API configuration** -- CASBS uses JSON API to sync content from an external FileMaker database. Look for changes to `jsonapi.settings.yml`, `jsonapi_extras.*` configs, and the `stanford_decoupled` module.
- **Migration configs** -- Changes to `migrate_plus.migration.*` files could affect event, person, or course imports.
- **Theme changes** -- Review changes in `themes/stanford_basic/` to make sure CASBS theme customizations aren't lost (see commit history for prior theme restoration work).

### 7. Update dependencies

```bash
lando composer update
```

This pulls in any new or updated packages required by the updated profile.

### 8. Deploy and test locally

```bash
lando drush deploy
```

This runs database updates, imports configuration, and clears caches. Check your local site for:

- The site loads without errors
- SAML login still works
- JSON API endpoints respond correctly
- Content types and views render properly
- The FileMaker data import still functions (if applicable)

If you stashed changes earlier, restore them:

```bash
git stash pop
```

You will likely want to run a `lando drush @default.local cex` to export any configs which have been updated via the dependency update process.

### 9. Commit and push

Once everything looks good locally, commit the changes and push the branch:

```bash
git add -A
git commit -m "Updated stanford_profile to <tag>"
git push origin upstream-update/<tag>
```

Then open a pull request against `12.x` for review.

## Troubleshooting

### `working tree has modifications. Cannot add.`

The `git subtree pull` command requires a clean working tree. Stash your changes first:

```bash
git stash --include-untracked
lando composer pull-profile
git stash pop
```

### Composer process timeout

If `lando composer` commands time out, make sure `process-timeout` is set to `0` in `composer.json`:

```json
"config": {
    "process-timeout": 0
}
```

### Merge conflicts in the subtree

If the subtree pull produces merge conflicts, resolve them manually. The CASBS-specific files that are always kept local (`composer.json`, `stanford_profile.info.yml`) are handled automatically by the script, but other conflicts will need manual resolution.

### Config import failures after deploy

If `lando drush deploy` fails during config import, check for:

- Removed or renamed modules that are still referenced in config
- Schema changes in updated modules that require manual migration
- Config split patches in `split/casbs/` that no longer apply cleanly to the updated base config

You can debug config issues with:

```bash
lando drush config:status
lando drush config:import --diff
```
