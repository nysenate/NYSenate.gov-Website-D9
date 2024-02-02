<?php

/**
 * @file
 * Post update functions for Components.
 *
 * All empty post-update hooks ensure the cache is cleared.
 * @see https://www.drupal.org/node/2960601
 */

/**
 * Clear caches to allow alter hooks used by components.registry service.
 */
function components_post_update_components_info_alter() {
}

/**
 * Clear caches to allow caching of data by components.registry service.
 */
function components_post_update_components_info_cache() {
}

/**
 * Clear caches to load new components.registry service.
 */
function components_post_update_components_registry_service() {
}

/**
 * Clear caches to load updated components.twig.loader service.
 */
function components_post_update_components_twig_loader_service() {
}

/**
 * Clear caches to allow components.registry service to cache template paths.
 */
function components_post_update_components_registry_cache_paths() {
}
