<?php

namespace Drupal\twitter_api_block\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a Twitter API 'SearchBlock' block.
 *
 * @Block(
 *   id = "twitter_api_block_search",
 *   admin_label = @Translation("Twitter search"),
 *   category = @Translation("Twitter")
 * )
 */
class TwitterSearchBlock extends TwitterBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form   = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['options']['display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display'),
      '#options' => [
        'embed' => $this->t('Embed'),
        'raw' => $this->t('Raw'),
      ],
      '#default_value' => $config['options']['display'] ?? 'embed',
      '#required' => TRUE,
    ];

    $form['options']['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of tweets to display'),
      '#default_value' => $config['options']['count'] ?? 3,
      '#required' => TRUE,
      '#min' => 0,
    ];

    $form['options']['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Search"),
      '#description' => $this->t("Enter your query string (ex: <code>#drupal</code> or <code>from:drupal</code>).") . '<br>' .
      Link::fromTextAndUrl(
          $this->t('Full documentation here'),
          Url::fromUri('https://developer.twitter.com/en/docs/twitter-api/tweets/search/integrate/build-a-query', [
            'attributes' => [
              'target' => '_blank',
            ],
          ]),
      )->toString(),
      '#default_value' => $config['options']['search'] ?? NULL,
      '#required' => TRUE,
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['options']['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#weight' => 100,
      ];
    }

    $form['options']['tweet_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query Tweets fields'),
      '#default_value' => $config['options']['tweet_fields'] ?? NULL,
      '#description' => $this->t('Comma-separated values. No space after commas.') . '<br>' .
      $this->t("Use it only if you know what you're doing otherwise, leave this empty."),
    ];

    $form['options']['sort_order'] = [
      '#type' => 'radios',
      '#options' => [
        'recency' => $this->t('Most recent first'),
        'relevancy' => $this->t('Most relevant first'),
      ],
      '#title' => $this->t('Sort order'),
      '#default_value' => $config['options']['sort_order'] ?? 'recency',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockBuild() {
    $config = $this->getConfiguration();
    $display = $config['options']['display'] ?? NULL;
    $count = $config['options']['count'] ?? 3;

    $arguments = [];
    $arguments['query'] = $config['options']['search'] ?? '';
    $arguments['max_results'] = $count <= 10 ? 10 : $count;
    $arguments['sort_order'] = $config['options']['sort_order'] ?? 'recency';
    $arguments['tweet.fields'] = explode(',', $config['options']['tweet_fields'] ?? '');

    $tweets = $this->twitter->searchTweets($arguments);

    return [
      '#theme' => 'tweets',
      '#tweets' => array_slice($tweets, 0, $count),
      '#oembed' => ($display == 'embed'),
      '#context' => ['query' => $arguments['query']],
    ];
  }

}
