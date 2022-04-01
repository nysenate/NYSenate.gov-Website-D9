INTRODUCTION
------------

Email Registration allows users to register and login with their email address
instead of using a separate username in addition to the email address. It will
automatically generate a username based on the email address but that behavior
can be overridden with a custom hook implementation in a site specific module.

INSTALLATION
------------

Required step:

Enable the module as you normally would. See:
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules


CONFIGURATION
-------------

* You will probably want to change the welcome email (Administration
  -> Configuration -> People -> Account Settings) and replace instances of the
  token [user:display-name] with [user:mail].

* The automatically generated username is still displayed name for posts,
  comments, etc. You can allow your users to change their username by granting
  the permission (Administraion -> People -> Permissions) to "change own
  username". This privilege allows a user to change their username in "My
  Account".

* If a user enters an invalid email or password they will see a message:
 "Unrecognized username or password. Forgot your password?"
    That message is confusing because it mentions username when all other
    language on the page mentions entering their email. This can be easily
    overridden in your settings.php file with an entry like this:

$settings['locale_custom_strings_en'][''] = [
  'Unrecognized username or password. <a href=":password">Forgot your password?</a>' => 'Unrecognized e-mail address or password. <a href=":password">Forgot your password?</a>',
];

* If you use Drupal Commerce, adjust your checkout flow to use the alternative 
  login pane provided by this module.

BUGS, FEATURES, QUESTIONS
-------------------------
Post any bugs, features or questions to the issue queue:

http://drupal.org/project/issues/email_registration
