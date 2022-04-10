# Contribute to Doctrine

Thank you for contributing to Doctrine!

Before we can merge your Pull-Request here are some guidelines that you need to follow.
These guidelines exist not to annoy you, but to keep the code base clean,
unified and future proof.

Doctrine has [general contributing guidelines][contributor workflow], make
sure you follow them.

[contributor workflow]: https://www.doctrine-project.org/contribute/index.html

## Coding Standard

This project follows [`doctrine/coding-standard`][coding standard homepage].
You may fix many some of the issues with `vendor/bin/phpcbf`.

[coding standard homepage]: https://github.com/doctrine/coding-standard

## Unit-Tests

Please try to add a test for your pull-request.

* If you want to fix a bug or provide a reproduce case, create a test file in
  ``tests/Doctrine/Tests/ORM/Functional/Ticket`` with the name of the ticket,
  ``DDC1234Test.php`` for example.
* If you want to contribute new functionality add unit- or functional tests
  depending on the scope of the feature.

You can run the unit-tests by calling ``vendor/bin/phpunit`` from the root of the project.
It will run all the tests with an in memory SQLite database.

In order to do that, you will need a fresh copy of the ORM, and you
will have to run a composer installation in the project:

```sh
git clone git@github.com:doctrine/orm.git
cd orm
composer install
```

To run the testsuite against another database, copy the ``phpunit.xml.dist``
to for example ``mysql.phpunit.xml`` and edit the parameters. You can
take a look at the ``ci/github/phpunit`` directory for some examples. Then run:

    vendor/bin/phpunit -c mysql.phpunit.xml

If you do not provide these parameters, the test suite will use an in-memory
sqlite database.

Tips for creating unit tests:

1. If you put a test into the `Ticket` namespace as described above, put the testcase and all entities into the same class.
   See `https://github.com/doctrine/orm/tree/2.8.x/tests/Doctrine/Tests/ORM/Functional/Ticket/DDC2306Test.php` for an
   example.

## Getting merged

Please allow us time to review your pull requests. We will give our best to review
everything as fast as possible, but cannot always live up to our own expectations.

Thank you very much again for your contribution!

