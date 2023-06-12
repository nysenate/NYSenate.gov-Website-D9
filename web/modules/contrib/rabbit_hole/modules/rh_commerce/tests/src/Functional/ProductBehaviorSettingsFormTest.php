<?php

namespace Drupal\Tests\rh_commerce\Functional;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;

/**
 * Test the functionality of the rabbit hole form additions to Commerce Product.
 *
 * @requires module commerce
 * @group rh_commerce
 */
class ProductBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  use StoreCreationTrait;

  /**
   * Test product type.
   *
   * @var \Drupal\commerce_product\Entity\ProductTypeInterface
   */
  protected $bundle;

  /**
   * Test store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'commerce_product';

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'commerce_product_type';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_commerce'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->store = $this->createStore();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_type');
    $product_type = $storage->create([
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'variationType' => 'default',
    ]);
    $storage->save($product_type);
    $this->bundle = $product_type;
    return $product_type->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/commerce/config/product-types/add');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'rh_action' => $action,
      'rh_override' => $override,
    ];
    $this->submitForm($edit, 'Save');
    $this->bundle = $this->loadBundle($edit['id']);
    return $edit['id'];
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }

    $product = Product::create($values + [
      'title' => $this->randomString(),
      'type' => $this->bundle->id(),
      'stores' => [$this->store],
    ]);
    $product->save();

    return $product->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('entity.commerce_product.add_form', ['commerce_product_type' => $this->bundle->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.commerce_product.edit_form', ['commerce_product' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.commerce_product_type.edit_form', ['commerce_product_type' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return [
      'administer commerce_store',
      'administer commerce_product',
      'administer commerce_product_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityFormSubmit() {
    return 'Save';
  }

}
