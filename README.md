# Travis-Builder!

This special tool aim to provide an easy way to build the PHAR files of your public 'PocketMine-MP' plugins.

##### NOTE: Before anything else, you must login into [Travis](https://travis-ci.org) website and enable your repository in the settings tab

## How to install:
Just the initial process to use this system is 'long' but pretty simple:
 1. Open your git-enabled terminal and navigate to your plugin's git root directory
 2. Type in: `git submodule add https://github.com/iksaku/Travis-Builder travis`
 3. Create a new file called `.travis.yml` and write paste the following text:
 `
 language: php
 php: 7.0
 sudo: false
 script: php ./travis/build.php
 `
 4. Run: `git add -A`
 5. Commit, Push & Enjoy!

## Configuration
Now that you know how to add this tool to your git repo, it is now time to configure the ability to deploy...
 * TO-DO

## Update the scripts
 * TO-DO