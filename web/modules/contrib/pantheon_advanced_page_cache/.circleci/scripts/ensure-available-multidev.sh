#!/bin/bash
set -euo pipefail
IFS=$'\n\t'
###
# Given a site name, ensure we can create a new multidev, or delete the oldest one.
###

usage_exit(){
    echo "usage: $0 site_name multidev_count"
    exit 1
}

SITE_NAME="${1:-}"
if [[ -z "${SITE_NAME}" ]];then
    echo "Missing Site Name"
    usage_exit
fi

NUMBER_OF_CDES_REQUIRED="${2:-}"
if [[ "${NUMBER_OF_CDES_REQUIRED}" == "" ]]; then
    echo "No multidev count provided, assuming 1."
    NUMBER_OF_CDES_REQUIRED=1
elif ! [[ $NUMBER_OF_CDES_REQUIRED =~ ^[0-9]+$ ]]; then
    echo "The variable is not an integer."
    usage_exit
fi

MAX_CDE_COUNT="$(terminus site:info "${SITE_NAME}" --field='Max Multidevs')"
echo "Max Multidev Count: ${MAX_CDE_COUNT}"

DOMAINS="$(terminus env:list "${SITE_NAME}" --format=string --fields=ID,Created)"

# Filter out dev, test, live as they don't count agains the Max Multidev count.
CDE_DOMAINS=$(echo "$DOMAINS" | grep -vE '\b(dev|test|live)\b')

# Count current environments
CDE_COUNT="$(echo "$CDE_DOMAINS" | wc -l)"
# remove whitespace to make the arithmetic work
CDE_COUNT="${CDE_COUNT//[[:blank:]]/}"

echo "There are currently ${CDE_COUNT}/${MAX_CDE_COUNT} multidevs. I need ${NUMBER_OF_CDES_REQUIRED}."

POTENTIAL_CDE_COUNT=$((CDE_COUNT + NUMBER_OF_CDES_REQUIRED))
if [[ "${POTENTIAL_CDE_COUNT}" -le "${MAX_CDE_COUNT}" ]]; then
  echo "There are enough multidevs."
  exit
fi

NUMBER_OF_CDES_TO_DELETE=$((POTENTIAL_CDE_COUNT - MAX_CDE_COUNT))
echo "There are not enough multidevs, deleting the oldest ${NUMBER_OF_CDES_TO_DELETE} environment(s)."

# Filter out any multidev environments that should never be deleted.
# This is seperate from filtering out dev/test/live above as they still count towards 'Max Multidev'.
# Then, sort the list by the timestamps
SORTED_DOMAINS=$(echo "$CDE_DOMAINS" | grep -vE '\b(drupal10)\b' | sort -n -k2)

# Delete as many multidevs as we need to make room for testing.
for (( i = 1; i<=NUMBER_OF_CDES_TO_DELETE; i++ )); do
    ENV_TO_REMOVE="$(echo "$SORTED_DOMAINS" | head -n "$i" | tail -n 1 | cut -f1)"
    echo "Removing '${ENV_TO_REMOVE}'."
    terminus multidev:delete --delete-branch "${SITE_NAME}.${ENV_TO_REMOVE}" --yes
done
