<?php

namespace Drupal\contextual_range_filter\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to execute PHP code to return default argument value.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "php_default",
 *   title = @Translation("PHP code")
 * )
 */
class PhpDefault extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['code'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $warning = $this->t('In order to use this feature you must 1) have the "Use PHP code for default contextual filter" permission and 2) know what you are doing.');
    $help1 = $this->t('Enter PHP code that returns a single text or number to use for this filter. Do <em>not</em> use <code>&lt;?php ?&gt;</code>');
    $help2 = $this->t("Depending on the page you're on some entity objects are available as <code>\$entity['node'], \$entity['user']</code> etc.");
    $example1 = $this->t("<strong>Example</strong>: if the View is a block displayed on <em>node/*</em> pages and the View has a field named <em>Price</em>, then you can return items cheaper than the main node shown, using a snippet like this:");
    $code1 = "<code>return '--' . \$entity['node']->field_price->getString();</code>";
    $help3 = $this->t("<code>--</code> (double hyphen) is the range operator. In the above example there is no lower limit to the range, only an upper limit.");

    $form['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('PHP contextual filter code -- experienced users only'),
      '#default_value' => $this->options['code'],
      '#description' => "$warning<br/>$help1<br/>$help2<br/>$example1<br/>$code1<br/>$help3<br/>",
    ];
    $this->checkAccess($form, 'code');
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return \Drupal::currentUser()->hasPermission('use views php code contextual filter');
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // Make common entities available in the PHP code through variable $entity:
    // $entity['view'], ie. $this->view.
    // $entity['node'], if on a content page, eg node/123.
    // $entity['user'], if on a user page, eg user/456.
    $entity = [];
    foreach ($params = \Drupal::routeMatch()->getParameters() as $type => $param) {
      if ($param instanceof EntityInterface) {
        $entity[$type] = $param;
      }
    }

    ob_start();
    $result = eval($this->options['code']);
    ob_end_clean();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

}
