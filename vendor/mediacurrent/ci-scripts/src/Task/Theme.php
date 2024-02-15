<?php

namespace Mediacurrent\CiScripts\Task;

use Robo\Result;

class Theme extends \Mediacurrent\CiScripts\Task\Base
{
    use \Robo\Task\Base\Tasks;
    use \Robo\Task\Npm\Tasks;
    use \Mediacurrent\CiScripts\Task\loadTasks;

    protected $exitCode = 0;
    protected $noNvm = true;
    protected $pathToTheme;

    public function __construct()
    {
        $this->startTimer();
        parent::__construct();
    }

    public function getCommand($command = '') {

        if(!$this->noNvm) {
            $command = "/bin/bash -l -c 'nvm install && $command'";
        }

        return $command;
    }

    public function npmRunBuild()
    {

        $command = $this->getCommand('npm run build');
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmRunCompile()
    {

        $command = 'npm run compile';
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function nvmInstall()
    {

        $this->noNvm = false;
        $command = 'nvm install';
        $result = $this->collectionBuilder()->taskExec("/bin/bash -l -c '$command'")
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function nvmUse()
    {

        $command = 'nvm use';
        $result = $this->collectionBuilder()->taskExec("/bin/bash -l -c '$command'")
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmCi()
    {

        $command = $this->getCommand('npm ci');
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmInstall()
    {

        $command = $this->getCommand('npm install');
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function npmRunStyleGuide()
    {

        $command = $this->getCommand('npm run styleguide');
        $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();
        return $this;
    }

    public function npmRunWatch()
    {

        $command = $this->getCommand('npm run watch');
        $result = $this->collectionBuilder()->taskExec($command)
            ->dir($this->pathToTheme)
            ->run();

        if (!$result->wasSuccessful()) {
            exit(1);
        }
        return $this;
    }

    public function themeDirectory($pathToTheme = null)
    {
        if (!$pathToTheme) {
            $drush = 'drush --uri=' . $this->configuration['vagrant_hostname'] . ' --root=' . $this->getWebRoot() . ' ';
            $drush_command = 'php-eval "return \Drupal::theme()->getActiveTheme()->getPath();"';
            $command = $drush . $drush_command;
            $result = shell_exec($command);
            $active_theme = str_replace(array( "'", "\n"), '', $result);
            $this->pathToTheme = $this->getWebRoot() . '/' . $active_theme;
        } else {
            $this->pathToTheme = $pathToTheme;
        }

        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->stopTimer();
        return new Result(
            $this,
            $this->exitCode,
            'Theme',
            ['time' => $this->getExecutionTime()]
        );
    }
}
