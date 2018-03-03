# Contributing to Doctrine ORM

Thank you for contributing to Doctrine ORM!

Before we can merge your pull request here are some guidelines that you need to follow.
These guidelines exist not to annoy you, but to keep the code base clean,
unified and future proof.

## Obtaining a copy

In order to submit a pull request, you will need to [fork the project][Fork] and a fresh copy of the source code:

```sh
git clone git@github.com:<your-github-name>/doctrine2.git orm
cd orm
```

Then you will have to run a Composer installation in the project:
```sh
curl -sS https://getcomposer.org/installer | php
./composer.phar install
```

## Choosing the branch

 * I am submitting a bugfix for a stable release
   * Your PR should target the [lowest active stable branch (2.6)][2.6].
 * I am submitting a new feature
   * Your PR should target the [master branch (3.0)][Master].
 * I am submitting a BC-breaking change
   * Your PR must target the [master branch (3.0)][Master].
   * Please also try to provide a deprecation path in a PR targeting the [2.7 branch][2.7].
   
Please always create a new branch for your changes (i.e. not commit directly into `master` in your fork), otherwise you would run into troubles with creating multiple pull requests.

## Coding Standard

We follow the [Doctrine Coding Standard][CS].
Please refer to this repository to learn about the rules your code should follow.
You can also use `vendor/bin/phpcs` to validate your changes locally.

## Tests

Please try to add a test for your pull request.

* If you want to fix a bug or provide a reproduce case, create a test file in
  ``tests/Doctrine/Tests/ORM/Functional/Ticket`` with the identifier of the issue,
  i.e. ``GH1234Test.php`` for an issue with id `#1234`.
* If you want to contribute new functionality, add unit or functional tests
  depending on the scope of the feature.

You can run the tests by calling ``vendor/bin/phpunit`` from the root of the project.
It will run all the tests with an in-memory SQLite database.

To run the testsuite against another database, copy the ``phpunit.xml.dist``
to for example ``mysql.phpunit.xml`` and edit the parameters. You can
take a look at the ``tests/travis`` folder for some examples. Then run:

    vendor/bin/phpunit -c mysql.phpunit.xml

Tips for creating unit tests:

1. If you put a test into the `Ticket` namespace as described above, put the testcase and all entities into the same file.
   See [DDC2306Test][Test Example] for an example.

## CI

We automatically run all pull requests through [Travis CI][Travis].

* The test suite is ran against SQLite, MySQL, MariaDB and PostgreSQL on all supported PHP versions.
* The code is validated against our [Coding Standard](#coding-standard).
* The code is checked by a static analysis tool.

If you break the tests, we cannot merge your code,
so please make sure that your code is working before opening a pull request.

## Getting merged

Please allow us time to review your pull requests. We will give our best to review
everything as fast as possible, but cannot always live up to our own expectations.

Thank you very much again for your contribution!

  [Master]: https://github.com/doctrine/doctrine2/tree/master
  [2.7]: https://github.com/doctrine/doctrine2/tree/2.7
  [2.6]: https://github.com/doctrine/doctrine2/tree/2.6
  [CS]: https://github.com/doctrine/coding-standard
  [Fork]: https://guides.github.com/activities/forking/
  [Travis]: https://www.travis-ci.org
  [Test Example]: https://github.com/doctrine/doctrine2/tree/master/tests/Doctrine/Tests/ORM/Functional/Ticket/DDC2306Test.php
