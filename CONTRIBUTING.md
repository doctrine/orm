# Contribute to Doctrine

Thank you for contributing to Doctrine. Before we can merge your Pull-Request
here are some guidelines that you need to follow:

## Coding Standard

We use PSR-1 and PSR-2:

* https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
* https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md

with some exceptions/differences:

* Keep the nesting of control structures per method as small as possible
* Align equals (=) signs
* Add spaces between assignment, control and return statements
* Prefer early exit over nesting conditions
* Add spaces around a negation if condition ``if ( ! $cond)``

## Unit-Tests

You can run the unit-tests by calling ``phpunit`` from the root of the project.
It will run all the tests with an in memory SQLite database.

To run the testsuite against another database, copy the ``phpunit.xml.dist``
to for example ``mysql.phpunit.xml`` and edit the parameters. You can
take a look at the ``tests/travis`` folder for some examples. Then run:

    phpunit -c mysql.phpunit.xml

## Travis

We automatically run your pull request through [Travis CI](http://www.travis-ci.org)
against SQLite, MySQL and PostgreSQL. If you break the tests, we cannot merge your code.

## DoctrineBot, Tickets and Jira

DoctrineBot will synchronize your Pull-Request into our [Jira](http://www.doctrine-project.org).
Make sure to add any existing Jira ticket into the Pull-Request Title, for example:

    "[DDC-123] My Pull Request"

## Getting merged

Please allow us time to review your pull requests. We will give our best to review
everything as fast as possible, but cannot always live up to our own expectations.

Thank you very much again for your contribution!

