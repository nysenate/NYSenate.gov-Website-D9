/*
 * PostCSS Configurations and Plugins
 * All documentation, including setup and plugin options can be found on the PostCSS Github page. Here, you can
 * read about the setup chosen for Prototype and specifics of how to configure on a per-project basis.
 * https://github.com/postcss/postcss
 * - 01 - Requirements
 * - 02 - SVG Encode
 * - 03 - WYSIWYG Support
 * - 04 - Exports
 */

/*------------------------------------*\
  01 - Requirements
  Although PostCSS does not inherently require any other plugins or settings in order to operate, other plugins are
  used to make the build step of CSS files better and allow developers access to additional tools and functions.

  * PostCSS Preset Env: https://github.com/csstools/postcss-plugins/tree/main/plugin-packs/postcss-preset-env
    - Plugin Pack for PostCSS which leverages the list of the features from https://cssdb.org/ and applies plugins,
      so you can use those new features without having to worry about browser support. This plugin pack includes
      Autoprefixer, which in the past, had been loaded as a separate plugin.

  * PostCSS Inline SVG: https://github.com/TrySound/postcss-inline-svg
    - Plugin to reference an SVG file and control its attributes with CSS syntax.
\*------------------------------------*/

// const cssnano = require("cssnano");
const postcssPresetEnv = require('postcss-preset-env');
const postcssInlineSvg = require('postcss-inline-svg');
const PrefixWrap = require('postcss-prefixwrap');


/*------------------------------------*\
  02 - SVG Encode
  Since we are using PostCSS Inline SVG, we will need to add to the default set of encode regex options when replacing
  inline SVG code during the build step. Here we are adding to that default set of replacements.
\*------------------------------------*/

function encode(code) {
  return code
    .replace(/\%/g, "%25")
    .replace(/\</g, "%3C")
    .replace(/\>/g, "%3E")
    .replace(/\s/g, "%20")
    .replace(/\!/g, "%21")
    .replace(/\*/g, "%2A")
    .replace(/\'/g, "%27")
    .replace(/\"/g, "%22")
    .replace(/\(/g, "%28")
    .replace(/\)/g, "%29")
    .replace(/\;/g, "%3B")
    .replace(/\:/g, "%3A")
    .replace(/\@/g, "%40")
    .replace(/\&/g, "%26")
    .replace(/\=/g, "%3D")
    .replace(/\+/g, "%2B")
    .replace(/\$/g, "%24")
    .replace(/\,/g, "%2C")
    .replace(/\//g, "%2F")
    .replace(/\?/g, "%3F")
    .replace(/\#/g, "%23")
    .replace(/\[/g, "%5B")
    .replace(/\]/g, "%5D");
}

/*------------------------------------*\
  03 - WYSIWYG Support
  Define both the developmental, "Watch" and final production, "Build"
  processes for compiling files. The final production, "Build" process includes
  minified files.
\*------------------------------------*/

const prefexWrapSelector = '.ck-content';
const prefixWrapConfig = {
    // You may want to exclude some selectors from being prefixed, this is
    // enabled using the `ignoredSelectors` option.
    ignoredSelectors: [],

    // You may want root tags, like `body` and `html` to be converted to
    // classes, then prefixed, this is enabled using the `prefixRootTags`
    // option.
    // With this option, a selector like `html` will be converted to
    // `.my-container .html`, rather than the default `.my-container`.
    prefixRootTags: false,

    // In certain scenarios, you may only want `PrefixWrap()` to wrap certain
    // CSS files. This is done using the `whitelist` option.
    // ⚠️ **Please note** that each item in the `whitelist` is parsed as a
    // regular expression. This will impact how file paths are matched when you
    // need to support both Windows and Unix like operating systems which use
    // different path separators.
    whitelist: ['wysiwyg.css'],

    // In certain scenarios, you may want `PrefixWrap()` to exclude certain CSS
    // files. This is done using the `blacklist` option.
    // ⚠️ **Please note** that each item in the `blacklist` is parsed as a
    // regular expression. This will impact how file paths are matched when you
    // need to support both Windows and Unix like operating systems which use
    // different path separators.
    // If `whitelist` option is also included, `blacklist` will be ignored.
    blacklist: [],

    // When writing nested css rules, and using a plugin like `postcss-nested`
    // to compile them, you will want to ensure that the nested selectors are
    // not prefixed. This is done by defining the `nested` property and setting
    // the value to the selector prefix being used to represent nesting, this is
    // most likely going to be `"&"`.
    nested: '&',
};


/*------------------------------------*\
  04 - Exports
  Define both the developmental, "Watch" and final production, "Build"
  processes for compiling files. The final production, "Build" process includes
  minified files.
\*------------------------------------*/

module.exports = {
  plugins: [
    postcssPresetEnv({
      features: {
        // Disable custom property fallbacks.
        'logical-properties-and-values': false,
        'custom-properties': false,
      },
    }), // Additional options can be defined here for PostCSS Preset Env
    postcssInlineSvg({
      // Other additional options can be defined here for PostCSS Inline SVG
      encode,
      paths: ['./images/icons'],
    }),
    PrefixWrap(prefexWrapSelector, prefixWrapConfig),
    // cssnano(), // Uncomment this line if you would like to minimize all CSS
  ],
};
