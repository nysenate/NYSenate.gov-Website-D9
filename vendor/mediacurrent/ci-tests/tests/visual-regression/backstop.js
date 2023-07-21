/* Quick guide to BackstopJS commands

  backstop reference --configPath=backstop.js --pathFile=paths --env=local --refHost=http://site.dev
  backstop test --configPath=backstop.js --pathFile=paths --env=local --testHost=http://site.dev

  Remember that the `--env` parameter needs to be the same for reference and test commands, even if you're comparing two different environments.

*/

var args = require('minimist')(process.argv.slice(2)); // grabs the process args
// var dotenv = require('dotenv').config(); // if sites have basic auth
var defaultPaths = ['/']; // default path just checks the homepage
var scenarios = []; // The array that'll have the URL paths to check

var environments = {
  'local': 'http://example.mcdev',
  'ci': 'http://172.17.0.1:8080',
  'dev': 'dev URL',
  'staging': 'staging URL',
  'prod': 'prod URL'
};
var default_environment = 'screenshots';

// Set the environments that are being compared
if (!args.env) {
  args.env = default_environment;
}
else if (!environments.hasOwnProperty(args.env)) {
  // @todo notify of unknown environment?
  args.env = default_environment;
}

// Site for reference screenshots
if (!args.refHost) {
  args.refHost = environments[args.env];
}

// Site for test screenshots
if (!args.testHost) {
  args.testHost = environments[args.env];
}

// Setting the directories to save screenshots
var saveDirectories = {
  "bitmaps_reference": "./backstop_data/"+args.env+"_reference",
  "bitmaps_test": "./backstop_data/"+args.env+"_test",
  "html_report": "./backstop_data/"+args.env+"_html_report",
  "ci_report": "./backstop_data/"+args.env+"_ci_report"
};


// Work out which paths to use: an array from a file, a supplied array, or the default
  // We'll be using the array from the `paths.js` file
if (args.pathFile) {
  var pathConfig = require('./'+args.pathFile+'.js'); // use paths.js file
  var paths = pathConfig.array;
} else if (args.paths) {
  pathString = args.paths; // pass in a comma-separated list of paths in terminal
  var paths = pathString.split(',');
} else {
  var paths = defaultPaths; // use the default of just the homepage
}

// Scenarios are a default part of config for BackstopJS
// Explanations for the sections below are at https://www.npmjs.com/package/backstopjs
for (var k = 0; k < paths.length; k++) {
  scenarios.push (
    {
      "label": paths[k],
      "referenceUrl": args.refHost+paths[k],
      "url": args.testHost+paths[k],
      "hideSelectors": ['iframe'],
      "removeSelectors": [],
      "selectors": ["document"], // "document" will snapshot the entire page
      "delay": 1000,
      "misMatchThreshold" : 0.1
    }
  );
}

// BackstopJS configuration
module.exports =
{
  "id": "project_"+args.env+"_config",
  "viewports": [
    {
      "name": "desktop",
      "width": 1025,
      "height": 2000
    },
    {
      "name": "tablet",
      "width": 768,
      "height": 2000
    },
    {
      "name": "mobile",
      "width": 375,
      "height": 2000
    }
  ],
  "scenarios":
    scenarios,
  "paths":
    saveDirectories,
  // "casperFlags": ["--ignore-ssl-errors=true", "--ssl-protocol=any"],
  "engine": "puppeteer", // alternate can be chromy, in which case uncomment the engineOptions below
  "engineOptions": {"args": ["--no-sandbox", "--disable-setuid-sandbox"]},
    // "engineOptions": {
    //   chromeFlags: ["--allow-insecure-localhost"]
    // },
  "report": ["browser"],
  "startingPort": 9333,
  "asyncCaptureLimit": 5,
  "asyncCompareLimit": 50,
  "debug": false
};
