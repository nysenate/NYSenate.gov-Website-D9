Comments Ban

This module allow to the sites administrators to Ban certain users from the Drupal's comments system.


How it works:

We have created Validation Constraint for the comments entity, in this constraint we validate if the user creating the comment is allowed to create it.

In order to achieve the ban functionality, we provided the following custom actions:
 - Remove comment and ban user (Could be added to any comments view)
 - Unban user from the comments (Could be added to any user view)

We also provide a brand new view called Users banned from comments, in this view you should be able to see and administer any user blocked from the Drupal comments system.


How to use it


- Initial setup:

Step 1:
Give permissions to some users to ban users from the comments. To do that just give the permission "administer users" to the role that you want to allow to ban users.

Step 2:
Enable the field "User banned from comments"  to be use into the user account form settings.


- Banning users:

Banning a users is very simple, just go to the user profile page, press the edit button and then look for the field "User banned from comments" and check it.

- Unbanning users:
You could do it going directly to the user account edition and remove the check over the field "User banned from comments" or you could go to the
following page: /admin/config/people/banned-from-comments and from there select multiple users and unban them using the our custom bulk operation.
