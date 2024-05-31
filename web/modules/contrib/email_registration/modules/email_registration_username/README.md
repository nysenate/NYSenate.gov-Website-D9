# Email Registration Username module

This module updates a user's username with their email-address (on user
creation) and keeps both values in sync (if they were synced before, making sure
users with the right permission can still set a different username if needed).

This module also provides an action, to update a user's username with their
email-address. This action automatically replaces all occurrences of the
main module's action logic to update the username, including the action on the
core "People" view.

## Security implications

Having the email-address as the username could result to leaked email-addresses
(see https://www.drupal.org/drupal-security-team/security-team-procedures/disclosure-of-usernames-and-user-ids-is-not-considered-a-weakness).
The option to override the users display name will elevate this security
implication slightly.

## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Go to "Configuration -> People -> Account Settings"
(`/admin/config/people/accounts`) to configure this module's options:
- "Override user display name" (`username_display_override_mode`)
  - Allows dynamic overriding of the user display name. The options are as
  follows:
  - `Disabled` => Shows the username (=email) (Note: Higher risk of information disclosure) 
  - `Email registration default` => Replace the name with the part of the email address before the '@' 
  - `Custom` => Replace the name with a custom value (allows tokens) 
