Overview:
---------
The session-limit module is designed to allow an administrator to limit
the number of simultaneous sessions a user may have.

Installation and configuration:
-------------------------------
Installation is as simple as copying the module into your 'modules' directory,
then enabling the module.

The default max sessions is 1. Change default max sessions can be changed in
'Configuration >> People >> Session Limit'
The path for this is /admin/config/people/session_limit

Triggers are available to assign any of the three system actions to either the
collision or disconnect events. That includes displaying a message to the user,
sending an email, or redirecting to a different URL.

Rules events are available for collision or disconnect events.

The precedence of defined session limits are:

1. The user's session limit if set, otherwise,
2. The highest session limit for a user as set on their roles, if all are set to default then
3. The system default session_limit

Optional:
---------
This module is able to use the token module for generating tokenized emails
or showing tokenized messages on the collision and disconnect events.

http://drupal.org/project/token

If you want to customize the notices that users see, try the String Overrides
module. Both the message for prompting to disconnect a user and the message
that the disconnected user sees are passed through Drupal's localize t()
function.

http://drupal.org/project/stringoverrides

Requires:
---------
 - Drupal 7.x

Issue queue:
------------
http://drupal.org/project/issues/session_limit
