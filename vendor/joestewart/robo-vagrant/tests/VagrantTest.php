<?php

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Output\NullOutput;
use Robo\TaskAccessor;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use PHPUnit\Framework\TestCase;

class VagrantTest extends \PHPUnit\Framework\TestCase implements ContainerAwareInterface
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

    public function testVagrantHelpArgCommand()
    {
        $command = $this->taskVagrantStatus('/usr/bin/vagrant')
            ->help()
            ->getCommand();
        $expected = '/usr/bin/vagrant status --help';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantDestroyCommand()
    {
        $command = $this->taskVagrantDestroy('/usr/bin/vagrant')
            ->force()
            ->getCommand();
        $expected = '/usr/bin/vagrant destroy --force';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantPackageCommand()
    {
        $command = $this->taskVagrantPackage('/usr/bin/vagrant')
            ->base('base_name')
            ->output('output_file')
            ->includefile('include_file')
            ->vagrantfile('vagrantfile_file')
            ->getCommand();
        $expected = '/usr/bin/vagrant package --base "base_name" --output "output_file" --include "include_file" --vagrantfile "vagrantfile_file"';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantPortCommand()
    {
        $command = $this->taskVagrantPort('/usr/bin/vagrant')
            ->machineReadable()
            ->guest('22')
            ->getCommand();
        $expected = '/usr/bin/vagrant port --machine-readable --guest 22';
        $this->assertEquals($expected, $command);
    }

    public function testVagrantSshCommand()
    {
        $command = $this->taskVagrantSsh('/usr/bin/vagrant')
            ->plain()
            ->command('ls -l')
            ->getCommand();
        $expected = '/usr/bin/vagrant ssh --plain --command "ls -l"';
        $this->assertEquals($expected, $command);
    }
}