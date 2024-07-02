# About

This document is about how to maintain and extend the Drupal 7 database fixture
and the related file assets that are used by Media Migration's PHPUnit tests.
You will be able to easily create a Drupal 7 instance that incorporates the
database and file fixtures, add the required modifications for the new test
cases and re-export the database and the new files back to code.


## Requirements

- Alias for the source database's Drupal 7 instance: `drupal7-media.localhost`.

- A Drupal 8|9 codebase for the database import-export script.

- (Recommended, but optional) Drush `8`. Drush 8 was the last version compatible
  with Drupal 7.


## Create the Drupal 7 instance that represents the db and file fixtures

### Codebase

- Change directory to the Media Migration module's root and build the source
  Drupal 7 project:

  `drush make ./tests/fixtures/d7/drupal7-media.make.yml ./tests/fixtures/d7/drupal7-media`

- Still from the Media Migration module's root, copy
  `./tests/fixtures/d7/drupal7-media/sites/default/default.settings.php` to
  `./tests/fixtures/d7/drupal7-media/sites/default/settings.php`, add some hash
  salt and define the database connection.

### File assets

- From the Media Migration module's root, copy
  `./tests/fixtures/sites/default/files` into
  `./tests/fixtures/d7/drupal7-media/sites/default/`.

  `cp -r ./tests/fixtures/sites/default/files ./tests/fixtures/d7/drupal7-media/sites/default/`


### Database

The next steps are almost the same as in the
[Generating database fixtures for D8 Migrate tests][1] documentation and require
a Drupal 8|9 instance. You can skip the _Set up Drupal 6 / 7 installation that
uses your test database_ section since it is replaced by the make files
we provide.

- If it does not exist, create a new database with name `drupal7_media` for the
  media based source, or `drupal7_nomedia` for the Drupal 7 source which does
  not have media and file_entity modules, only plain file and image fields.

  - `mysql -u <user> -p -e "CREATE DATABASE drupal7_media DEFAULT CHARACTER SET = 'utf8' DEFAULT COLLATE 'utf8_general_ci';"`
  - `mysql -u <user> -p -e "grant ALL privileges on drupal7_media.* to 'devuser'@'localhost';"`

  Or:

  - `mysql -u <user> -p -e "CREATE DATABASE drupal7_nomedia DEFAULT CHARACTER SET = 'utf8' DEFAULT COLLATE 'utf8_general_ci';"`
  - `mysql -u <user> -p -e "grant ALL privileges on drupal7_nomedia.* to 'devuser'@'localhost';"`

- Make sure that the `drupal7_media[|drupal7_nomedia]` DB is empty.

- [Define a database connection to your empty database][2] in your Drupal 8|9
  `settings.php`:
  ```
    $databases['fixture_connection']['default'] = array (
      'database' => 'drupal7_media',
      // 'database' => 'drupal7_nomedia',
      'username' => 'devuser',
      'password' => 'devpassword',
      'prefix' => '',
      'host' => 'localhost',
      'port' => '3306',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
    );
    ```

- Import the Drupal 7 media fixture into this database.
  From your Drupal 8|9 project root, run:
  `php core/scripts/db-tools.php import --database fixture_connection [path-to-media_migration]/tests/fixtures/drupal7_media.php`
  or
  `php core/scripts/db-tools.php import --database fixture_connection [path-to-media_migration]/tests/fixtures/drupal7_nomedia.php`

- [Add a row for uid 0 to {users} table manually][3].
  - `drush -u 1 sql-query "INSERT INTO users (name, pass, mail, theme, signature, language, init, timezone) VALUES ('', '', '', '', '', '', '', '')"`
  - `drush -u 1 sql-query "UPDATE users SET uid = 0 WHERE name = ''"`


##  Log in to your test site and make the necessary changes

These necessary changes could be for instance:
- Someone found a bug that can be reproduced with a well-prepared node body
  copy with special embed tokens, thus while we fix it, we also are able to
  create a test:

  In this case, you need to add a new node with the body text that causes the
  error.

- You want to provide migration path for a special media provider.

  In this case, you might add some media entity of this special type and test
  that they are migrated properly.

- The Drupal 7 core, or one of the contrib modules that the Drupal 7 fixture
  uses got a new release, and we have to update the fixture database (and even
  the drush make file).

  In this case, after that the corresponding component was updated, we have to
  run the database updates.

Admin (uid = 1) user's credentials:

- Username is `user`
- Password is `password`

Editor (uid = 2) user's credentials:

- Username is `editor`
- Password is `password`

If you need to add or update a contrib module, or update core: please don't
forget to update the drush make file as well!


## Export the modifications you made

- Export the Drupal 7 database to the fixture file:
  From your Drupal 8|9 project root, run:
  `php core/scripts/db-tools.php dump --database fixture_connection > [path-to-media_migration]/tests/fixtures/drupal7_media.php`
  or
  `php core/scripts/db-tools.php dump --database fixture_connection > [path-to-media_migration]/tests/fixtures/drupal7_nomedia.php`

- Copy `./tests/fixtures/d7/drupal7-media/sites/default/files` back into
  `./tests/fixtures/sites/default`. At the Media Migration module root:

  `cp -r tests/fixtures/d7/drupal7-media/sites/default/files tests/fixtures/sites/default/`

- You can remove the untracked and ignored files if you think so:

  `git clean -fdx ./tests/fixtures/`


[1]: https://www.drupal.org/node/2583227
[2]: https://www.drupal.org/node/2583227#s-importing-data-from-the-fixture-to-your-testdatabase
[3]: https://www.drupal.org/node/1029506
