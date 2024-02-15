<?php
namespace JoeStewart\Robo\Task\Vagrant;

trait loadTasks 
{

    /**
     * @param null $pathToVagrant
     * @return Box
     */
    protected function taskVagrantBox($pathToVagrant = null) {
        return $this->task(Box::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Destroy
     */
    protected function taskVagrantDestroy($pathToVagrant = null) {
        return $this->task(Destroy::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return GlobalStatus
     */
    protected function taskVagrantGlobalStatus($pathToVagrant = null) {
        return $this->task(GlobalStatus::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Halt
     */
    protected function taskVagrantHalt($pathToVagrant = null) {
        return $this->task(Halt::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Help
     */
    protected function taskVagrantHelp($pathToVagrant = null) {
        return $this->task(Help::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Init
     */
    protected function taskVagrantInit($pathToVagrant = null) {
        return $this->task(Init::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return ListCommands
     */
    protected function taskVagrantListCommands($pathToVagrant = null) {
        return $this->task(ListCommands::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Package
     */
    protected function taskVagrantPackage($pathToVagrant = null) {
        return $this->task(Package::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Plugin
     */
    protected function taskVagrantPlugin($pathToVagrant = null) {
        return $this->task(Plugin::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Port
     */
    protected function taskVagrantPort($pathToVagrant = null) {
        return $this->task(Port::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Provision
     */
    protected function taskVagrantProvision($pathToVagrant = null) {
        return $this->task(Provision::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Reload
     */
    protected function taskVagrantReload($pathToVagrant = null) {
        return $this->task(Reload::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Resume
     */
    protected function taskVagrantResume($pathToVagrant = null) {
        return $this->task(Resume::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Ssh
     */
    protected function taskVagrantSsh($pathToVagrant = null) {
        return $this->task(Ssh::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return SshConfig
     */
    protected function taskVagrantSshConfig($pathToVagrant = null) {
        return $this->task(SshConfig::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return SshConfig
     */
    protected function taskVagrantSnapshot($pathToVagrant = null) {
        return $this->task(Snapshot::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Status
     */
    protected function taskVagrantStatus($pathToVagrant = null) {
        return $this->task(Status::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Suspend
     */
    protected function taskVagrantSuspend($pathToVagrant = null) {
        return $this->task(Suspend::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Up
     */
    protected function taskVagrantUp($pathToVagrant = null) {
        return $this->task(Up::class, $pathToVagrant);
    }

    /**
     * @param null $pathToVagrant
     * @return Version
     */
    protected function taskVagrantVersion($pathToVagrant = null) {
        return $this->task(Version::class, $pathToVagrant);
    }

}
