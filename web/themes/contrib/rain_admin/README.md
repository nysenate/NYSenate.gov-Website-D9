# Getting Started

## Browser Support
Autoprefixer & Babel is set to support the last 2 versions of modern browsers.

These can be updated at any time within the `package.json`.

## Run the following commands from the theme directory
If you haven't yet, install nvm:
https://github.com/creationix/nvm

### Use the right version of node with:
`nvm use`

This command will look at your `.nvmrc` file and use the version node.js 
specified in it. This ensures all developers use the same version of
node for consistency.

### If that version of node isn't installed, install it with:
`nvm install`

### Install npm dependencies with
`npm install`

This command looks at `package.json` and installs all the npm dependencies
specified in it. Some of the dependencies include gulp, autoprefixer,
gulp-sass and others.

### Runs default task
`npm run build`

This will run whatever the default task is.

### Compiles Sass
`npm run compile`

This will perform a one-time Sass compilation.

### Runs the watch command
`npm run watch`

This is ideal when you are doing a lot of Sass changes and you want to make
sure every time a change is saved it automatically gets compiled to CSS.

### Cleans complied directory
`npm run clean`

This will perform a one-time deletion of all compiled files within the
dist/ directory.
