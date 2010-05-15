# Running the Doctrine 2 Testsuite

## Setting up a PHPUnit Configuration XML

..

## Testing Lock-Support

The Lock support in Doctrine 2 is tested using Gearman, which allows to run concurrent tasks in parallel.
Install Gearman with PHP as follows:

1. Go to http://www.gearman.org and download the latest Gearman Server
2. Compile it and then call ldconfig
3. Start it up "gearmand -vvvv"
4. Install pecl/gearman by calling "gearman-beta"

You can then go into tests/ and start up two workers:

    php Doctrine/Tests/ORM/Functional/Locking/LockAgentWorker.php

Then run the locking test-suite:

    phpunit --configuration <myconfig.xml> Doctrine/Tests/ORM/Functional/Locking/GearmanLockTest.php

This can run considerable time, because it is using sleep() to test for the timing ranges of locks.