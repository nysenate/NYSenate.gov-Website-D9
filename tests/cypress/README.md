# Cypress Testing

## Cypress Usage
Update `./tests/cypress/cypress.config.js` for your environment.

Change to the Cypress test directory:
```
cd ./tests/cypress
```

Set up the Node environment and install Cypress:
```
nvm use
nvm install
npm install
```

## Cypress E2E Testing

Runs Cypress tests from the CLI without the GUI:
```
npx cypress run
```

Opens Cypress in the interactive GUI:
```
npx cypress open
```
> **_NOTE:_** Run `npx cypress help` for more information.
