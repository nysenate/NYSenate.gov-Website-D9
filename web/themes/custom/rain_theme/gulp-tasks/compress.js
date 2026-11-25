/*eslint strict: ["error", "global"]*/
'use strict';

// Include gulp
const { src, dest } = require('gulp');

// Include Our Plugins
const rename = require('gulp-rename');

// Export our tasks.
module.exports = {
  // Copy image assets to the build output.
  copyAssets: function() {
    return src([
      './src/patterns/{global,layout,components}/**/*{.png,.jpg,.svg}'
    ])
      .pipe(
        rename(function(path) {
          path.dirname = '';
          return path;
        })
      )
      .pipe(dest('./dist/images'));
  }
};
