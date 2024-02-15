<?php

namespace Mediacurrent\CiScripts\Task;


use Robo\Contract\TaskInterface;
use Robo\Task\BaseTask;
use Robo\Contract\BuilderAwareInterface;
use Robo\Common\BuilderAwareTrait;
use Robo\Contract\CommandInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Config;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;

abstract class Base extends BaseTask implements BuilderAwareInterface, CommandInterface
{
    use \Robo\Common\ExecOneCommand;
    use BuilderAwareTrait;
    use ConfigAwareTrait;

    private $vendor_dir;
    private $vendor_bin;
    private $config_dir;
    private $config_filename;
    private $config_source_filename;
    private $drupalvm_package;

    /**
     * @var array
     */
    public $configuration = array();

    public function __construct($config_file = null) {

        $this->drupalvmPackage('geerlingguy/drupal-vm');
        $this->configDir('config');
        $this->configFilename('config.yml');
        $this->configSourceFilename('default.config.yml');

        if (file_exists($this->getVagrantConfig($config_file))) {
            $this->loadDrupalVMConfiguration($config_file);
            Robo::loadConfiguration([$this->getVagrantConfig($config_file)]);
        }
    }

    public function drupalvmPackage($drupalvm_package) {
        $this->drupalvm_package = $drupalvm_package;

        return $this;
    }

    public function configDir($config_dir) {
        $this->config_dir = $config_dir;

        return $this;
    }

    public function configFilename($config_filename) {
        $this->config_filename = $config_filename;

        return $this;
    }

    public function configSourceFilename($config_source_filename) {
        $this->config_source_filename = $config_source_filename;

        return $this;
    }

    public function getProjectRoot($project_root =  null) {
        if(!$project_root) {
            $project_root = __DIR__ . '/../../../../../';
        }
        return realpath($project_root);
    }

    public function getComposerJson() {
      return $this->getProjectRoot() . '/composer.json';
    }

    public function getVendorDir() {
      if(!$this->vendor_dir) {
        $this->vendor_dir = $this->getComposerConfig( 'vendor-dir');
      }
      return $this->vendor_dir;
    }

    public function getVendorBin() {
      if(!$this->vendor_bin) {
        $this->vendor_bin = $this->getComposerConfig( 'bin-dir');
      }
      return $this->vendor_bin;
    }

    public function getWebRoot() {
        $webroot = (isset($this->configuration['drupal_webroot'])) ? $this->configuration['drupal_webroot'] : 'web';
        return $this->getProjectRoot() . '/' . $webroot;
    }

    public function getTmpDir() {
      return $this->getProjectRoot() . '/tmp';
    }

    public function getDrush() {
      return $this->getVendorBin() . '/drush';
    }

    public function getComposerConfig( $setting) {
        $result = shell_exec('composer config ' . $setting . ' --absolute --working-dir=' . $this->getProjectRoot());
        return str_replace("\n", '', $result);
    }

    public function getVagrantConfig() {
        return $this->getProjectRoot() . '/' . $this->getVagrantDir(). '/' . $this->getVagrantConfigFilename();
    }

    public function getVagrantDir() {
        return $this->config_dir;
    }

    public function getVagrantConfigFilename() {
        return $this->config_filename;
    }

    public function getVagrantSourceConfig() {
        $config_filename = '/mediacurrent/ci-scripts/files/config.yml.j2';
        if ($this->useVagrant()) {
            $config_filename = '/mediacurrent/ci-scripts/files/config.vagrant.yml.j2';
        }
        return $this->getVendorDir() . $config_filename;
    }

    public function getDrupalVMConfigValue($variable_name) {
        return $this->configuration[$variable_name];
    }

    public function useVagrant() {

        $result = shell_exec('command -v vagrant');
        return $result;
    }

    public function loadDrupalVMConfiguration($config_file = null) {
        $contents = file_get_contents($this->getVagrantConfig($config_file));
        $this->configuration = \Symfony\Component\Yaml\Yaml::parse($contents);
    }

    public function getCommand() {

    }
}

