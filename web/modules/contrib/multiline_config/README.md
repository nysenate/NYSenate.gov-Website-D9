### SUMMARY
This module allows configuration strings to be exported as multiline instead of
one long single line.

### INSTALLATION
Install as usual, see [Installing Drupal 8
Modules](https://www.drupal.org/node/1897420) or [Installing modules' Composer
dependencies](https://www.drupal.org/node/2627292) for further information.

### CONFIGURATION
No configuration is needed.

### USAGE VIA CONFIGURATION SYNCHRONIZATION
After installing the module, navigate to
"/admin/config/development/configuration/full/export" or
"/admin/config/development/configuration/single/export" and export your desired
configuration.

### USAGE VIA DRUSH
After installing the module, only need to execute configuration export command,
if the destination is the same of the current configuration you need first to
remove the files, if there are not changes.
```
Export Drupal configuration to a directory.

Examples:
  drush config:export --destination=config-export; Save files in a backup directory named config-export.

Arguments:
  [label] A config directory label (i.e. a key in $config_directories array in settings.php).

Options:
  --add                       Run `git add -p` after exporting. This lets you choose which config changes to sync for commit.
  --commit                    Run `git add -A` and `git commit` after exporting. This commits everything that was exported without prompting.
  --message=MESSAGE           Commit comment for the exported configuration. Optional; may only be used with --commit.
  --destination[=DESTINATION] An arbitrary directory that should receive the exported files. A backup directory is used when no value is provided. 
  --diff                      Show preview as a diff, instead of a change list.

Aliases: cex, config-export
```

### Before
```yml
cancel_confirm:
  body: "[user:display-name],\n\nA request to cancel your account has been made at [site:name].\n\nYou may now cancel your account on [site:url-brief] by clicking this link or copying and pasting it into your browser:\n\n[user:cancel-url]\n\nNOTE: The cancellation of your account is not reversible.\n\nThis link expires in one day and nothing will happen if it is not used.\n\n--  [site:name] team"
  subject: 'Account cancellation request for [user:display-name] at [site:name]'
```

### After
```yml
cancel_confirm:
  body: |
    [user:display-name],
    
    A request to cancel your account has been made at [site:name].
    
    You may now cancel your account on [site:url-brief] by clicking this link or copying and pasting it into your browser:
    
    [user:cancel-url]
    
    NOTE: The cancellation of your account is not reversible.
    
    This link expires in one day and nothing will happen if it is not used.
    
    --  [site:name] team
  subject: 'Account cancellation request for [user:display-name] at [site:name]'
```

### THANKS TO
- Export configuration YAML strings as multiline [#2844452](https://www.drupal.org/project/drupal/issues/2844452)
- Jacob Rockowitz [(jrockowitz)](https://www.drupal.org/u/jrockowitz)
- Andrey Postnikov [(andypost)](https://www.drupal.org/u/andypost)

### SPONSORS
- [Fundación UNICEF Comité Español](https://www.unicef.es)

### CONTACT
Developed and maintained by Cambrico (http://cambrico.net).

Get in touch with us for customizations and consultancy:
http://cambrico.net/contact

#### Current maintainers:
- Pedro Cambra [(pcambra)](https://www.drupal.org/u/pcambra)
- Manuel Egío [(facine)](https://www.drupal.org/u/facine)
