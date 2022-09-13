<?php

namespace Drupal\Tests\email_registration\Functional\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the login checkout pane.
 *
 * @group email_registration
 */
class EmailRegistrationLoginTest extends CommerceBrowserTestBase {

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'email_registration',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place commerce blocks.
    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    // Create a product with variation.
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    // Enable the email_registration_login pane and disable the default login
    // pane.
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->container
      ->get('entity_type.manager')
      ->getStorage('commerce_checkout_flow')
      ->load('default');
    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow_plugin */
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    /** @var \Drupal\email_registration\Plugin\Commerce\CheckoutPane\EmailRegistrationLogin $pane */
    $er_login_pane = $checkout_flow_plugin->getPane('email_registration_login');
    $er_login_pane->setConfiguration([]);
    $er_login_pane->setStepId('login');

    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login $pane */
    $login_pane = $checkout_flow_plugin->getPane('login');
    $login_pane->setStepId('_disabled');

    // Save pane settings.
    $checkout_flow_plugin_configuration = $checkout_flow_plugin->getConfiguration();
    $checkout_flow_plugin_configuration['panes']['email_registration_login'] = $er_login_pane->getConfiguration();
    $checkout_flow_plugin_configuration['panes']['login'] = $login_pane->getConfiguration();
    $checkout_flow_plugin->setConfiguration($checkout_flow_plugin_configuration);
    $checkout_flow->save();

    $this->drupalLogout();
    $this->addProductToCart($this->product);
  }

  /**
   * Tests if an user can login with their email address.
   */
  public function testLoginWithMailAddress() {
    // Create an user to login with.
    $account = $this->drupalCreateUser();

    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');

    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getEmail(),
      'email_registration_login[returning_customer][password]' => $account->passRaw,
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertCheckoutProgressStep('Order information');
    $this->assertLoggedIn($account);
  }

  /**
   * Tests if an user can login with their username.
   */
  public function testLoginWithUsername() {
    // Allow users to login with their username.
    $this->config('email_registration.settings')
      ->set('login_with_username', TRUE)
      ->save();

    // Create an user to login with.
    $account = $this->drupalCreateUser();

    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');
    $this->assertSession()->pageTextContains('Email address or username');

    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getAccountName(),
      'email_registration_login[returning_customer][password]' => $account->passRaw,
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertCheckoutProgressStep('Order information');
    $this->assertLoggedIn($account);
  }

  /**
   * Tests trying to login without entering name or mail address.
   */
  public function testLoginWithoutValues() {
    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Log in');
    $this->assertSession()->pageTextContains('Unrecognized email address or password. Forgot your password?');
  }

  /**
   * Tests trying to login with wrong password.
   */
  public function testFailedLogin() {
    // Create an user to login with.
    $account = $this->drupalCreateUser();

    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');

    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getEmail(),
      'email_registration_login[returning_customer][password]' => $account->passRaw . 'foo',
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('Unrecognized email address or password. Forgot your password?');
  }

  /**
   * Tests if an user cannot login with their username when that is forbidden.
   *
   * Thus when the option 'login_with_username' is disabled.
   */
  public function testFailedLoginWithUsername() {
    // Create an user to login with.
    $account = $this->drupalCreateUser();

    // Don't allow users to login with their username.
    $this->config('email_registration.settings')
      ->set('login_with_username', FALSE)
      ->save();

    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');
    $this->assertSession()->pageTextContains('Enter your email address.');

    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getAccountName(),
      'email_registration_login[returning_customer][password]' => $account->passRaw,
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('Unrecognized email address or password. Forgot your password?');
  }

  /**
   * Tests failed login using mail when logging in with username is allowed.
   *
   * When the option 'login_with_username' is enabled and trying to login with
   * an existing mail address, the error message should say "Unrecognized
   * username or password."
   */
  public function testFailedLoginWithMailAddressWhenUsernameIsAllowed() {
    // Create an user to login with.
    $account = $this->drupalCreateUser();

    // Don't allow users to login with their username.
    $this->config('email_registration.settings')
      ->set('login_with_username', TRUE)
      ->save();

    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');

    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getEmail(),
      'email_registration_login[returning_customer][password]' => $account->passRaw . 'foo',
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('Unrecognized username, email, or password. Have you forgotten your password?');
  }

  /**
   * Tests trying to login in with an mail address belonging to a blocked user.
   */
  public function testFailedLoginWithMailAddressWithBlockedUser() {
    // Create an user to login with.
    $account = $this->drupalCreateUser();
    $account->status = FALSE;
    $account->save();

    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');

    // Try logging in with wrong pass first.
    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getEmail(),
      'email_registration_login[returning_customer][password]' => $account->passRaw . 'foo',
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('The account with email address ' . $account->getEmail() . ' has not been activated or is blocked.');

    // Now try to login with right password. Same error message should come up.
    $edit = [
      'email_registration_login[returning_customer][name]' => $account->getEmail(),
      'email_registration_login[returning_customer][password]' => $account->passRaw,
    ];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('The account with email address ' . $account->getEmail() . ' has not been activated or is blocked.');
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet('user/login');
    $this->submitForm([
      'mail' => $account->getEmail(),
      'pass' => $account->passRaw,
    ], t('Log in'));

    $this->assertLoggedIn($account);
  }

  /**
   * Adds the given product to the cart.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product to add to the cart.
   */
  protected function addProductToCart(ProductInterface $product) {
    $this->drupalGet($product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
  }

  /**
   * Proceeds to checkout.
   */
  protected function goToCheckout() {
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
  }

  /**
   * Asserts the current step in the checkout progress block.
   *
   * @param string $expected
   *   The expected value.
   */
  protected function assertCheckoutProgressStep($expected) {
    $current_step = $this->getSession()->getPage()->find('css', '.checkout-progress--step__current')->getText();
    $this->assertEquals($expected, $current_step);
  }

  /**
   * Asserts that a particular user is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check for being logged in.
   */
  protected function assertLoggedIn(AccountInterface $account) {
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
