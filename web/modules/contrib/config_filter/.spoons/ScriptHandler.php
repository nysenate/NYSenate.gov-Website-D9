<?php

namespace Spoons;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

/**
 * A Composer script handler.
 */
class ScriptHandler {

  /**
   * Create a web/modules/[SLUG] dir and symlink all project files into it.
   *
   * @param \Composer\Script\Event $event
   *   A Composer package event.
   */
  public static function createSymlinks(Event $event) {
    $full_name = $event->getComposer()->getPackage()->getName();
    [, $project_name] = explode('/', $full_name);
    $cmd = "rm -rf web/modules/custom/$project_name && mkdir -p web/modules/custom/$project_name";
    $process = new Process($cmd);
    $process->mustRun();
    $cmd = 'find ../../../.. -maxdepth 1 ! -name .git ! -name web ! -name vendor ! -name .idea -print | while read file; do ln -s "$file" .; done';
    $process = new Process($cmd, "web/modules/custom/$project_name");
    $process->mustRun();
  }

}
