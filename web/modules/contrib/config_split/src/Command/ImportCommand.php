<?php

namespace Drupal\config_split\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND

/**
 * Class ImportCommand.
 *
 * @package Drupal\config_split
 *
 * @DrupalCommand (
 *     extension="config_split",
 *     extensionType="module"
 * )
 */
class ImportCommand extends SplitCommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config_split:import')
      ->setAliases(['csim'])
      ->setDescription($this->trans('commands.config_split.import.description'))
      ->addOption('split', NULL, InputOption::VALUE_OPTIONAL);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    try {
      // Make the magic happen.
      $this->cliService->ioImport($input->getOption('split'), $this->getIo(), [$this, 't'], $input->getOption('yes'));
    }
    catch (\Exception $e) {
      $this->getIo()->error($e->getMessage());
    }
  }

}
