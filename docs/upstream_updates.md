# Upstream Updates

How to pull [stanford_profile](https://github.com/SU-SWS/stanford_profile)
upstream releases into CASBS Gryphon. The profile is a **git subtree** at
`docroot/profiles/custom/stanford_profile`, carrying some CASBS changes. This
runbook reflects the 12.2.4 to 13.0.12 upgrade (SWSDEVOPS-347).

- [1. Prerequisites](#1-prerequisites)
- [2. Pick the target release](#2-pick-the-target-release)
- [3. Path A: standard minor/patch update](#3-path-a-standard-minorpatch-update)
- [4. Path B: major update or stale subtree metadata](#4-path-b-major-update-or-stale-subtree-metadata)
- [5. Root composer and dependencies](#5-root-composer-and-dependencies)
- [6. Database and config](#6-database-and-config)
- [7. Review checklist](#7-review-checklist)
- [8. Final verification](#8-final-verification)
- [9. PR and merge policy](#9-pr-and-merge-policy)
- [10. Troubleshooting](#10-troubleshooting)
- [Appendix: known CASBS drift inside the subtree](#appendix-known-casbs-drift-inside-the-subtree)

## 1. Prerequisites

- A working Lando site (see the [README](../README.md)). Check that the `casbs`
  containers run from **this** checkout:
  `docker ps --format '{{.Names}} {{.Label "io.lando.src"}}'`. Another checkout,
  `~/code/casbs_profile_updates/ace-casbsgryphon`, uses the same Lando name,
  `casbs`.
- `gh` CLI authenticated. In some sandboxes SSH to GitHub fails. HTTPS and
  `gh api` work, and every command below uses them.
- A local DB that matches prod (`lando composer run-script --timeout=0 sync-casbs`
  overwrites your local DB and files from **prod**, so be sure before you run it).
- A feature branch off `12.x`:

  ```bash
  git checkout 12.x && git pull origin 12.x
  git checkout -b SWSDEVOPS-XXX   # or upstream-update/<tag>
  ```

## 2. Pick the target release

CASBS follows **acsf-cardinalsites** releases. Each cardinalsites release locks
one `stanford_profile` tag and one `stanford_profile_helper` version. Choose the
cardinalsites tag first, then read the profile tag from its lock file:

```bash
CS_OLD=<previous cardinalsites tag>; CS_NEW=<target cardinalsites tag>

# Which stanford_profile / stanford_profile_helper does the target lock?
# (composer.lock is > 1 MB, so the contents API returns no base64 `content`;
# ask for the raw file and filter it with gh's built-in --jq.)
gh api -H "Accept: application/vnd.github.raw+json" \
  "repos/SU-SWS/acsf-cardinalsites/contents/composer.lock?ref=$CS_NEW" \
  --jq '.packages[] | select(.name|test("stanford_profile")) | "\(.name) \(.version)"'

# What changed at cardinalsites root level (composer.json, patches/, hooks/)?
gh api "repos/SU-SWS/acsf-cardinalsites/compare/$CS_OLD...$CS_NEW" \
  --jq '.files[] | select(.filename|test("^(composer\\.json|patches/|hooks/)")) | .filename'
```

Copy over the relevant root-level changes (patches, constraints, `config` flags,
hook steps). Keep the **CASBS-specific root deltas**:

- `wikimedia/composer-merge-plugin`, which merges the profile's `composer.json`
- the `config_readonly` patch (JSON:API config pages)
- the `conflict` pins: `drupal/jsonapi_views` and `su-sws/drupal-patches`
  (the `drupal/readonly_field_widget` pin was removed in SWSDEVOPS-347)
- the `sync-casbs`, `sync-db` and `pull-profile` scripts
- the `Drupal\CasbsDrush\` autoload (`drush/casbs/src`)

Throughout this doc, `OLD` is the profile tag the subtree is on now and `NEW`
is the target tag:

```bash
OLD=<current profile tag>   # e.g. 13.0.12
NEW=<target profile tag>
```

Find out which upstream tag the subtree history *thinks* it is on:

```bash
git log -1 --grep='git-subtree-dir: docroot/profiles/custom/stanford_profile' \
  --format='%H%n%B' | grep -E 'git-subtree-(dir|split)'
# Compare the split sha with the tag's commit. Upstream tags are lightweight,
# so only the plain ref prints; the ^{} line appears for annotated tags.
git ls-remote https://github.com/SU-SWS/stanford_profile.git "refs/tags/$OLD" "refs/tags/$OLD^{}"
```

If the shas match, use **Path A**. If they don't (the metadata is stale), or this
is a major-version jump, use **Path B**.

## 3. Path A: standard minor/patch update

### How `git subtree` finds its base

`git subtree pull --squash` uses the most recent commit whose message contains
`git-subtree-dir:` / `git-subtree-split:` trailers as the merge base. In squash
mode, that commit's tree must equal the subtree. **A GitHub squash-merge
destroys that commit.** That is what happened with PR #99: history pointed at
12.2.2 while the files were already at 12.2.4. See
[section 9](#9-pr-and-merge-policy).

### Steps

1. Update the tag in the `pull-profile` script in root `composer.json`. Always
   pin to a **tag**, never to a branch. Older versions of this script pulled the
   `12.x` branch.

   ```json
   "git subtree pull --prefix=docroot/profiles/custom/stanford_profile --squash stanford_profile tags/<NEW> || true",
   ```

   If SSH to GitHub fails, point the remote at HTTPS:
   `git remote set-url stanford_profile https://github.com/SU-SWS/stanford_profile.git`.

2. Get a clean **tracked** tree. `pull-profile` runs `rm -rf` on the profile
   directory, and `subtree pull` refuses to run on a dirty tree. Commit your
   work, or stash only the paths you need to:

   ```bash
   git status
   git stash push -- composer.json   # stash specific paths only
   ```

   Avoid `git stash --include-untracked`. It also stashes untracked files such
   as `.claude/`. Never use `git stash --all`; it also stashes ignored files:
   `local.settings.php`, `local.drush.yml`, `keys/`.

3. Run it:

   ```bash
   lando composer pull-profile
   ```

   The script removes and restores the profile directory, adds the remote, and
   runs a squashed subtree pull of the tag. Then it runs
   `git checkout --ours` on the profile `composer.json` and
   `stanford_profile.info.yml`.

4. **Check those two files.** The `--ours` lines only take effect when those
   files *conflict*, and then they silently keep the **CASBS (old)** copy. On a
   major upgrade that leaves old dependency constraints and the old profile
   version in place. After any pull, both files must match upstream's copy at
   `NEW`, with exactly one CASBS change re-applied:

   ```bash
   P=docroot/profiles/custom/stanford_profile
   for f in composer.json stanford_profile.info.yml; do
     gh api "repos/SU-SWS/stanford_profile/contents/$f?ref=$NEW" --jq .content \
       | base64 -d | diff - "$P/$f"
   done
   # Expected diff: composer.json only, "drupal/jsonapi_views": "~1.1.0"
   ```

5. Resolve any remaining conflicts using the
   [drift appendix](#appendix-known-casbs-drift-inside-the-subtree). Then
   continue with [sections 5 to 8](#5-root-composer-and-dependencies).

## 4. Path B: major update or stale subtree metadata

Used for 13.0.12. This is a file-level 3-way merge in the working tree. Its
results don't depend on the subtree history.

### 4.1 Fetch BASE and THEIRS

```bash
W=$(mktemp -d)   # keep it outside the repo
for t in "$OLD" "$NEW"; do
  mkdir -p "$W/$t"
  gh api "repos/SU-SWS/stanford_profile/tarball/$t" > "$W/$t.tgz"
  tar -xzf "$W/$t.tgz" -C "$W/$t" --strip-components=1
done
```

Tarballs leave out `export-ignore` paths, so check each one against the git tree:

```bash
for t in "$OLD" "$NEW"; do
  gh api "repos/SU-SWS/stanford_profile/git/trees/$t?recursive=1" --jq .truncated   # must be false
  gh api "repos/SU-SWS/stanford_profile/git/trees/$t?recursive=1" \
    --jq '.tree[] | select(.type=="blob") | .path' | sort > "$W/$t.tree"
  (cd "$W/$t" && find . \( -type f -o -type l \) | sed 's|^\./||' | sort) > "$W/$t.files"
  comm -23 "$W/$t.tree" "$W/$t.files"   # missing from tarball; fetch these individually
done
```

For 12.2.4 and 13.0.12 both lists were empty (2708 and 2841 blobs, symlinks
included), so the tarballs were complete.

### 4.2 Produce the CASBS drift list

```bash
P=docroot/profiles/custom/stanford_profile
diff -rq --no-dereference -x node_modules "$W/$OLD" "$P"
```

`--no-dereference` compares symlinks as links instead of following them, so
upstream's symlinks don't show up as false differences.

Every line in the output is CASBS drift: a file modified, added, or deleted
relative to upstream `OLD`. The one exception is
`Only in .../themes/stanford_basic: .yarn`. That directory is the gitignored
Yarn cache from the local theme build. It is not drift. Check the list against the
[appendix](#appendix-known-casbs-drift-inside-the-subtree) and update the
appendix if it has changed.

### 4.3 Merge rules (per file)

| Case | Action |
|---|---|
| CASBS didn't change it (ours == base) | Take THEIRS, including upstream adds, deletes and symlinks |
| Upstream didn't change it (theirs == base) | Keep OURS |
| CASBS-only file (not in base or theirs) | Keep |
| Both changed | `git merge-file -p ours base theirs > merged`, then resolve the conflict markers by hand |

Example for one "both changed" file:

```bash
f=config/sync/core.extension.yml
git merge-file -p "$P/$f" "$W/$OLD/$f" "$W/$NEW/$f" > "$W/merged" ; echo "conflicts: $?"
cp "$W/merged" "$P/$f"
```

Afterwards, apply [step 4 of Path A](#3-path-a-standard-minorpatch-update)
(profile `composer.json` / `info.yml` equal upstream plus the `jsonapi_views`
pin), and `diff -rq` again against `$W/$NEW` to confirm that only the expected
drift is left.

### 4.4 Re-anchor the subtree metadata

Without this step the next Path A pull will use the wrong base. Do it after the
merge result is committed on the branch.

```bash
# Tracked tree must be clean: stash specific tracked paths only, if needed.
git fetch https://github.com/SU-SWS/stanford_profile.git "refs/tags/$NEW"
git subtree merge --squash --prefix=docroot/profiles/custom/stanford_profile FETCH_HEAD
git log -1 --format=%B | grep git-subtree-split   # must equal the $NEW tag's commit sha
git ls-remote https://github.com/SU-SWS/stanford_profile.git "refs/tags/$NEW" "refs/tags/$NEW^{}"
```

Your tree already contains the merged result, so this merge should change no
files, or very few. If it does report conflicts, resolve them in favour of the
committed working tree. Also update the `pull-profile` tag to `NEW`.

## 5. Root composer and dependencies

```bash
lando composer update -W
lando composer validate --no-check-publish
lando composer audit
```

- `composer-exit-on-patch-failure` is on. Watch for "Could not apply patch".
  Rebase or drop patches that upstream has merged.
- The root `config` sets `"policy": false` to match cardinalsites. With
  Composer 2.11, this means the `config.audit.ignore` list is **ignored**. Run `composer audit` by hand and track the
  results. At 13.0.12, `enshrined/svg-sanitize` 0.22.0 had 3 open advisories.
- The `su-sws/drupal-patches` version is frozen by `conflict` (currently
  `>11.2.5`). Raise it on purpose, together with cardinalsites.
- The remaining CASBS conflict pins are `drupal/jsonapi_views` (`>1.1.0`) and
  `su-sws/drupal-patches`. The `jsonapi_views` pin protects the FileMaker
  JSON:API sync. Don't relax it unless the sync has been tested.
- The `drupal/readonly_field_widget` pin (`>2.1.0`, added in `aca2a376` with
  no recorded reason) was removed in SWSDEVOPS-347. Version 2.1.1 (the same
  as cardinalsites 7.0.3) adds `formatter_third_party_settings` and
  `empty_text` to the widget's config schema. Core 11.4 sorts config by
  schema, so with 2.1.0 the active
  `core.entity_form_display.node.stanford_event.default` never matched
  upstream's base file and stayed "Different" in `config:status`. Don't add
  the pin back without a documented reason.
- Check that the `composer.lock` diff only touches the packages you expect.
- After new PHP classes appear (Drush commands, hook classes), run
  `lando restart`. `cr` alone is not enough.

## 6. Database and config

1. **Take a rollback snapshot first:**

   ```bash
   lando db-export pre-upgrade-$OLD.sql.gz
   ```

2. On major upgrades, run `updb` **before** `cr`. A cache rebuild can fatal
   before the updates run (for example, graphql 5 typed properties).

3. Iterate until clean:

   ```bash
   lando drush @default.local updb -y
   lando drush @default.local cr
   lando drush @default.local cim -y      # fix errors in split/patch config, repeat
   lando drush @default.local cex -y
   ```

   Throw away `cex` changes that only reorder keys. Keep real changes, and check
   that CASBS config lands in `config/sync/split/casbs/`.

### Major-version gotchas (13.0)

- **DS / ui_patterns 1 removal.** Split config that depends on DS or
  ui_patterns 1 breaks `cim`. Convert those displays to `jumpstart_ui_one_column`
  or similar layouts in `split/casbs/`.
- **Stored Layout Builder overrides.** Converting config doesn't fix per-node
  overrides. Look for removed layouts:

  ```bash
  lando drush @default.local sql:query \
    "SELECT entity_id FROM node__layout_builder__layout WHERE layout_builder__layout_section LIKE '%<removed_layout_id>%'"
  ```

- **Removed permissions.** If a permission no longer exists but is still listed
  in a `config_split.patch.user.role.*.yml`, `cim` adds it back. Remove it from
  the patch as well.
- **graphql 5 first `updb`.** Update hooks that load `graphql_server` entities
  (`ui_patterns_library_update_10201`, `ui_patterns_update_10202`,
  `graphql_compose_update_30000`) fatal with "Cannot assign null to property
  ...Server::$query_depth of type int" before `graphql_update_50001` runs.
  A Drush `pre-command` hook on `updatedb` handles this:
  `drush/casbs/src/Drush/Commands/UpgradeWorkaroundCommands.php`. It casts
  `query_depth`/`query_complexity` to int on the raw
  `graphql.graphql_servers.*` config and marks `graphql_compose_update_30000`
  as run, guarded by the installed schema versions (a no-op once updated).
  Because it hooks `updatedb` itself, it runs on every path with no extra
  step: `drush deploy` (runs `updatedb` as a subprocess), the Acquia hooks'
  `drush sws:multisite:update:parallel` (runs `drush @self deploy` per site),
  `post-db-copy`'s `drush deploy`, CI `drush drupal:sync` (runs
  `drush @default.local deploy`), and local `updb`. Drush finds it through
  PSR-4 discovery of the root `Drupal\CasbsDrush\` autoload
  (`*\Drush\Commands\*Commands.php`). Look for
  `SWSDEVOPS-347: normalized ...` / `marked graphql_compose_update_30000 as run`
  notices in the `updb` output.

  Delete that one file after the first successful prod deploy of 13.x
  (SWSDEVOPS-347).

## 7. Review checklist

- **CASBS split** (`config_split.config_split.casbs.yml`, `split/casbs/`).
  Check whether upstream changes to base config conflict with partial-split
  patches, and whether new upstream config overlaps the `complete_list`.
- **Roles and permissions.** Check the `user.role.*` changes and the
  `site_editor`, `layout_builder_user` and `decoupled_site_users` patches.
- **JSON:API / FileMaker.** Check `jsonapi.settings`, `jsonapi_extras.*`,
  `stanford_decoupled`, and the `decoupled_site_users` role.
- **Migrations.** Check `migrate_plus.migration.*` (events, people, courses).
- **GraphQL.** The base `graphql_compose.settings.graphql_compose_server.yml`
  must stay identical to upstream. The CASBS entries live in
  `split/casbs/config_split.patch.graphql_compose.settings.graphql_compose_server.yml`
  (partial split). After `cex`, check that the base file still equals upstream
  and that the patch only adds CASBS bundles/vocabularies and
  `su_search_exclusion` entries.
- **Theme.** Check that `themes/stanford_basic` still has the CASBS
  customizations, and rebuild:

  ```bash
  cd docroot/profiles/custom/stanford_profile/themes/stanford_basic
  corepack yarn install --immutable && corepack yarn build
  grep -c '\.casbs-' dist/css/theme.css   # must be > 0
  ```

  Never take upstream's built `dist/css/theme.css` as-is. That once dropped all
  CASBS rules (restored in `5cdae5ab`).

## 8. Final verification

Prove that deploy works from prod-like data in a single pass. This is what
Acquia will run.

```bash
lando db-import <prod-or-pre-upgrade snapshot>
lando drush @default.local deploy -y          # updb + cim + cr, must succeed first time
lando drush @default.local config:status      # must be clean
lando drush @default.local deploy -y          # second run must be a no-op
```

Then smoke-test: pages load with no PHP errors, SAML login works, JSON:API
endpoints respond (FileMaker sync), CASBS content types, views and the theme
render, and imports run.

## 9. PR and merge policy

- Open the PR against `12.x`, linking the Jira ticket, old and new profile
  tag, cardinalsites tag, and any manual post-deploy steps.
- **A PR that contains a subtree squash commit (`git-subtree-split:` trailer)
  must be merged with a merge commit, never squash-merged or rebased.**
  Squashing deletes the subtree metadata and breaks the next pull.
- After the merge, check on `12.x` that
  `git log -1 --grep=git-subtree-dir:` shows the `NEW` split sha.

## 10. Troubleshooting

| Symptom | Fix |
|---|---|
| `working tree has modifications. Cannot add.` | Commit, or `git stash push -- <paths>`, then rerun |
| Subtree pull produces huge or nonsensical conflicts | The metadata is stale (squash-merged earlier). Use Path B, then re-anchor |
| Profile `composer.json`/`info.yml` still show the old version | The `--ours` lines kept the CASBS copy. Replace them with upstream's at `NEW` plus the `jsonapi_views` pin |
| `Could not apply patch` | The patch was merged upstream or has drifted. Update or drop the entry in `extra.patches` |
| `cr` fatals right after the upgrade | Run `updb` first (the `UpgradeWorkaroundCommands` pre-`updatedb` hook handles graphql 5) |
| `cim` fails on missing plugin/layout | Split config depends on a removed module. Convert it in `split/casbs/`, and check stored LB overrides |
| A removed permission comes back after `cim` | Remove it from the `config_split.patch.user.role.*` file too |
| CASBS styles are missing | Rebuild the theme. Check `customizations/index` import and `.casbs-*` in `dist/css/theme.css` |
| Wrong site responds in Lando | Another checkout also uses Lando name `casbs`. Run `docker ps --format '{{.Names}} {{.Label "io.lando.src"}}'` |
| `git fetch`/`subtree` over SSH fails | Use the HTTPS URL |
| Composer timeout | For scripts such as `sync-casbs`, use `lando composer run-script --timeout=0 <script>`. Otherwise set `"process-timeout": 0` in the root `composer.json` `config` |

Debug config with:

```bash
lando drush @default.local config:status
lando drush @default.local config:import --diff   # prompts before importing; answer "no" to only preview
lando drush @default.local config-split:status
```

`config:export --diff` and `config:import --diff` still write after the diff.
In a non-interactive shell the prompt auto-answers yes, so don't use them only
to preview.

## Appendix: known CASBS drift inside the subtree

These are the files where CASBS intentionally differs from upstream (as of
13.0.12). Keep these changes on every pull. Regenerate the list with
[section 4.2](#42-produce-the-casbs-drift-list).

**Composer / profile metadata**
- `composer.json`: upstream at `NEW`, plus `"drupal/jsonapi_views": "~1.1.0"`
- `stanford_profile.info.yml`: upstream at `NEW`

**Config (`config/sync/`)**
- `config_split.config_split.casbs.yml` and all of `split/casbs/`
- purge: `purge.logger_channels.yml`, `purge.plugins.yml`,
  `purge_queuer_coretags.settings.yml`,
  `ultimate_cron.job.purge_processor_cron_cron.yml`
- `core.extension.yml`: adds `acquia_purge` / `purge*`, and removes
  `react_paragraphs` and `react_paragraphs_behaviors`
- `system.rss.yml`
- `system.action.media_break_lock_action.yml`
- `core.entity_view_display.node.stanford_person.default.yml`: **deleted** from
  base. The split `complete_list` owns it.
- `user.role.layout_builder_user.yml`: the `stanford_person` display
  dependency is removed by the split. Recorded base drift: the role's
  dependency on a display the `casbs` split owns has to go from the base file,
  which a partial-split patch can't express cleanly.
- `core.extension.yml` (above) is also recorded base drift on purpose: a
  split's `module` list can only add modules on import, so it can't express
  removing upstream's `react_paragraphs*`. The `acquia_purge`/`purge*` adds go
  with the purge config files above, which are base drift too.

`graphql_compose.settings.graphql_compose_server.yml` is **not** drift: the
base file is byte-identical to upstream. The CASBS additions (bundles
`casbs_book` and `datatables_data`, the 7 `su_casbs_*` vocabularies, and
`su_search_exclusion` entries for 6 bundles, which stanford_profile_helper's
`field_config_insert` hook writes during `cim`) are carried by the `casbs`
split `partial_list` as
`split/casbs/config_split.patch.graphql_compose.settings.graphql_compose_server.yml`.

**Tests**
- `LocalFooterLockupCest`, `TeaserCest`, `DecoupledMenuCest`

**Theme (`themes/stanford_basic/`)**
- `src/scss/theme/index.scss`: the `@import 'customizations/index'` line
- `src/scss/theme/customizations/_casbs.scss` (and `customizations/index`)
- `dist/css/theme.css`: always rebuilt locally, never taken from upstream
