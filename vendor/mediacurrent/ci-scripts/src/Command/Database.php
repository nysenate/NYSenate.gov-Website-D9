<?php

namespace Mediacurrent\CiScripts\Command;

trait Database
{

    /**
     * Database Import command.
     *
     * database:import runs drush sqlc < ./latest.sql
     *
     * @param  string $database_file Relative subdirectory path to source database file.
     *
     * @return object Result
     */
    public function databaseImport($database_file = 'latest.sql')
    {
        $this->taskDatabaseImport()
            ->databaseFile($database_file)
            ->run();
    }
}
