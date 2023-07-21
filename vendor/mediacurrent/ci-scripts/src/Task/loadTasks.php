<?php
namespace Mediacurrent\CiScripts\Task;

trait loadTasks
{

    /**
     * @return Console
     */
    protected function taskConsole()
    {
        return $this->task(Console::class);
    }

    /**
     * @return DatabaseImport
     */
    protected function taskDatabaseImport()
    {
        return $this->task(DatabaseImport::class);
    }

     /**
     * @return Ddev
     */
    protected function taskDdev()
    {
        return $this->task(Ddev::class);
    }

    /**
     * @return Drush
     */
    protected function taskDrush()
    {
        return $this->task(Drush::class);
    }

    /**
     * @return ProjectInit
     */
    protected function taskProjectInit()
    {
        return $this->task(ProjectInit::class);
    }

    /**
     * @return ReleaseBuild
     */
    protected function taskReleaseBuild()
    {
        return $this->task(ReleaseBuild::class);
    }

    /**
     * @return ReleaseDeploy
     */
    protected function taskReleaseDeploy()
    {
        return $this->task(ReleaseDeploy::class);
    }

    /**
     * @return SiteBuild
     */
    protected function taskSiteBuild()
    {
        return $this->task(SiteBuild::class);
    }

    /**
     * @return SiteInstall
     */
    protected function taskSiteInstall()
    {
        return $this->task(SiteInstall::class);
    }

    /**
     * @return SiteTest
     */
    protected function taskSiteTest()
    {
        return $this->task(SiteTest::class);
    }

    /**
     * @return SiteUpdate
     */
    protected function taskSiteUpdate()
    {
        return $this->task(SiteUpdate::class);
    }

    /**
     * @return Theme
     */
    protected function taskThemeBuild()
    {
        return $this->task(Theme::class);
    }

   /**
     * @return Theme
     */
    protected function taskThemeCompile()
    {
        return $this->task(Theme::class);
    }

    /**
     * @return Theme
     */
    protected function taskThemeStyleGuide()
    {
        return $this->task(Theme::class);
    }

    /**
     * @return Theme
     */
    protected function taskThemeWatch()
    {
        return $this->task(Theme::class);
    }

    /**
     * @return VagrantCheck
     */
    protected function taskVagrantCheck()
    {
        return $this->task(VagrantCheck::class);
    }
}
