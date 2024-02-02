#!/bin/bash
CMD="cd web && drush $@"
docker-compose exec drupal bash -c "$CMD"
