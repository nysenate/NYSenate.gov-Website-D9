# NYS Issue Migration

Drush commands for building and populating the canonical `global_issues`
taxonomy vocabulary, and for migrating data over from the old `issues`
vocabulary / `field_issues` system. All commands are dry-run by default —
pass `--commit` to actually write.

Two unrelated pieces live in this module:

- **`issue-tag-migrate`** — the original (never fully completed) project to
  consolidate ~10,000 messy `issues` vocabulary tags down to ~75 canonical
  terms *within that same vocabulary*. Its output — the classification CSV
  described below — is reused by the newer commands. Not part of this guide.
- **The four commands below** — build the new, separate `global_issues`
  vocabulary and populate it, without touching the old `issues` system at
  all. This is the guide for replicating that work on another environment.

## Before you start: files that don't travel with git

`web/sites/*/files/` is gitignored. These CSVs live under
`web/sites/default/files/issue-tags/` and will **not** be present on a
fresh checkout of a test/live environment — copy them over manually
(SFTP, `ddev import-files`-style transfer, whatever the environment uses)
before running any of the commands below:

- `tag_classification_final_2026-08-06.csv` — the old→canonical tag
  mapping from the original consolidation project. Required by
  `global-issues-migrate-followers` and `global-issues-tag-content`.
- `pass1-canonical-terms-create.csv` — the canonical term list (name,
  keywords, description). Required by `global-issues-import-terms`.
- `pass2-field-related-issue-update.csv` — the related-issues mapping.
  Required by `global-issues-import-related`, and only buildable *after*
  pass 1 has run on that environment (it references live tids).

Everything else (the `global_issues` vocabulary itself, `field_global_issues`
and its 14 bundle instances, `field_keywords`/`field_related_issue`, the
`pathauto` pattern, the `follow_global_issue` flag, the view changes, the
templates) is in `config/sync` and comes over with the normal
`drush config:import` deploy step — no manual work needed for that part.

## Order of operations

Run in this order. Each command is safe to re-run (matches by name or tid,
updates in place rather than duplicating), so re-running a step after
fixing a CSV typo is fine.

### 1. Deploy code + config

```
drush deploy   # or: drush cim -y && drush updb -y && drush cr
```

This creates the `global_issues` vocabulary's fields, the pathauto pattern,
the `follow_global_issue` flag, and wires up the views/templates. The
vocabulary itself will be empty until step 2.

### 2. Pass 1 — create the canonical terms

```
drush global-issues-import-terms
drush global-issues-import-terms --commit
```

Reads `pass1-canonical-terms-create.csv` (columns: `name`,
`field_keywords`, `description`). Creates one term per row in
`global_issues`, splitting `field_keywords` on commas into the field's
separate values. Matches existing terms by name, so re-running with an
updated CSV updates rather than duplicates.

### 3. Export name/tid for building the pass-2 CSV

The pass-2 CSV needs real tids, which only exist after step 2 has run on
*this* environment (tids differ across environments). Export them:

```
drush php:eval "
\$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
\$ids = \$storage->getQuery()->condition('vid', 'global_issues')->accessCheck(FALSE)->sort('name', 'ASC')->execute();
\$out = fopen('canonical-terms-name-tid.csv', 'w');
fputcsv(\$out, ['name', 'tid']);
foreach (\$storage->loadMultiple(\$ids) as \$term) {
  fputcsv(\$out, [\$term->getName(), \$term->id()]);
}
fclose(\$out);
echo 'Wrote ' . count(\$ids) . ' rows.' . PHP_EOL;
"
```

Writes `../../../sites/default/files/issue-tags/canonical-terms-name-tid.csv` to the Drupal root. Use it to build
`pass2-field-related-issue-update.csv` (columns: `tid`,
`field_related_issue` [comma-separated related tids], `name`), then copy
that CSV to `web/sites/default/files/issue-tags/` on this environment.

### 4. Pass 2 — set related issues

```
drush global-issues-import-related
drush global-issues-import-related --commit
```

Reads `pass2-field-related-issue-update.csv` and writes
`field_related_issue`. Matches rows by `tid`, and cross-checks the CSV's
`name` column against the term's actual current name — a mismatch means
the tid doesn't refer to what the CSV was built against (e.g. terms were
recreated since the name/tid export), and the row is skipped rather than
guessed at. Any related tid that doesn't resolve to a real `global_issues`
term is dropped with a warning, rather than failing the whole row.

### 5. Migrate followers

```
drush global-issues-migrate-followers
drush global-issues-migrate-followers --commit
```

Reads `tag_classification_final_2026-08-06.csv`. For every user following
an old `issues` term that maps to a canonical concept, creates a
`follow_global_issue` flagging on the corresponding `global_issues` term.
Purely additive — the old `follow_issue` flaggings are left completely
untouched, nothing is deleted or modified there. Skips any (user, term)
pair that's already flagged, so it's safe to re-run.

### 6. Backfill content tags

```
drush global-issues-tag-content
drush global-issues-tag-content --commit
```

Also reads `tag_classification_final_2026-08-06.csv`. For every non-bill
node with `field_issues` populated and `field_global_issues` still empty,
resolves each `field_issues` tag to a canonical `global_issues` term and
writes the distinct result. Only ever touches nodes where
`field_global_issues` is empty, so it's safe to re-run — it won't overwrite
anything already set (by this command or by an editor). Writes directly to
the node's current revision without creating a new one, since this is a
backend backfill, not an editorial change.

## Expected warnings

Both step 5 and step 6 will warn about two canonical concept names with no
matching `global_issues` term: **Veterans Hall of Fame** and **Women of
Distinction**. These existed as canonical categories in the old
consolidation project but were deliberately left out of the new 77-term
canonical list (they're recognition/award categories, not issues).
Followers/content whose only old tag was one of these are left unmigrated
rather than mapped to something incorrect. That's expected, not an error.

## What each command never touches

- The `issues` vocabulary, its terms, or `field_issues` values on any node.
- The `follow_issue` flag or its flaggings.
- `bill` nodes (excluded from the content backfill entirely).
- Any node that already has `field_global_issues` set, or any user who
  already follows the relevant `global_issues` term (both migrations skip
  rather than duplicate).
