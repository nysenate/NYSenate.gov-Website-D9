<?php

use  JoeStewart\RoboDrupalVM\Task\Vm;

class RoboFile extends \Robo\Tasks
{

    use \Mediacurrent\CiScripts\Task\loadTasks;
    use \Mediacurrent\CiScripts\Command\Console;
    use \Mediacurrent\CiScripts\Command\Drush;
    use \Mediacurrent\CiScripts\Command\Database;
    use \Mediacurrent\CiScripts\Command\Project;
    use \Mediacurrent\CiScripts\Command\Release;
    use \Mediacurrent\CiScripts\Command\Site;
    use \Mediacurrent\CiScripts\Command\Theme;
    use \Mediacurrent\CiScripts\Command\Vagrant;
    use \Boedah\Robo\Task\Drush\loadTasks;
    use \JoeStewart\RoboDrupalVM\Task\loadTasks;

    private $vm;
    private $configuration;

    private $drupalvm_package;

    public function __construct() {

      $this->drupalvm_package = 'mediacurrent/mis_vagrant';

      $this->vm = New Vm();
      $this->configuration = $this->vm->configuration;
    }

    /**
     * Run all PHPUnit unit tests.
     */
    public function testPhpUnitTests()
    {
        $this->stopOnFail(true);
        $this->testPhpUnitCustomModulesTests();
    }

    /**
     * Run PHPUnit Unit tests on custom modules.
     */
    public function testPhpUnitCustomModulesTests()
    {
        $this->taskPhpUnit($this->vm->getVendorBin() . '/phpunit')
            ->option('disallow-test-output')
            ->option('report-useless-tests')
            ->option('strict-coverage')
            ->option('-v')
            ->option('-d error_reporting=-1')
            ->configFile($this->vm->getProjectRoot() . '/tests/phpunit/phpunit.xml')
            ->arg($this->vm->getWebRoot() . '/modules/custom')
            ->run();
    }

    /**
     * Run tests on changes to custom modules.
     *
     * Use during development to continuously run tests for any change in
     * web/modules/custom.
     */
    public function watchCustomModules()
    {
        $this->taskWatch()->monitor($this->vm->getWebRoot() . '/modules/custom', function () {
            $this->testPhpUnitCustomModulesTests();
        })->run();
    }
}
