<?php

namespace Drupal\nys_sendgrid;

use Drupal\nys_sendgrid\Api\Template;

/**
 * Provides access to templates through the Sendgrid API.
 */
abstract class TemplatesManager {

  /**
   * Array of Template objects, keyed by template ID.
   *
   * @var array
   */
  protected static array $templates = [];

  /**
   * Simple getter for configured template assignments.
   *
   * @return array
   *   Array of configured assignments, as [message_id => template_id, ...]
   */
  public static function getTemplateAssignments(): array {
    static $templates = [];

    if (!count($templates)) {
      $config = \Drupal::config('nys_sendgrid.settings');

      // Get the assignments.  Add default entry, if configured.
      $templates = $config->get('template_assignments') ?? [];
      if (($default = $config->get('default_template')) && ($templates[$default] ?? FALSE)) {
        $templates['_default_'] = $templates[$default];
      }
    }

    return $templates;
  }

  /**
   * Gets a template by ID.
   */
  public static function getTemplate(string $id): ?Template {
    $all_templates = static::getTemplates();
    if ($id && ($all_templates[$id] ?? FALSE)) {
      return $all_templates[$id];
    }
    else {
      return NULL;
    }
  }

  /**
   * Gets a template by name.
   */
  public static function getTemplateByName(string $name): ?Template {
    $search = array_filter(
          static::getTemplates(),
          function ($v) use ($name) {
              return $v->getName() == $name;
          }
      );
    return current($search) ?: NULL;
  }

  /**
   * Retrieves a list of all available templates.
   *
   * If static/cache is not populated, calls the API to get a new list.
   *
   * @param bool $refresh
   *   TRUE to force a refresh of the cached list.
   *
   * @return array
   *   In the form ['template title' => 'template id', ...]
   */
  public static function getTemplates(bool $refresh = FALSE): array {
    static $slack_sent = FALSE;

    // If forced refresh, or no templates in cache, call the API.
    if ($refresh || !(count($templates = static::getCachedTemplates()))) {

      $templates = [];

      /**
       * @var \SendGrid $sg
       */
      $sg = \Drupal::service('nys_sendgrid_client');
      $response = $sg->client->templates()
        ->get(NULL, ['generations' => 'legacy,dynamic']);

      // If response is good, parse it.
      if ($response->statusCode() == '200') {
        $list = (json_decode($response->body())->templates) ?? [];
        foreach ($list as $val) {
          try {
            $templates[$val->id] = new Template($val->id, $val->name, $val->generation);
          }
          catch (\Throwable $e) {
            \Drupal::logger('nys_sendgrid')
              ->error(
                      "Failed to create a Template from API response", [
                        '%template' => $val,
                        '%message' => $e->getMessage(),
                      ]
                  );
          }
        }
      }
      // Otherwise, report the error.
      else {
        $msg = 'Call to SendGrid templates() failed.';
        \Drupal::logger('nys_sendgrid')
          ->error($msg, ['%response' => $response->body()]);

        // Send to slack.
        if (!$slack_sent) {
          /**
           * @var \Drupal\nys_slack\Service\Slack $slack
           */
          $slack = \Drupal::getContainer()->get('slack_messaging');
          $slack->setTitle($msg)
            ->addAttachment("env\n" . ($_ENV['PANTHEON_ENVIRONMENT'] ?? 'n/a'))
            ->addAttachment("body\n" . var_export($response->body(), 1))
            ->send('SendGrid API returned status code ' . $response->statusCode());
          $slack_sent = TRUE;
        }
      }

      // Save the templates.
      \Drupal::cache()
        ->set('nys_sendgrid:templates', $templates, time() + 86400);
    }

    // Return the templates.
    return $templates;
  }

  /**
   * Checks cache for the list of templates.
   */
  public static function getCachedTemplates(): array {

    if (!count(static::$templates)) {
      $cache = \Drupal::cache()->get('nys_sendgrid:templates');
      static::$templates = $cache ? $cache->data : [];
    }

    return static::$templates;
  }

}
