[![Tidelift](https://tidelift.com/badges/github/doctrine/doctrine2)](https://tidelift.com/subscription/pkg/packagist-doctrine-orm?utm_source=packagist-doctrine-orm&utm_medium=referral&utm_campaign=readme)

[Professionally supported Doctrine is now available](https://tidelift.com/subscription/pkg/packagist-doctrine-orm?utm_source=packagist-doctrine-orm&utm_medium=referral&utm_campaign=readme)

| [Master][Master] | [2.7][2.7] | [2.6][2.6] | [2.5][2.5] |
|:----------------:|:----------:|:----------:|:----------:|
| [![Build status][Master image]][Master] | [![Build status][2.7 image]][2.7] | [![Build status][2.6 image]][2.6] | [![Build status][2.5 image]][2.5] |
| [![Coverage Status][Master coverage image]][Master coverage] | [![Coverage Status][2.7 coverage image]][2.7 coverage] | [![Coverage Status][2.6 coverage image]][2.6 coverage] | [![Coverage Status][2.5 coverage image]][2.5 coverage] |

 ##### :warning: You are browsing the code of upcoming Doctrine 3.0.
 ##### Things changed a lot here and major code changes should be expected. If you are rather looking for a stable version, refer to the [2.6 branch][2.6] for the current stable release or [2.7 branch][2.7] for the upcoming release. If you are submitting a pull request, please see the _[Which branch should I choose?](#which-branch-should-i-choose)_ section below.

-----

Doctrine 3 is an object-relational mapper (ORM) for PHP 7.2+ that provides transparent persistence
for PHP objects. It sits on top of a powerful database abstraction layer (DBAL). One of its key features
is the option to write database queries in a proprietary object oriented SQL dialect called Doctrine Query Language (DQL),
inspired by Hibernate's HQL. This provides developers with a powerful alternative to SQL that maintains flexibility
without requiring unnecessary code duplication.

-----

### Which branch should I choose?

 * I am submitting a bugfix for a stable release
   * Your PR should target the [lowest active stable branch (2.6)](2.6).
 * I am submitting a new feature
   * Your PR should target the [master branch (3.0)][Master].
 * I am submitting a BC-breaking change
   * Your PR must target the [master branch (3.0)][Master].
   * Please also try to provide a deprecation path in a PR targeting the [2.7 branch][2.7].


## More resources:

* [Website](http://www.doctrine-project.org)
* [Documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)


  [Master image]: https://img.shields.io/travis/doctrine/doctrine2/master.svg?style=flat-square
  [Master]: https://travis-ci.org/doctrine/doctrine2
  [Master coverage image]: https://img.shields.io/scrutinizer/coverage/g/doctrine/doctrine2/master.svg?style=flat-square
  [Master coverage]: https://scrutinizer-ci.com/g/doctrine/doctrine2/?branch=master
  [2.7 image]: https://img.shields.io/travis/doctrine/doctrine2/2.7.svg?style=flat-square
  [2.7]: https://github.com/doctrine/doctrine2/tree/2.7
  [2.7 coverage image]: https://img.shields.io/scrutinizer/coverage/g/doctrine/doctrine2/2.7.svg?style=flat-square
  [2.7 coverage]: https://scrutinizer-ci.com/g/doctrine/doctrine2/?branch=2.7
  [2.6 image]: https://img.shields.io/travis/doctrine/doctrine2/2.6.svg?style=flat-square
  [2.6]: https://github.com/doctrine/doctrine2/tree/2.6
  [2.6 coverage image]: https://img.shields.io/scrutinizer/coverage/g/doctrine/doctrine2/2.6.svg?style=flat-square
  [2.6 coverage]: https://scrutinizer-ci.com/g/doctrine/doctrine2/?branch=2.6
  [2.5 image]: https://img.shields.io/travis/doctrine/doctrine2/2.5.svg?style=flat-square
  [2.5]: https://github.com/doctrine/doctrine2/tree/2.5
  [2.5 coverage image]: https://img.shields.io/scrutinizer/coverage/g/doctrine/doctrine2/2.5.svg?style=flat-square
  [2.5 coverage]: https://scrutinizer-ci.com/g/doctrine/doctrine2/?branch=2.5
