<?php

namespace JoeStewart\RoboDrupalVM\Task;

use Robo\Task\BaseTask;

abstract class Base extends BaseTask
{
    use \Robo\Common\ExecOneCommand;

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
          $contents = file_get_contents($this->getVagrantConfig($config_file));
          $this->configuration = \Symfony\Component\Yaml\Yaml::parse($contents);
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
      return $this->getProjectRoot() . '/web';
    }

    public function getTmpDir() {
      return $this->getProjectRoot() . '/tmp';
    }

    public function getDrush() {
      return $this->getVendorBin() . '/drush';
    }

    public function getComposerConfig( $setting) {
        $isPrinted = isset($this->isPrinted) ? $this->isPrinted : true;
        $this->isPrinted = false;
        $result = $this->executeCommand('composer config ' . $setting . ' --absolute --working-dir=' . $this->getProjectRoot());
        $value = $result->getMessage();
        $this->isPrinted = $isPrinted;
        return str_replace("\n", '', $value);
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
        return $this->getProjectRoot() . '/vendor/' . $this->drupalvm_package . '/' . $this->config_source_filename;
    }

    public function getDrupalVMConfigValue($variable_name) {
        return $this->configuration[$variable_name];
    }
}

