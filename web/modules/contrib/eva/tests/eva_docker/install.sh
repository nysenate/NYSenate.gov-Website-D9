#!/bin/bash

echo "Configure the site"
docker-compose exec drupal bash -c "/startup.sh"

echo "Enabling modules..."
docker-compose exec drupal bash -c "cd web && drush en -y eva eva_test simpletest"

echo "Setting permissions..."
docker-compose exec drupal bash -c "test -d /app/web/sites/default/files/simpletest || mkdir -p /app/web/sites/default/files/simpletest && chown www-data:www-data /app/web/sites/default/files/simpletest && chmod -R 777 /app/web/sites/default/files"
