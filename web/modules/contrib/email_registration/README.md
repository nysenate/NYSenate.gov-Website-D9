# Email Registration

Email Registration allows users to register and login with their email address
instead of using a separate username in addition to the email address. It will
automatically generate a username based on the email address but that behavior
can be overridden with a custom hook implementation in a site specific module.

If you use Drupal Commerce, adjust your checkout flow to use the alternative
login pane provided by this module.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/email_registration).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/email_registration).

## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

- You will probably want to change the welcome email `Administration
  -> Configuration -> People -> Account Settings` and replace instances of the
  token `[user:display-name]` with `[user:mail]`.

- The automatically generated username is still displayed name for posts,
  comments, etc. You can allow your users to change their username by granting
  the permission `Administration -> People -> Permissions` to "change own
  username". This privilege allows a user to change their username in "My
  Account".

- If you use Drupal Commerce, adjust your checkout flow to use the alternative
  login pane provided by this module.

- You can use the provided "Update username (from email_registration)" action
  to batch update multiple usernames to use the module's username definition
  used on new user registration.

- If you use Behat for testing user scenarios you should override the default
  login behavior so the email address will be used to log in instead of the
  username. To do this you should install the Service Container Extension
  which allows to override the default authentication service from Behat
  Drupal Extension:

`$ composer require friends-of-behat/service-container-extension`

Then add the following to your `behat.yml` configuration file:

```yaml
    default:
      extensions:
        FriendsOfBehat\ServiceContainerExtension:
          imports:
            - "./path/to/modules/contrib/email_registration/behat.services.yml"
```
Replace the last line with the actual path to where the Email Registration
module is located in your project.

Note that the minimum required Behat Drupal Extension version is 4.x. Older versions do
not have a way to override the default authentication functionality.

## Maintainers

- Christopher Herberte - [christopher-herberte](https://www.drupal.org/u/christopher-herberte)
- Greg Knaddison - [greggles](https://www.drupal.org/u/greggles)
- Andrey Postnikov - [andypost](https://www.drupal.org/u/andypost)
- Moshe Weitzman - [moshe weitzman](https://www.drupal.org/u/moshe-weitzman)
- Joshua Sedler - [Grevil](https://www.drupal.org/u/grevil)
- Julian Pustkuchen - [Anybody](https://www.drupal.org/u/anybody)
