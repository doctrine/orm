# Running the Doctrine 2 Testsuite

To execute the Doctrine2 testsuite, you just need to execute this simple steps:

 * Clone the project from GitHub
 * Enter the Doctrine2 folder
 * Install the dependencies
 * Execute the tests
 
 All this is (normally) done with:

    git clone git@github.com:doctrine/doctrine2.git
    cd doctrine2
    composer install
    ./vendor/bin/phpunit

## Pre-requisites
Doctrine2 works on many database vendors; the tests can detect the presence of installed vendors, but you need at least one of those; the easier to install is SQLite.

If you're using Debian, or a Debian-derivate Linux distribution (like Ubuntu), you can install SQLite with:

    sudo apt-get install sqlite

## Testing Lock-Support

The Lock support in Doctrine 2 is tested using Gearman, which allows to run concurrent tasks in parallel.
Install Gearman with PHP as follows:

1. Go to http://www.gearman.org and download the latest Gearman Server
2. Compile it and then call ldconfig
3. Start it up "gearmand -vvvv"
4. Install pecl/gearman by calling "gearman-beta"

You can then go into `tests/` and start up two workers:

    php Doctrine/Tests/ORM/Functional/Locking/LockAgentWorker.php

Then run the locking test-suite:

    phpunit --configuration <myconfig.xml> Doctrine/Tests/ORM/Functional/Locking/GearmanLockTest.php

This can run considerable time, because it is using sleep() to test for the timing ranges of locks.
