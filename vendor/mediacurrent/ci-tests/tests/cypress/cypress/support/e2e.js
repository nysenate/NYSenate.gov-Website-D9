// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands';

// load and register the grep feature using "require" function
// https://github.com/bahmutov/cy-grep
const registerCypressGrep = require('@bahmutov/cy-grep')
registerCypressGrep()

// if you want to use the "import" keyword
// note: `./index.d.ts` currently extends the global Cypress types and
// does not define `registerCypressGrep` so the import path is directly
// pointed to the support file
// import registerCypressGrep from '@bahmutov/cy-grep/src/support'
// registerCypressGrep()

// "import" with `@ts-ignore`
// @see error 2306 https://github.com/microsoft/TypeScript/blob/3fcd1b51a1e6b16d007b368229af03455c7d5794/src/compiler/diagnosticMessages.json#L1635
// @ts-ignore
// import registerCypressGrep from '@bahmutov/cy-grep'
// registerCypressGrep()