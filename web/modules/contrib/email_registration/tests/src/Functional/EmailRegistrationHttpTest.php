<?php

declare(strict_types=1);

namespace Drupal\Tests\email_registration\Functional;

use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Url;
use Drupal\user\Controller\UserAuthenticationController;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests login using mail via direct HTTP.
 *
 * This is mostly copied from \Drupal\Tests\user\Functional\UserLoginHttpTest.
 *
 * @group email_registration
 */
class EmailRegistrationHttpTest extends EmailRegistrationFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['email_registration'];

  /**
   * The cookie jar.
   *
   * @var \GuzzleHttp\Cookie\CookieJar
   */
  protected CookieJar $cookies;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected Serializer $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->cookies = new CookieJar();
    $encoders = [new JsonEncoder(), new XmlEncoder()];
    $this->serializer = new Serializer([], $encoders);
  }

  /**
   * Executes a login HTTP request.
   *
   * @param string $mail
   *   The user email.
   * @param string $pass
   *   The user password.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  protected function loginRequest($mail, $pass, $format = 'json'): ResponseInterface {
    $user_login_url = Url::fromRoute('user.login.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();

    $request_body = [];
    if (isset($mail)) {
      $request_body['mail'] = $mail;
    }
    if (isset($pass)) {
      $request_body['pass'] = $pass;
    }

    $result = \Drupal::httpClient()->post($user_login_url->toString(), [
      'body' => $this->serializer->encode($request_body, $format),
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
      'cookies' => $this->cookies,
    ]);
    return $result;
  }

  /**
   * Tests user login.
   */
  public function testLogin(): void {
    $client = \Drupal::httpClient();
    foreach ([FALSE, TRUE] as $serialization_enabled_option) {
      if ($serialization_enabled_option) {
        /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
        $module_installer = $this->container->get('module_installer');
        $module_installer->install(['serialization']);
        $formats = ['json', 'xml'];
      }
      else {
        // Without the serialization module only JSON is supported.
        $formats = ['json'];
      }
      foreach ($formats as $format) {
        // Create new user for each iteration to reset flood.
        // Grant the user administer users permissions to they can see the
        // 'roles' field.
        $account = $this->drupalCreateUser(['administer users']);
        $mail = $account->getEmail();
        $pass = $account->passRaw;

        $login_status_url = $this->getLoginStatusUrlString($format);
        $response = $client->get($login_status_url);
        $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);

        // Flooded.
        $this->config('user.flood')
          ->set('user_limit', 3)
          ->save();

        $response = $this->loginRequest($mail, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized email or password.', $format);

        $response = $this->loginRequest($mail, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized email or password.', $format);

        $response = $this->loginRequest($mail, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized email or password.', $format);

        $response = $this->loginRequest($mail, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 403, 'Too many failed login attempts from your IP address. This IP address is temporarily blocked.', $format);

        // After testing the flood control we can increase the limit.
        $this->config('user.flood')
          ->set('user_limit', 100)
          ->save();

        $response = $this->loginRequest(NULL, NULL, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.', $format);

        $response = $this->loginRequest(NULL, $pass, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.mail.', $format);

        $response = $this->loginRequest($mail, NULL, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.pass.', $format);

        // Blocked.
        $account
          ->block()
          ->save();

        $response = $this->loginRequest($mail, $pass, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'The user has not been activated or is blocked.', $format);

        $account
          ->activate()
          ->save();

        $response = $this->loginRequest($mail, 'garbage', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized email or password.', $format);

        $response = $this->loginRequest('garbage', $pass, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized email or password.', $format);

        $response = $this->loginRequest($mail, $pass, $format);
        $this->assertEquals(200, $response->getStatusCode());
        $result_data = $this->serializer->decode((string) $response->getBody(), $format);
        $this->assertEquals($mail, $result_data['current_user']['mail']);
        $this->assertEquals($account->id(), $result_data['current_user']['uid']);
        $this->assertEquals($account->getRoles(), $result_data['current_user']['roles']);
        $logout_token = $result_data['logout_token'];

        $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
        $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

        $response = $this->logoutRequest($format, $logout_token);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
        $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);

        $this->resetFlood();
      }
    }
  }

  /**
   * Gets a value for a given key from the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param string $key
   *   The key for the value.
   * @param string $format
   *   The encoded format.
   *
   * @return mixed
   *   The value for the key.
   */
  protected function getResultValue(ResponseInterface $response, $key, $format): mixed {
    $decoded = $this->serializer->decode((string) $response->getBody(), $format);
    if (is_array($decoded)) {
      return $decoded[$key];
    }
    return $decoded->{$key};
  }

  /**
   * Resets all flood entries.
   */
  protected function resetFlood(): void {
    \Drupal::database()->delete(DatabaseBackend::TABLE_NAME)->execute();
  }

  /**
   * Executes a logout HTTP request.
   *
   * @param string $format
   *   The format to use to make the request.
   * @param string $logout_token
   *   The csrf token for user logout.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  protected function logoutRequest($format = 'json', $logout_token = ''): ResponseInterface {
    /** @var \GuzzleHttp\Client $client */
    $client = $this->container->get('http_client');
    $user_logout_url = Url::fromRoute('user.logout.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();
    if ($logout_token) {
      $user_logout_url->setOption('query', ['token' => $logout_token]);
    }
    $post_options = [
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
      'cookies' => $this->cookies,
    ];

    $response = $client->post($user_logout_url->toString(), $post_options);
    return $response;
  }

  /**
   * Checks a response for status code and body.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param int $expected_code
   *   The expected status code.
   * @param mixed $expected_body
   *   The expected response body.
   */
  protected function assertHttpResponse(ResponseInterface $response, $expected_code, $expected_body): void {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_body, (string) $response->getBody());
  }

  /**
   * Checks a response for status code and message.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param int $expected_code
   *   The expected status code.
   * @param string $expected_message
   *   The expected message encoded in response.
   * @param string $format
   *   The format that the response is encoded in.
   */
  protected function assertHttpResponseWithMessage(ResponseInterface $response, $expected_code, $expected_message, $format = 'json'): void {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_message, $this->getResultValue($response, 'message', $format));
  }

  /**
   * Gets the URL string for checking login.
   *
   * @param string $format
   *   The format to use to make the request.
   *
   * @return string
   *   The URL string.
   */
  protected function getLoginStatusUrlString($format = 'json'): string {
    $user_login_status_url = Url::fromRoute('user.login_status.http');
    $user_login_status_url->setRouteParameter('_format', $format);
    $user_login_status_url->setAbsolute();
    return $user_login_status_url->toString();
  }

}
