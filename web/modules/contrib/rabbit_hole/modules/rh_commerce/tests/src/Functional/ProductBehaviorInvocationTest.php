<?php

namespace Drupal\Tests\rh_commerce\Functional;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorInvocationTestBase;

/**
 * Test that rabbit hole behaviors are invoked correctly for commerce products.
 *
 * @requires module commerce
 * @group rh_commerce
 */
class ProductBehaviorInvocationTest extends RabbitHoleBehaviorInvocationTestBase {

  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'commerce_product';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_commerce'];

  /**
   * Product type.
   *
   * @var \Drupal\commerce_product\Entity\ProductTypeInterface
   */
  protected $productType;

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle($action = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_type');
    $product_type = $storage->create([
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ]);
    $storage->save($product_type);
    $this->productType = $product_type;

    if (isset($action)) {
      $this->behaviorSettingsManager->saveBehaviorSettings([
        'action' => $action,
        'allow_override' => TRUE,
      ], 'commerce_product_type', $this->productType->id());
    }
    return $this->productType->id();
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
      'type' => $this->productType->id(),
      'stores' => [$this->createStore()],
    ]);
    $product->save();

    return $product;
  }

}
