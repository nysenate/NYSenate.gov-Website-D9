#!/usr/bin/env bash
set -euo pipefail

if [[ $# -eq 1 ]]; then
  destination=$1
else
  echo "Usage: $0 <empty-destination-directory>" >&2
  exit 2
fi

source_root=$(git rev-parse --show-toplevel)
mkdir -p "$destination"

if [[ -n "$(find "$destination" -mindepth 1 -print -quit)" ]]; then
  echo "Artifact destination must be empty: $destination" >&2
  exit 1
fi

if [[ ! -d "$source_root/vendor" || ! -d "$source_root/web/core" ]]; then
  echo "Composer dependencies must be installed before artifact staging." >&2
  exit 1
fi

excludes=(
  --exclude='.git'
  --exclude='node_modules'
  --exclude='.env*'
  --exclude='settings.local.php'
  --exclude='settings.ddev*.php'
  --exclude='services.local.yml'
  --exclude='services.yml'
  --exclude='private_settings.php'
  --exclude='/web/sites/*/files/***'
  --exclude='/web/sites/*/private/***'
  --exclude='*.sql'
  --exclude='*.sql.gz'
  --exclude='auth.json'
  --exclude='.npmrc'
  --exclude='*.pem'
  --exclude='*.key'
  --exclude='*.p12'
  --exclude='*.pfx'
  --exclude='/web/themes/custom/*/package.json'
  --exclude='/web/themes/custom/*/package-lock.json'
  --exclude='/web/themes/custom/*/.nvmrc'
  --exclude='/web/themes/custom/*/gulpfile.js'
  --exclude='/web/themes/custom/*/gulp-tasks/***'
  --exclude='/web/themes/custom/*/webpack.config.js'
  --exclude='/web/themes/custom/*/postcss.config.js'
  --exclude='/web/themes/custom/*/pattern-lab/***'
)

tracked_paths=(
  composer.json composer.lock patches.lock.json pantheon.yml pantheon.upstream.yml
  config drush patches scripts/composer/ScriptHandler.php web
)
git -C "$source_root" ls-files -z -- "${tracked_paths[@]}" |
  rsync -az --from0 --files-from=- "${excludes[@]}" "$source_root/" "$destination/"

for scaffold_file in .htaccess autoload.php autoload_runtime.php index.php robots.txt update.php; do
  if [[ ! -f "$source_root/web/$scaffold_file" ]]; then
    echo "Required Drupal scaffold file is missing: web/$scaffold_file" >&2
    exit 1
  fi
  cp "$source_root/web/$scaffold_file" "$destination/web/$scaffold_file"
done

copy_generated_tree() {
  local relative_path=$1

  if [[ -d "$source_root/$relative_path" ]]; then
    mkdir -p "$destination/$relative_path"
    rsync -az "${excludes[@]}" "$source_root/$relative_path/" "$destination/$relative_path/"
  fi
}

for generated_path in \
  vendor \
  drush/Commands/contrib \
  web/core \
  web/libraries \
  web/modules/contrib \
  web/profiles/contrib \
  web/themes/contrib; do
  copy_generated_tree "$generated_path"
done

copy_generated_tree web/themes/custom/nysenate_theme/dist

if [[ -d "$source_root/web/themes/custom/nys/components" ]]; then
  mkdir -p "$destination/web/themes/custom/nys/components"
  rsync -az \
    --exclude='src' \
    --exclude='node_modules' \
    --include='*/' \
    --include='*.css' \
    --include='*.js' \
    --exclude='*' \
    "$source_root/web/themes/custom/nys/components/" \
    "$destination/web/themes/custom/nys/components/"
fi

for required_path in composer.json composer.lock patches.lock.json vendor/autoload.php web/index.php; do
  if [[ ! -e "$destination/$required_path" ]]; then
    echo "Required release file is missing: $required_path" >&2
    exit 1
  fi
done

if [[ -e "$destination/vendor/bin/phpunit" || -d "$destination/vendor/phpunit" ]]; then
  echo "Production artifact must not contain Composer development dependencies." >&2
  exit 1
fi

unexpected=$(find "$destination" \( \
  -name '.env*' -o \
  -name '.git' -o \
  -name 'node_modules' -o \
  -name 'settings.local.php' -o \
  -name 'settings.ddev*.php' -o \
  -name 'services.local.yml' -o \
  -name 'services.yml' -o \
  -name 'private_settings.php' -o \
  -name '*.sql' -o \
  -name '*.sql.gz' -o \
  -name 'auth.json' -o \
  -name '.npmrc' -o \
  -name '*.pem' -o \
  -name '*.key' -o \
  -name '*.p12' -o \
  -name '*.pfx' -o \
  -path "$destination/web/sites/*/files" -o \
  -path "$destination/web/sites/*/private" \
  \) -print -quit)

if [[ -n "$unexpected" ]]; then
  echo "Unexpected file in release artifact: $unexpected" >&2
  exit 1
fi
