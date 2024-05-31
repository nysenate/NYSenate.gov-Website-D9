<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Plugin\search_api\processor\Property\RenderedItemProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\ThemeSwitcherInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an additional field containing the rendered item.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Property\RenderedItemProperty
 *
 * @SearchApiProcessor(
 *   id = "rendered_item",
 *   label = @Translation("Rendered item"),
 *   description = @Translation("Adds an additional field containing the rendered item as it would look when viewed."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class RenderedItem extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface|null
   */
  protected $accountSwitcher;

  /**
   * The renderer to use.
   *
   * @var \Drupal\Core\Render\RendererInterface|null
   */
  protected $renderer;

  /**
   * The theme switcher.
   *
   * @var \Drupal\search_api\Utility\ThemeSwitcherInterface|null
   */
  protected $themeSwitcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setAccountSwitcher($container->get('account_switcher'));
    $plugin->setRenderer($container->get('renderer'));
    $plugin->setThemeSwitcher($container->get('search_api.theme_switcher'));
    $plugin->setLogger($container->get('logger.channel.search_api'));

    return $plugin;
  }

  /**
   * Retrieves the account switcher service.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   The account switcher service.
   */
  public function getAccountSwitcher() {
    return $this->accountSwitcher ?: \Drupal::service('account_switcher');
  }

  /**
   * Sets the account switcher service.
   *
   * @param \Drupal\Core\Session\AccountSwitcherInterface $current_user
   *   The account switcher service.
   *
   * @return $this
   */
  public function setAccountSwitcher(AccountSwitcherInterface $current_user) {
    $this->accountSwitcher = $current_user;
    return $this;
  }

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function getRenderer() {
    return $this->renderer ?: \Drupal::service('renderer');
  }

  /**
   * Sets the renderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The new renderer.
   *
   * @return $this
   */
  public function setRenderer(RendererInterface $renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  /**
   * Retrieves the theme switcher.
   *
   * @return \Drupal\search_api\Utility\ThemeSwitcherInterface
   *   The theme switcher.
   */
  public function getThemeSwitcher(): ThemeSwitcherInterface {
    return $this->themeSwitcher ?: \Drupal::service('search_api.theme_switcher');
  }

  /**
   * Sets the theme switcher.
   *
   * @param \Drupal\search_api\Utility\ThemeSwitcherInterface $theme_switcher
   *   The new theme switcher.
   *
   * @return $this
   */
  public function setThemeSwitcher(ThemeSwitcherInterface $theme_switcher): self {
    $this->themeSwitcher = $theme_switcher;
    return $this;
  }

  // @todo Add a supportsIndex() implementation that checks whether there is
  //   actually any datasource present which supports viewing.

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Rendered HTML output'),
        'description' => $this->t('The complete HTML which would be displayed when viewing the item'),
        'type' => 'search_api_html',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['rendered_item'] = new RenderedItemProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Switch to the default theme in case the admin theme (or any other theme)
    // is enabled.
    $previous_theme = $this->getThemeSwitcher()->switchToDefault();

    // Fields for which some view mode config is missing.
    $unset_view_modes = [];

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'rendered_item');
    foreach ($fields as $field) {
      $configuration = $field->getConfiguration();

      // If a (non-anonymous) role is selected, then also add the authenticated
      // user role.
      $roles = $configuration['roles'];
      $authenticated = RoleInterface::AUTHENTICATED_ID;
      if (array_diff($roles, [$authenticated, RoleInterface::ANONYMOUS_ID])) {
        $roles[$authenticated] = $authenticated;
      }

      // Change the current user to our dummy implementation to ensure we are
      // using the configured roles.
      $this->getAccountSwitcher()
        ->switchTo(new UserSession(['roles' => array_values($roles)]));

      $datasource_id = $item->getDatasourceId();
      $datasource = $item->getDatasource();
      $bundle = $datasource->getItemBundle($item->getOriginalObject());
      // When no view mode has been set for the bundle, or it has been set to
      // "Don't include the rendered item", skip this item.
      if (empty($configuration['view_mode'][$datasource_id][$bundle])) {
        // If it was really not set, also notify the user through the log.
        if (!isset($configuration['view_mode'][$datasource_id][$bundle])) {
          $unset_view_modes[$field->getFieldIdentifier()] = $field->getLabel();
        }

        // Restore the original user.
        $this->getAccountSwitcher()->switchBack();

        continue;
      }
      $view_mode = (string) $configuration['view_mode'][$datasource_id][$bundle];

      try {
        $build = $datasource->viewItem($item->getOriginalObject(), $view_mode);
        if ($build) {
          // Add the excerpt to the render array to allow adding it to view modes.
          $build['#search_api_excerpt'] = $item->getExcerpt();
          $value = (string) $this->getRenderer()->renderPlain($build);
          if ($value) {
            $field->addValue($value);
          }
        }
      }
      catch (\Throwable $e) {
        // This could throw all kinds of exceptions in specific scenarios, so we
        // just catch all of them here. Not having a field value for this field
        // probably makes sense in that case, so we just log an error and
        // continue.
        $variables = [
          '%item_id' => $item->getId(),
          '%view_mode' => $view_mode,
          '%index' => $this->index->label(),
        ];
        $this->logException($e, '%type while trying to render item %item_id with view mode %view_mode for search index %index: @message in %function (line %line of %file).', $variables);
      }

      // Restore the original user.
      $this->getAccountSwitcher()->switchBack();
    }

    // Restore the original theme if themes got switched before.
    $this->getThemeSwitcher()->switchBack($previous_theme);

    if ($unset_view_modes > 0) {
      foreach ($unset_view_modes as $field_id => $field_label) {
        $url = new Url('entity.search_api_index.field_config', [
          'search_api_index' => $this->index->id(),
          'field_id' => $field_id,
        ]);
        $context = [
          '%index' => $this->index->label(),
          '%field_id' => $field_id,
          '%field_label' => $field_label,
          'link' => (new Link($this->t('Field settings'), $url))->toString(),
        ];
        $this->getLogger()
          ->warning('The field %field_label (%field_id) on index %index is missing view mode configuration for some datasources or bundles. Please review (and re-save) the field settings.', $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($this->index->getFields(), NULL, 'rendered_item');
    foreach ($fields as $field) {
      $view_modes = $field->getConfiguration()['view_mode'];
      foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
        if (($entity_type_id = $datasource->getEntityTypeId()) && !empty($view_modes[$datasource_id])) {
          foreach ($view_modes[$datasource_id] as $view_mode) {
            if ($view_mode) {
              /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode_entity */
              $view_mode_entity = EntityViewMode::load($entity_type_id . '.' . $view_mode);
              if ($view_mode_entity) {
                $this->addDependency($view_mode_entity->getConfigDependencyKey(), $view_mode_entity->getConfigDependencyName());
              }
            }
          }
        }
      }
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // All dependencies of this processor are entity view modes, so we go
    // through all of the index's fields using our property and remove the
    // settings for all datasources or bundles which were set to one of the
    // removed view modes. This will always result in the removal of all those
    // dependencies.
    // The code is highly similar to calculateDependencies(), only that we
    // remove the setting (if necessary) instead of adding a dependency.
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($this->index->getFields(), NULL, 'rendered_item');
    foreach ($fields as $field) {
      $field_config = $field->getConfiguration();
      $view_modes = $field_config['view_mode'];
      foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
        $entity_type_id = $datasource->getEntityTypeId();
        if (!$entity_type_id) {
          continue;
        }
        foreach ($view_modes[$datasource_id] ?? [] as $bundle => $view_mode_id) {
          if ($view_mode_id) {
            /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode */
            $view_mode = EntityViewMode::load($entity_type_id . '.' . $view_mode_id);
            if ($view_mode) {
              $dependency_key = $view_mode->getConfigDependencyKey();
              $dependency_name = $view_mode->getConfigDependencyName();
              if (!empty($dependencies[$dependency_key][$dependency_name])) {
                unset($view_modes[$datasource_id][$bundle]);
              }
            }
          }
        }
      }
      $field_config['view_mode'] = $view_modes;
      $field->setConfiguration($field_config);
    }

    return TRUE;
  }

}
