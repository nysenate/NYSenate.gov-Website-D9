<?php
namespace JoeStewart\Robo\Task\Vagrant;

use Robo\Task\BaseTask;
use Robo\Exception\TaskException;
use Symfony\Component\Process\ExecutableFinder;
use Robo\Contract\CommandInterface;

abstract class Base extends BaseTask implements CommandInterface
{
    use \Robo\Common\ExecOneCommand;

    protected $opts = [];
    protected $action = '';

    public function __construct($pathToVagrant = null)
    {
        if ($pathToVagrant) {
            $this->command = $pathToVagrant;
        } else {
            $finder = new ExecutableFinder();
            $this->command = $finder->find('vagrant');
        }
    }

    /**
     * adds `help` option to vagrant
     *
     * @return $this
     */
    public function help()
    {
        $this->option('--help');

        return $this;
    }

    public function getCommand()
    {
        return "{$this->command} {$this->action}{$this->arguments}";
    }
}
