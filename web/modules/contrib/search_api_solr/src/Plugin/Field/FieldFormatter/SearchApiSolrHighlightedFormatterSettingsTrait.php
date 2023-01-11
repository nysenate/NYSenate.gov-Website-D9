<?php

namespace Drupal\search_api_solr\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Common formatter settings for SearchApiSolrHighlighted* formatters
 */
trait SearchApiSolrHighlightedFormatterSettingsTrait {

  public static function defaultSettings() {
    return [
        'prefix' => '<strong>',
        'suffix' => '</strong>',
      ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['prefix'] = [
      '#title' => t('Prefix'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('prefix'),
      '#description' => t('The prefix for a highlighted snippet, usually an opening HTML tag. Ensure that the selected text format for this field allows this tag.'),
    ];

    $form['suffix'] = [
      '#title' => t('Suffix'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('suffix'),
      '#description' => t('The suffix for a highlighted snippet, usually a closing HTML tag. Ensure that the selected text format for this field allows this tag.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Highlighting: @prefixtext snippet@suffix', ['@prefix' => $this->getSetting('prefix'), '@suffix' => $this->getSetting('suffix')]);
    return $summary;
  }

  /**
   * Get highlighted field item value based on latest search results.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param $value
   *   The filed item value.
   * @param $langcode
   *   The requested language.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheableMetadata
   *   The cache metadata for the highlighted field item value.
   *
   * @return string
   *   The highlighted field item value.
   */
  protected function getHighlightedValue(FieldItemInterface $item, $value, $langcode, RefinableCacheableDependencyInterface $cacheableMetadata) {
    /** @var \Drupal\search_api\Utility\QueryHelperInterface $queryHelper */
    $queryHelper = \Drupal::service('search_api.query_helper');

    $id_langcode = $item->getLangcode();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $item->getEntity();
    if ($entity instanceof TranslatableInterface) {
      if ($entity->hasTranslation($langcode)) {
        // In case of a non-translatable field of a translatable entity the
        // item language might not match the search_api ID language.
        $id_langcode = $langcode;
      }
    }
    $item_id = Utility::createCombinedId('entity:' . $entity->getEntityTypeId(),$entity->id() . ':' . $id_langcode);
    $highlighted_keys = [];

    $cacheableMetadata->addCacheableDependency($entity);

    foreach ($queryHelper->getAllResults() as $resultSet) {
      foreach ($resultSet->getResultItems() as $resultItem) {
        if ($resultItem->getId() === $item_id) {
          $cacheableMetadata->addCacheableDependency($resultSet->getQuery());
          if ($highlighted_keys_tmp = $resultItem->getExtraData('highlighted_keys')) {
            $highlighted_keys = $highlighted_keys_tmp;
            break 2;
          }
        }
      }
    }

    foreach ($highlighted_keys as $key) {
      $value = preg_replace('/'. preg_quote($key, '/') . '/', $this->getSetting('prefix') . $key . $this->getSetting('suffix'), $value);
    }

    return $value;
  }

}
