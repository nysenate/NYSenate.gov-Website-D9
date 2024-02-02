<?php

use JoeStewart\RoboDrupalVM\Task\Vm;
use Symfony\Component\Console\Input\InputOption;

class RoboFile extends \Robo\Tasks
{

    use \Mediacurrent\CiScripts\Task\loadTasks;
    use \Mediacurrent\CiScripts\Command\Console;
    use \Mediacurrent\CiScripts\Command\Ddev;
    use \Mediacurrent\CiScripts\Command\Drush;
    use \Mediacurrent\CiScripts\Command\Database;
    use \Mediacurrent\CiScripts\Command\Project;
    use \Mediacurrent\CiScripts\Command\Release;
    use \Mediacurrent\CiScripts\Command\Site;
    use \Mediacurrent\CiScripts\Command\Theme;
    use \Mediacurrent\CiScripts\Command\Vagrant;
    use \JoeStewart\RoboDrupalVM\Task\loadTasks;

    private $vm;
    private $configuration;

    private $drupalvm_package;

    public function __construct()
    {

        $this->drupalvm_package = 'mediacurrent/mis_vagrant';

        $this->vm = new Vm();
        $this->configuration = $this->vm->configuration;
    }

    /**
     * Run PHPUnit tests.
     * @option $filter Optionally Filter which tests to run.
     * @option $config_file Optionally specify the config file ( Defaults to "tests/phpunit/phpunit.xml").
     * @option $test_dir Optionally specify the test directory ( Defaults to "web/modules/custom").
     */
    public function testPhpunitTests(
        $opts = [
        'config_file' => InputOption::VALUE_REQUIRED,
        'test_dir' => InputOption::VALUE_REQUIRED,
        'filter' => InputOption::VALUE_REQUIRED,
        ]
    ) {

        $config_file = 'tests/phpunit/phpunit.xml';
        if (!empty($opts['config_file'])) {
            $config_file = $opts['config_file'];
        }

        $test_dir = 'web/modules/custom';
        if (!empty($opts['test_dir'])) {
            $test_dir = $opts['test_dir'];
        }

        if (empty($opts['filter'])) {
            $opts['filter'] = 'Unit';
        }

        $this->stopOnFail(true);
        $phpunit = $this->_getProjectRoot() . '/vendor/bin/phpunit';
        $this->taskPhpUnit($phpunit)
        ->option('disallow-test-output')
        ->option('strict-coverage')
        ->option('-v')
        ->option('-d error_reporting=-1')
        ->configFile($this->_getProjectRoot() . '/' . $config_file)
        ->arg($this->_getProjectRoot() . '/' . $test_dir)
        ->filter($opts['filter'])
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
        $this->taskWatch()->monitor($this->_getProjectRoot() . '/web//modules/custom', function () {
            $this->testPhpUnitTests();
        })->run();
    }

    public function _getProjectRoot($project_root = null)
    {
        if (!$project_root) {
            $project_root = __DIR__ . '/../';
        }
        return realpath($project_root);
    }
}
