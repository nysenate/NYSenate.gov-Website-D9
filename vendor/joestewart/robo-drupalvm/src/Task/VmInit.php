<?php

namespace JoeStewart\RoboDrupalVM\Task;


use Robo\Result;
use Robo\Common\ResourceExistenceChecker;
use Robo\Common\Timer;
use Robo\Common\TaskIO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Common\BuilderAwareTrait;

class VmInit extends \JoeStewart\RoboDrupalVM\Task\Base implements BuilderAwareInterface
{
    use BuilderAwareTrait;
    use ResourceExistenceChecker;

    /**
     * Ensures config.yml is installed.
     *
     * @return $this
     */
    public function configFile($source_file = null, $dest_file = null)
    {
        if(!file_exists($this->getVagrantConfig())) {
            if(!$source_file) {
                $source_file = $this->getVagrantSourceConfig();
            }
            if(!$dest_file) {
                $dest_file = $this->getVagrantConfig();
            }
            $this->collectionBuilder()->taskFileSystemStack()
                ->copy($source_file, $dest_file)
                ->run();
        }
        return $this;
    }

    /**
     * Ensures Vagrantfile is installed in project root.
     *
     * @return $this
     */
    public function vagrantFile($drupalvm_package = 'geerlingguy/drupal-vm')
    {
        if(!file_exists($this->getProjectRoot() . '/Vagrantfile')) {
           $text = <<<EOF
 # The absolute path to the root directory of the project. Both Drupal VM and
# the config file need to be contained within this path.
ENV['DRUPALVM_PROJECT_ROOT'] = "#{__dir__}"
# The relative path from the project root to the config directory where you
# placed your config.yml file.
ENV['DRUPALVM_CONFIG_DIR'] = "config"
# The relative path from the project root to the directory where Drupal VM is located.
ENV['DRUPALVM_DIR'] = "vendor/geerlingguy/drupal-vm"

# Load the real Vagrantfile
load "#{__dir__}/#{ENV['DRUPALVM_DIR']}/Vagrantfile"
EOF;
            if($drupalvm_package != 'geerlingguy/drupal-vm') {
                $text = str_replace('geerlingguy/drupal-vm', $drupalvm_package, $text);
            }
            $this->collectionBuilder()->taskWriteToFile($this->getProjectRoot() . '/Vagrantfile')
                ->text($text)
                ->run();
        }
        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->startTimer();
        $this->stopTimer();
        return new Result(
            $this,
            0,
            'VmInit',
            ['time' => $this->getExecutionTime()]
        );

    }
}
