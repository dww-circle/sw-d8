# Installation
### Step 1
Make sure you have Node and npm installed.
You can read a guide on how to install node here: https://docs.npmjs.com/getting-started/installing-node

### Step 2
If you don't already have yarn (v1.2.1) installed, you can do so by `npm install -g yarn`.

### Step 3
Navigate (`cd`) to theme root

### Step 4
Run the command `yarn install`

### Step 5
Copy sample.config.local.json to config.local.json. Change proxy url to your local url. This file is ignored by git.

## Usage
From the theme directory run `gulp`
 - Gulp will compile Sass directory into CSS.
 - Browser sync will launch
 - JS will be minified

## Troubleshooting
- Confirmed to work with Node v6.11.4 npm v5.5.1 and yarn v1.2.1
- If you get unresolvable errors try deleting the node_module folder and running `yarn install` again. 