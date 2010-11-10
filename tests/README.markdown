# Running the Doctrine 2 Testsuite

## Setting up a PHPUnit Configuration XML

..

## Running the unit tests
If you haven't prepared your system to run unit tests, please read the previous chapter.

Once your system is set up, "cd" into the tests directory, then run:

	phpunit --configuration dbproperties.xml Doctrine/Tests/AllTest
	

## Preparing to run Unit Tests
In order to run the unit tests, you need [phing](http://www.phing.info), [PHPUnit](http://www.phpunit.de) > 3.4.0 and < 3.5.0.

You can simply issue the following commands to install all you need to run the unit tests. Note that we pull in all dependencies.
If you do not wish that, you need to go through every package manually.

Please note that you *need* the XSL extension of PHP.

	\# Install XML-Serializer manually, because it is beta
	pear install channel://pear.php.net/XML_Serializer-0.20.2
	
	\# Install phing with all dependencies
	pear channel-discover pear.phing.info
	pear install -a phing/phing
	
	\# Additional phing task for packaging
	pear channel-discover pear.domain51.com
	pear install channel://pear.domain51.com/Phing_d51PearPkg2Task-0.6.3
	
	\# Install PHPUnit
	pear channel-discover pear.phpunit.de
	pear channel-discover components.ez.no
	pear channel-discover pear.symfony-project.com
	pear install phpunit/PHPUnit-3.4.15
	
Now, copy dbproperties.xml.dev to dbproperties.xml and put your database settings into that.

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