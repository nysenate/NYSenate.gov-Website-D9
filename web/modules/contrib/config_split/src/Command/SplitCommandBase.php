<?php

namespace Drupal\config_split\Command;

use Drupal\config_split\ConfigSplitCliService;
use Drupal\Console\Core\Command\Command;

/**
 * Class SplitCommandBase for shared functionality.
 *
 * @internal
 */
abstract class SplitCommandBase extends Command {

  /**
   * The cli service doing all the work.
   *
   * @var \Drupal\config_split\ConfigSplitCliService
   */
  protected $cliService;

  /**
   * Constructor with cli service injection.
   *
   * @param \Drupal\config_split\ConfigSplitCliService $cliService
   *   The cli service to delegate all actions to.
   */
  public function __construct(ConfigSplitCliService $cliService) {
    parent::__construct();
    $this->cliService = $cliService;
  }

  /**
   * The translation function akin to Drupal's t().
   *
   * @param string $string
   *   The string to translate.
   * @param array $args
   *   The replacements.
   *
   * @return string
   *   The translated string.
   */
  public function t($string, array $args = []) {
    $c = 'commands.' . strtr($this->getName(), [':' => '.']) . '.messages.';
    $translations = [
      'Configuration successfully exported.' => $c . 'success',
    ];
    if (array_key_exists($string, $translations)) {
      $string = $translations[$string];
    }

    // Translate with consoles translations.
    return strtr($this->trans($string), $args);
  }

}
