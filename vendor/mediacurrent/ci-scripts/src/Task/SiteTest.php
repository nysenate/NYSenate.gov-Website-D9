<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;
use Robo\Common\ResourceExistenceChecker;

class SiteTest extends \Mediacurrent\CiScripts\Task\Base
{
    use ResourceExistenceChecker;
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Testing\Tasks;

    protected $exit_code = 0;
    protected $test_argument;
    protected $test_options;

    public function testArgument($test_argument = null)
    {
        $this->test_argument = $test_argument;

        return $this;
    }

    public function testOptions($test_options = null)
    {
        $this->test_options = $test_options;

        return $this;
    }

    public function behat($test_dir = null, $uri = null)
    {

        if (!$test_dir) {
            $test_dir = $this->getProjectRoot() . '/tests/behat';
        }

        if (!is_dir($test_dir)) {
            $this->printTaskInfo($test_dir . ' not found. Skipping behat tests');
            $this->exit_code = 1;
            return $this;
        }

        chdir($test_dir);
        $cmd = $this->getVendorBin() . '/behat';

        $result = $this->collectionBuilder()->taskExec($cmd)->run();
        $this->exit_code = $result->getExitCode();

        return $this;
    }

    public function pa11y($uri = null)
    {

        if (!$uri) {
            $uri = 'http://' . $this->configuration['vagrant_hostname'];
        }

        $cmd = 'pa11y --standard=WCAG2AA --ignore=WCAG2AA.Principle1.Guideline1_4.1_4_3.G18.Fail ' . $uri;

        $result = $this->collectionBuilder()->taskExec($cmd)->run();
        $this->exit_code = $result->getExitCode();

        return $this;
    }

    public function phpcs($test_dir = null)
    {

        if (!$test_dir) {
            $test_dir = $this->getWebRoot() . '/modules/custom';
        }

        if (!is_dir($test_dir)) {
            $this->printTaskInfo($test_dir . ' not found. Skipping code sniffer tests');
            return $this;
        }

        $phpcs = $this->getVendorBin() . '/phpcs';

        $cmd = $phpcs . ' --standard=' . $this->getVendorDir() . '/drupal/coder/coder_sniffer/Drupal --extensions=php,module,inc,install,test,profile,theme ' . $test_dir;

        $result = $this->collectionBuilder()->taskExec($cmd)->run();
        $this->exit_code = $result->getExitCode();

        return $this;
    }

    public function phpunit($test_dir = null)
    {

        if (!$test_dir) {
            $test_dir = $this->getWebRoot() . '/modules/custom';
        }

        $result = $this->collectionBuilder()->taskPHPUnit($this->getVendorBin() . '/phpunit')
          ->option('disallow-test-output')
          ->option('strict-coverage')
          ->option('-v')
          ->option('-d error_reporting=-1')
          ->configFile($this->getProjectRoot() . '/tests/phpunit/phpunit.xml')
          ->arg($test_dir)
          ->run();
        $this->exit_code = $result->getExitCode();

        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->startTimer();

        foreach ($this->test_options as $option => $value) {
            if ($value && method_exists($this, $option)) {
                $this->{$option}($this->test_argument);
            }
        }

        $this->stopTimer();
        return new Result(
            $this,
            $this->exit_code,
            'SiteInstall',
            ['time' => $this->getExecutionTime()]
        );
    }
}
