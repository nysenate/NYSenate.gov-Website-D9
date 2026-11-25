#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 <install-directory>" >&2
  exit 2
fi

readonly terminus_version=4.3.5
readonly terminus_sha256=b5653c9e08329ab087d4b772a98e7a733e9a5b28f9101096d860e25fba91c8d2
install_directory=$1
terminus_path="$install_directory/terminus"

mkdir -p "$install_directory"
curl --fail --location --silent --show-error --retry 3 \
  "https://github.com/pantheon-systems/terminus/releases/download/$terminus_version/terminus.phar" \
  --output "$terminus_path.tmp"
printf '%s  %s\n' "$terminus_sha256" "$terminus_path.tmp" | sha256sum --check --status -
chmod 0755 "$terminus_path.tmp"
mv "$terminus_path.tmp" "$terminus_path"
