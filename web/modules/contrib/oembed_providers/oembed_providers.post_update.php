<?php

/**
 * @file
 * Post update functions for oEmbed Providers.
 */

/**
 * Clear caches to discover new 'oembed_providers.invalidator' service.
 */
function oembed_providers_post_update_invalidator_service() {
  // No-op.
}

/**
 * Clear caches to discover new local task items.
 */
function oembed_providers_post_update_add_local_tasks() {
  // Empty post-update function.
}

/**
 * Clear caches due to updated ProviderRepositoryDecorator constructor.
 */
function oembed_providers_post_update_decorator_constructor_change() {
  // Empty post-update function.
}

/**
 * Clear caches due to updated ProviderRepositoryDecorator constructor.
 */
function oembed_providers_post_update_decorator_constructor_change2() {
  // Empty post-update function.
}

/**
 * Clear cached media source defintions.
 */
function oembed_providers_post_update_add_provider_to_media_source() {
  // Clear cached media source definitions to register newly added 'provider'
  // in provider bucket-generated definitions.
  \Drupal::service('plugin.manager.media.source')->clearCachedDefinitions();
}

/**
 * Update Provider Buckets' dependencies on Custom Providers.
 */
function oembed_providers_post_update_update_provider_bucket_dependencies() {
  $entities = \Drupal::service('entity_type.manager')->getStorage('oembed_provider_bucket')->loadMultiple();
  // Resave all existing ProviderBucket config entities to add any missing
  // custom provider dependencies.
  foreach ($entities as $entity) {
    $entity->save();
  }
}

/**
 * Update Media Type dependencies on Provider Buckets.
 */
function oembed_providers_post_update_update_media_type_dependencies() {
  // Register new oembed_providers_config_events_subscriber service.
  \Drupal::service('kernel')->invalidateContainer();

  $media_types = \Drupal::service('entity_type.manager')->getStorage('media_type')->loadMultiple();
  foreach ($media_types as $media_type) {
    $plugin_id = $media_type->getSource()->getPluginId();
    if (substr($plugin_id, 0, 7) === "oembed:") {
      $media_type->save();
    }
  }
}
