# Mediacurrent Rain Install Profile #

The Mediacurrent Rain install profile adds out-of-the-box editorial, administrative and media enhancements to the typical Drupal 8 installation.
This project adds additional content features to be used in conjunction with the 
base installation profile.

### Optional features
* [rain_blocks](https://bitbucket.org/mediacurrent/mis_rain/src/develop/modules/rain_blocks/) - Provides common block types. 
* [rain_content](https://bitbucket.org/mediacurrent/mis_rain/src/develop/modules/rain_content/) - Provides common content types. 
* [rain_paragraphs](https://bitbucket.org/mediacurrent/mis_rain/src/develop/modules/rain_paragraphs/) - Provides common paragraphs
* rain_search - Enables Search API search index and page

### Making configuration updates to this project

* One aspect of making updates to this project is that its important to remove uuids/hashes from configuration. The base script below will do this for you.
```
#!/bin/bash
FILES=config/install/*
for f in $FILES
do
  echo "Processing $f file..."
  sed -i '' '/^uuid:/d' $f
  sed -i '' '/^\_core:/d' $f
  sed -i '' '/^  default_config_hash:/d' $f
done
```