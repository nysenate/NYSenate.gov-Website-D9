# About

This document is about how to maintain and extend the Drupal 7 database fixture
and the related file assets that are used by Drupal 7 Location → Drupal 9
migrations.

- ```
  drush make \
    ./tests/fixtures/d7/drupal7-location.make.yml \
    ./tests/fixtures/d7/location
  ```

- ```
  cp \
    ./tests/fixtures/d7/example.settings.php \
    ./tests/fixtures/d7/location/sites/default/settings.php
  ```

- When the source DB not exists:
  - ```
    mysql -u <user> -p -e \
      "CREATE DATABASE drupal7_location \
        DEFAULT CHARACTER SET = 'utf8' \
        DEFAULT COLLATE 'utf8_general_ci';"
    ```
  - ```
    mysql -u <user> -p -e \
      "grant ALL privileges on drupal7_location.* to 'devuser'@'localhost';"
    ```


### Database

The next steps are almost the same as in the
[Generating database fixtures for D8 Migrate tests][1] documentation and require
a Drupal 8|9 instance. You can skip the _Set up Drupal 6 / 7 installation that
uses your test database_ section since it is replaced by the make files we
provide.

- Make sure that the `drupal7_location` DB is empty.

- [Define a database connection to your empty database][2] in your Drupal 8|9
  `settings.php`:
  ```
  $databases['fixture_connection']['default'] = [
    'database' => 'drupal7_location',
    'username' => 'devuser',
    'password' => 'devpassword',
    'prefix' => '',
    'host' => 'localhost',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  ];
  ```

- Import the Drupal 7 Location fixture into this database.
  From your Drupal 8|9 project root, run:
  ```
  php core/scripts/db-tools.php import \
    --database fixture_connection \
    [path-to-location_migration]/tests/fixtures/d7/drupal7_location.php
  ```

- [Add a row for uid 0 to {users} table manually][3].
  - ```
    drush -u 1 sql-query \
      "INSERT INTO users \
        (name, pass, mail, theme, signature, language, init, timezone) \
        VALUES ('', '', '', '', '', '', '', '')"
    ```
  - ```
    drush -u 1 sql-query "UPDATE users SET uid = 0 WHERE name = ''"
    ```


##  Log in to your test site and make the necessary changes

These necessary changes could be for instance:
- Someone found a bug that can be reproduced with a well-prepared data
  structure.
- The Drupal 7 core, or one of the contrib modules that the Drupal 7 fixture
  uses got a new release, and we have to update the fixture database (and even
  the drush make file).

If you need to add or update a contrib module, or update core: please don't
forget to update the drush make file as well!

Admin (uid = 1) user's credentials:

- Username is `admin`
- Password is `password`

The authenticated (uid = 2) user's credentials:

- Username is `user`
- Password is `password`


## Export the modifications you made

- Export the Drupal 7 database to the fixture file. From your Drupal 8|9 project
  root, run:
  ```
  php core/scripts/db-tools.php dump \
    --database fixture_connection \
    > [path-to-location_migration]/tests/fixtures/d7/drupal7_location.php
  ```

- You might want to remove the untracked and ignored files if you think so:
  ```
  git clean -fdx ./tests/fixtures/
  ```


[1]: https://www.drupal.org/node/2583227
[2]: https://www.drupal.org/node/2583227#s-importing-data-from-the-fixture-to-your-testdatabase
[3]: https://www.drupal.org/node/1029506
