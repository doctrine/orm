# Running the Doctrine 2 Testsuite

To execute the Doctrine2 testsuite, you just need to execute this simple steps:

 * Clone the project from GitHub
 * Enter the Doctrine2 folder
 * Install the dependencies
 * Execute the tests
 
 All this is (normally) done with:

```
git clone git@github.com:doctrine/doctrine2.git
cd doctrine2
composer install
./vendor/bin/phpunit
```

## Pre-requisites
Doctrine2 works on many database vendors; the tests can detect the presence of installed vendors, but you need at least one of those; the easier to install is SQLite.

If you're using a Debian-derivative Linux distribution, you can install SQLite with:

    sudo apt-get install sqlite
