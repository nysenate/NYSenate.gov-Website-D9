#!/bin/bash
. .env

echo "Running tests..."
for class in $TEST_CLASSES; do
    docker-compose exec -u www-data drupal bash -c "php web/core/scripts/run-tests.sh --verbose --class \"\Drupal\Tests\\$MODULE\Functional\\$class\""
done