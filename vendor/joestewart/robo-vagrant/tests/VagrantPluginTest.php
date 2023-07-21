<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class VagrantPluginTest extends \PHPUnit\Framework\TestCase implements ContainerAwareInterface
{
    use \JoeStewart\Robo\Task\Vagrant\loadTasks;
    use TaskAccessor;
    use ContainerAwareTrait;

    // Set up the Robo container so that we can create tasks in our tests.
    function setup(): void
    {
        $container = Robo::createDefaultContainer(null, new NullOutput());
        $this->setContainer($container);
    }

    // Scaffold the collection builder
    public function collectionBuilder()
    {
        $emptyRobofile = new \Robo\Tasks;
        return CollectionBuilder::create($this->getContainer(), $emptyRobofile);
    }

    public function testVagrantPluginInstallCommand()
    {
        $command = $this->taskVagrantPlugin('/usr/bin/vagrant')
            ->install()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant plugin install name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantPluginLicenseCommand()
    {
        $command = $this->taskVagrantPlugin('/usr/bin/vagrant')
            ->license()
            ->getCommand();
        $expected = '/usr/bin/vagrant plugin license';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantPluginListCommand()
    {
        $command = $this->taskVagrantPlugin('/usr/bin/vagrant')
            ->listPlugins()
            ->getCommand();
        $expected = '/usr/bin/vagrant plugin list';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantPluginUninstallCommand()
    {
        $command = $this->taskVagrantPlugin('/usr/bin/vagrant')
            ->uninstall()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant plugin uninstall name';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantPluginUpdateCommand()
    {
        $command = $this->taskVagrantPlugin('/usr/bin/vagrant')
            ->update()
            ->arg('name')
            ->getCommand();
        $expected = '/usr/bin/vagrant plugin update name';
        $this->assertEquals($expected, $command);
    }
}
