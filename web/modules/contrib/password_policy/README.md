Password Policy
===============

This is a Drupal 8 module for the Password Policy module. This is 
comprised of constraints and policies.

Constraints are different ways that you can restrict a password. A 
policy is an instance of a constraint that define specific parameters 
for the constraint.

Policies are applied through Drupal's role-based permissions system.

Password Policy comes bundled with a password expiration feature. 
Policies define a time-based expiration logic (based on days) and 
administrators have the ability to manually expire all passwords by 
role.


### Enable

-  Download and enable the module


### Plugins

All plugins are installed as separate modules. The only policies that 
are out of the box is the Password Reset feature.

-  Password Expiration (time-based or manually forced, built in feature 
of Password Policy)
-  Password Length (submodule of Password Policy)
-  Zxcvbn (https://github.com/nerdstein/password_policy_zxcvbn)


### Configure

-  Enable all plugin modules
-  Go to Password Policy's configuration page 
(/admin/config/security/password-policy)
-  Add policies by clicking on the tab for each plugin
-  Go to the permissions page (/admin/people/permissions)
-  Select which roles the policies applies to


### Architecture

-  Password Policy provides a plugin manager that defines an interface 
for constraints and the constraint's policies
-  Policies are implemented as permissions and enforced on the user form
-  Password expiration implements an event subscriber and forces a user 
to his/her user form upon expiration
-  Password time-based expiration leverages cron for tagging accounts as
 expired
- Externally authenticated users (via `externalauth` module) are excluded from validation and time-based expiration
