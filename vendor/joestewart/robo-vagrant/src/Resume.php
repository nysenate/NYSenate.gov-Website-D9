<?php
namespace JoeStewart\Robo\Task\Vagrant;

/**
 * Vagrant Resume
 *
 * ```php
 * <?php
 * // simple execution
 * $this->taskVagrantResume()->run();
 *
 * // with custom path
 * $this->taskVagrantResume('path/to/my/vagrant')
 *      ->run();
 * ?>
 * ```
 */
class Resume extends Base
{
    protected $action = 'resume';

    public function run()
    {
        $this->printTaskInfo('vagrant resume' . $this->arguments);
        return $this->executeCommand($this->getCommand());
    }
}
