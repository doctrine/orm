Security
========

The Doctrine library is operating very close to your database and as such needs
to handle and make assumptions about SQL injection vulnerabilities.

It is vital that you understand how Doctrine approaches security, because
we cannot protect you from SQL injection.

Please read the documentation chapter on Security in Doctrine DBAL and ORM to
understand the assumptions we make.

- [DBAL Security Page](https://www.doctrine-project.org/projects/doctrine-dbal/en/stable/reference/security.html)
- [ORM Security Page](https://www.doctrine-project.org/projects/doctrine-orm/en/stable/reference/security.html)

If you find a Security bug in Doctrine, please report it on Jira and change the
Security Level to "Security Issues". It will be visible to Doctrine Core
developers and you only.
