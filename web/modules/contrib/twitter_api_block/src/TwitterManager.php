<?php

namespace Drupal\twitter_api_block;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\RequestOptions;

/**
 * Default Twitter manager communicator.
 */
class TwitterManager implements TwitterManagerInterface {

  use StringTranslationTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Key service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Twitter application credentials.
   *
   * @var string
   */
  protected $credentials = [];

  /**
   * The Twitter API version.
   *
   * @var string
   */
  protected $version;

  /**
   * The Twitter API bearer token.
   *
   * @var string
   */
  protected $token;

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \Drupal\Core\ClientFactory $http_client_factory
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key service.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current account session.
   */
  public function __construct(
    ClientFactory $http_client_factory,
    LoggerChannelInterface $logger,
    KeyRepositoryInterface $key_repository,
    Token $token_service,
    AccountInterface $current_user
  ) {
    $this->logger = $logger;
    $this->keyRepository = $key_repository;
    $this->tokenService = $token_service;
    $this->currentUser = $current_user;

    $this->httpClient = $http_client_factory->fromOptions([
      'base_uri' => 'https://api.twitter.com/',
    ]);

    $this->version = '2';
  }

  /**
   * {@inheritDoc}
   */
  public function init(string $key_id) {
    $credentials = $this->keyRepository->getKey($key_id)->getKeyInput()->getConfiguration();
    $this->token = $credentials['bearer_token'] ?? NULL;

    // Request a Bearer token, if not set.
    if (!$this->token || empty($this->token)) {
      try {
        $response = $this->httpClient->request('POST', '/oauth2/token', [
          RequestOptions::AUTH => [
            $credentials['client_id'],
            $credentials['client_secret'],
          ],
          RequestOptions::QUERY => [
            'grant_type' => 'client_credentials',
          ],
        ]);

        $results = Json::decode($response->getBody()->getContents());
        $this->token = $results['access_token'];
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }

    return !empty($this->token);
  }

  /**
   * {@inheritDoc}
   */
  public function call(string $method, string $endpoint, array $parameters = []) {
    // Replace tokens.
    $token_params = ['clear' => TRUE];
    $token_data = ($parameters['token_data'] ?? []) + ['user' => $this->currentUser];

    $parameters = array_filter($parameters, function ($key) {
      return in_array($key, [
        'query',
        'start_time',
        'end_time',
        'since_id',
        'until_id',
        'max_results',
        'next_token',
        'pagination_token',
        'sort_order',
        'expansions',
        'tweet.fields',
        'media.fields',
        'poll.fields',
        'place.fields',
        'user.field',
      ]);
    }, ARRAY_FILTER_USE_KEY);

    foreach ($parameters as $key => $value) {
      $sanitized_value = Xss::filter($value);
      $processed_value = $this->tokenService->replace($sanitized_value, $token_data, $token_params);
      $parameters[$key] = $processed_value;
    }

    try {
      $arguments = [
        RequestOptions::HEADERS => [
          'Authorization' => 'Bearer ' . $this->token,
          'Accept' => 'application/json',
        ],
        RequestOptions::QUERY => UrlHelper::buildQuery($parameters),
      ];

      // Prefix call with API version/.
      $remote_url = str_replace('//', '/', '/' . $this->version . '/' . $endpoint);

      $response = $this->httpClient->request($method, $remote_url, $arguments);
      $results = Json::decode($response->getBody()->getContents());
      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    return FALSE;
  }

  /**
   * Search tweet and retrieve author information for easier use later.
   *
   * @param array $arguments
   *   A list of parameters to perform a search against Twitter API v2.
   *
   * @return array
   *   The list of tweets.
   *
   * @see https://developer.twitter.com/en/docs/twitter-api/tweets/search/introduction
   */
  public function searchTweets(array $arguments = []) {
    // Get tweet author for later to get OEmbed.
    $defaults['tweet.fields'][] = 'author_id';
    $defaults['expansions'][] = 'author_id';
    $defaults['user.fields'][] = 'username';

    $parameters = array_merge($defaults, $arguments);

    // Flatten parameters.
    foreach ($parameters as $key => $value) {
      if (\is_array($value)) {
        $parameters[$key] = implode(',', $value);
      }
    }

    $results = $this->call('GET', '/tweets/search/recent', $parameters);
    $authors = array_column($results['includes']['users'] ?? [], 'username', 'id');

    $tweets = [];
    foreach (($results['data'] ?? []) as $delta => $data) {
      if ($username = $authors[$data['author_id']] ?? NULL) {
        $tweets[$delta] = $data + ['username' => $username];
      }
    }

    return $tweets;
  }

  /**
   * Get oEmbed tweet data.
   *
   * @param array $tweet
   *   A tweet's data.
   *
   * @return array
   *   The list of tweet ready for render through our custom theme.
   *
   * @see https://developer.twitter.com/en/docs/twitter-for-websites/embedded-tweets/overview
   */
  public function renderTweet(array $tweet) {
    try {
      $id = $tweet['id'] ?? NULL;
      $username = $tweet['username'] ?? NULL;
      if (!$id || !$username) {
        throw new \Exception($this->t('Missing ID or username to render the tweet @data.', [
          '@data' => '<pre>' . Json::encode($tweet) . '</pre>',
        ]));
      }

      $tweet_uri = sprintf('https://twitter.com/%s/status/%s', $tweet['username'] ?? NULL, $tweet['id'] ?? NULL);
      $tweet_url = sprintf('https://publish.twitter.com/oembed?%s', UrlHelper::buildQuery([
        'url' => $tweet_uri,
      ]));

      $response = $this->httpClient->request('GET', $tweet_url);
      return Json::decode($response->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    return [];
  }

}
