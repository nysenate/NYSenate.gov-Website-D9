<?php

/**
 * @file
 * Contains hooks and methods for twitter_api_block module.
 */

use Drupal\Component\Serialization\Json;

/**
 * Prepares variables for a list of tweets.
 *
 * Default template: tweets.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - tweets: A list of tweets retrieved by the query against the Twitter API.
 *   - oembed: Either true or false.
 *   - context: An array with extra info to please crazy advanced theming logic.
 */
function template_preprocess_tweets(array &$variables) {
  $tweets = $variables['elements']['#tweets'] ?? [];
  $oembed = $variables['oembed'] = $variables['elements']['#oembed'] ?? [];

  // Render list of tweets.
  $variables['items'] = [
    '#theme' => 'item_list',
  ];

  foreach ($tweets as $tweet) {
    $variables['items']['#items'][] = [
      '#theme' => 'tweet',
      '#tweet' => $tweet,
      '#oembed' => $oembed,
    ];
  }

  // Expose Twitter API query&co for advanced frontend.
  $context = $variables['elements']['#context'] ?? [];
  if (!empty($context)) {
    $variables['attributes']['data-twitter-api-block-context'] = Json::encode($context);
  }

  if ($oembed) {
    // Twitter JS widget script to render OEmbed tweets.
    $variables['#attached']['library'][] = 'twitter_api_block/embed';
  }
}

/**
 * Prepares variables for a list of tweets.
 *
 * Default template: tweets.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - tweet: The original data receive from the Twitter API.
 *   - oembed: Wether or not this tweet needs to be rendered as HTML.
 */
function template_preprocess_tweet(array &$variables) {
  $tweet = $variables['tweet'] = $variables['elements']['#tweet'] ?? [];

  // Get OEmbed tweet (HTML).
  if ($variables['elements']['#oembed'] ?? FALSE) {
    $variables['oembed'] = \Drupal::service('twitter_api_block.twitter')
      ->renderTweet($tweet);

    // Twitter JS widget script to render OEmbed tweets.
    $variables['#attached']['library'][] = 'twitter_api_block/embed';
  }
}
