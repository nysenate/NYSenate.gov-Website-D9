<?php

namespace Drupal\devel\Drush\Commands;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Consolidation\SiteProcess\Util\Escape;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Commands\pm\PmCommands;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use Drush\Exec\ExecTrait;
use Drush\Utils\StringUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DevelCommands extends DrushCommands {
  use AutowireTrait;
  use ExecTrait;

  const REINSTALL = 'devel:reinstall';
  const HOOK = 'devel:hook';
  const EVENT = 'devel:event';
  const TOKEN = 'devel:token';
  const UUID = 'devel:uuid';
  const SERVICES = 'devel:services';

  /**
   * Constructs a new DevelCommands object.
   */
  public function __construct(
    protected Token $token,
    protected EventDispatcherInterface $eventDispatcher,
    protected ModuleHandlerInterface $moduleHandler,
    private SiteAliasManagerInterface $siteAliasManager,
  ) {
    parent::__construct();
  }

  /**
   * Gets the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The moduleHandler.
   */
  public function getModuleHandler(): ModuleHandlerInterface {
    return $this->moduleHandler;
  }

  /**
   * Gets the event dispatcher.
   *
   * @return mixed
   *   The eventDispatcher.
   */
  public function getEventDispatcher(): EventDispatcherInterface {
    return $this->eventDispatcher;
  }

  /**
   * Gets the container.
   *
   * @return mixed
   *   The container.
   */
  public function getContainer(): ContainerInterface {
    return Drush::getContainer()->get('service_container');
  }

  /**
   * Gets the token.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token.
   */
  public function getToken(): Token {
    return $this->token;
  }

  /**
   * Uninstall, and Install modules.
   */
  #[CLI\Command(name: self::REINSTALL, aliases: ['dre', 'devel-reinstall'])]
  #[CLI\Argument(name: 'modules', description: 'A comma-separated list of module names.')]
  public function reinstall($modules): void {
    $modules = StringUtils::csvToArray($modules);
    $modules_str = implode(',', $modules);
    $process = $this->processManager()->drush($this->siteAliasManager->getSelf(), PmCommands::UNINSTALL, [$modules_str]);
    $process->mustRun();
    $process = $this->processManager()->drush($this->siteAliasManager->getSelf(), PmCommands::INSTALL, [$modules_str]);
    $process->mustRun();
  }

  /**
   * List implementations of a given hook and optionally edit one.
   */
  #[CLI\Command(name: self::HOOK, aliases: ['fnh', 'fn-hook', 'hook', 'devel-hook'])]
  #[CLI\Argument(name: 'hook', description: 'The name of the hook to explore.')]
  #[CLI\Argument(name: 'implementation', description: 'The name of the implementation to edit. Usually omitted')]
  #[CLI\Usage(name: 'devel:hook cron', description: 'List implementations of hook_cron().')]
  #[CLI\OptionsetGetEditor()]
  public function hook($hook, string $implementation): void {
    // Get implementations in the .install files as well.
    include_once './core/includes/install.inc';
    drupal_load_updates();
    $info = $this->codeLocate($implementation . "_$hook");
    $exec = self::getEditor('');
    $cmd = sprintf($exec, Escape::shellArg($info['file']));
    $process = $this->processManager()->shell($cmd);
    $process->setTty(TRUE);
    $process->mustRun();
  }

  /**
   * Asks the user to select a hook implementation.
   */
  #[CLI\Hook(type: HookManager::INTERACT, target: self::HOOK)]
  public function hookInteract(Input $input, Output $output): void {
    $hook_implementations = [];
    if (!$input->getArgument('implementation')) {
      foreach ($this->getModuleHandler()->getModuleList() as $key => $extension) {
        if ($this->getModuleHandler()->hasImplementations($input->getArgument('hook'), [$key])) {
          $hook_implementations[] = $key;
        }
      }
      if ($hook_implementations) {
        if (!$choice = $this->io()->choice('Enter the number of the hook implementation you wish to view.', array_combine($hook_implementations, $hook_implementations))) {
          throw new UserAbortException();
        }
        $input->setArgument('implementation', $choice);
      }
      else {
        throw new \Exception(dt('No implementations'));
      }
    }
  }

  /**
   * List implementations of a given event and optionally edit one.
   */
  #[CLI\Command(name: self::EVENT, aliases: ['fne', 'fn-event', 'event'])]
  #[CLI\Argument(name: 'event', description: 'The name of the event to explore. If omitted, a list of events is shown.')]
  #[CLI\Argument(name: 'implementation', description: 'The name of the implementation to show. Usually omitted.')]
  #[CLI\Usage(name: 'drush devel:event', description: 'Pick a Kernel event, then pick an implementation, and then view its source code')]
  #[CLI\Usage(name: 'devel-event kernel.terminate', description: 'Pick a terminate subscribers implementation and view its source code.')]
  public function event($event, $implementation): void {
    $info = $this->codeLocate($implementation);
    $exec = self::getEditor('');
    $cmd = sprintf($exec, Escape::shellArg($info['file']));
    $process = $this->processManager()->shell($cmd);
    $process->setTty(TRUE);
    $process->mustRun();
  }

  /**
   * Asks the user to select an event and the event's implementation.
   */
  #[CLI\Hook(type: HookManager::INTERACT, target: self::EVENT)]
  public function interactEvent(Input $input, Output $output): void {
    $event = $input->getArgument('event');
    if (!$event) {
      // @todo Expand this list.
      $events = [
        'kernel.controller',
        'kernel.exception',
        'kernel.request',
        'kernel.response',
        'kernel.terminate',
        'kernel.view',
      ];
      $events = array_combine($events, $events);
      if (!$event = $this->io()->choice('Enter the event you wish to explore.', $events)) {
        throw new UserAbortException();
      }
      $input->setArgument('event', $event);
    }
    if ($implementations = $this->eventDispatcher->getListeners($event)) {
      foreach ($implementations as $implementation) {
        $callable = $implementation[0]::class . '::' . $implementation[1];
        $choices[$callable] = $callable;
      }
      if (!$choice = $this->io()->choice('Enter the number of the implementation you wish to view.', $choices)) {
        throw new UserAbortException();
      }
      $input->setArgument('implementation', $choice);
    }
    else {
      throw new \Exception(dt('No implementations.'));
    }
  }

  /**
   * List available tokens.
   */
  #[CLI\Command(name: self::TOKEN, aliases: ['token', 'devel-token'])]
  #[CLI\FieldLabels(labels: ['group' => 'Group', 'token' => 'Token', 'name' => 'Name'])]
  #[CLI\DefaultTableFields(fields: ['group', 'token', 'name'])]
  public function token($options = ['format' => 'table']): RowsOfFields {
    $all = $this->getToken()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }

  /**
   * Generate a Universally Unique Identifier (UUID).
   */
  #[CLI\Command(name: self::UUID, aliases: ['uuid', 'devel-uuid'])]
  public function uuid(): string {
    $uuid = new Php();
    return $uuid->generate();
  }

  /**
   * Get source code line for specified function or method.
   */
  public function codeLocate($function_name): array {
    // Get implementations in the .install files as well.
    include_once './core/includes/install.inc';
    drupal_load_updates();

    if (!str_contains($function_name, '::')) {
      if (!function_exists($function_name)) {
        throw new \Exception(dt('Function not found'));
      }
      $reflect = new \ReflectionFunction($function_name);
    }
    else {
      [$class, $method] = explode('::', $function_name);
      if (!method_exists($class, $method)) {
        throw new \Exception(dt('Method not found'));
      }
      $reflect = new \ReflectionMethod($class, $method);
    }
    return [
      'file' => $reflect->getFileName(),
      'startline' => $reflect->getStartLine(),
      'endline' => $reflect->getEndLine(),
    ];

  }

  /**
   * Get a list of available container services.
   */
  #[CLI\Command(name: self::SERVICES, aliases: ['devel-container-services', 'dcs', 'devel-services'])]
  #[CLI\Argument(name: 'prefix', description: 'Optional prefix to filter the service list by.')]
  #[CLI\Usage(name: 'drush devel-services', description: 'Gets a list of all available container services')]
  #[CLI\Usage(name: 'drush dcs plugin.manager', description: 'Get all services containing "plugin.manager"')]
  public function services($prefix = NULL, array $options = ['format' => 'yaml']): array {
    $container = $this->getContainer();

    // Get a list of all available service IDs.
    $services = $container->getServiceIds();

    // If there is a prefix, try to find matches.
    if (isset($prefix)) {
      $services = preg_grep("/$prefix/", $services);
    }

    if (empty($services)) {
      throw new \Exception(dt('No container services found.'));
    }

    sort($services);
    return $services;
  }

}
