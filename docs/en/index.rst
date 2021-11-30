Welcome to Doctrine 2 ORM's documentation!
==========================================

The Doctrine documentation is comprised of tutorials, a reference section and
cookbook articles that explain different parts of the Object Relational mapper.

Doctrine DBAL and Doctrine Common both have their own documentation.

Getting Help
------------

If this documentation is not helping to answer questions you have about
Doctrine ORM don't panic. You can get help from different sources:

-  There is a :doc:`FAQ <reference/faq>` with answers to frequent questions.
-  The `Doctrine Mailing List <https://groups.google.com/group/doctrine-user>`_
-  Slack chat room `#orm <https://www.doctrine-project.org/slack>`_
-  Report a bug on `GitHub <https://github.com/doctrine/orm/issues>`_.
-  On `StackOverflow <https://stackoverflow.com/questions/tagged/doctrine-orm>`_

If you need more structure over the different topics you can browse the :doc:`table
of contents <toc>`.

Getting Started
---------------

* **Tutorial**:
  :doc:`Getting Started with Doctrine <tutorials/getting-started>`

* **Setup**:
  :doc:`Installation & Configuration <reference/configuration>`

Mapping Objects onto a Database
-------------------------------

* **Mapping**:
  :doc:`Objects <reference/basic-mapping>` |
  :doc:`Associations <reference/association-mapping>` |
  :doc:`Inheritance <reference/inheritance-mapping>`

* **Drivers**:
  :doc:`Docblock Annotations <reference/annotations-reference>` |
  :doc:`Attributes <reference/attributes-reference>` |
  :doc:`XML <reference/xml-mapping>` |
  :doc:`YAML <reference/yaml-mapping>` |
  :doc:`PHP <reference/php-mapping>`

Working with Objects
--------------------

* **Basic Reference**:
  :doc:`Entities <reference/working-with-objects>` |
  :doc:`Associations <reference/working-with-associations>` |
  :doc:`Events <reference/events>`

* **Query Reference**:
  :doc:`DQL <reference/dql-doctrine-query-language>` |
  :doc:`QueryBuilder <reference/query-builder>` |
  :doc:`Native SQL <reference/native-sql>`

* **Internals**:
  :doc:`Internals explained <reference/unitofwork>` |
  :doc:`Associations <reference/unitofwork-associations>`

Advanced Topics
---------------

* :doc:`Architecture <reference/architecture>`
* :doc:`Advanced Configuration <reference/advanced-configuration>`
* :doc:`Limitations and known issues <reference/limitations-and-known-issues>`
* :doc:`Commandline Tools <reference/tools>`
* :doc:`Transactions and Concurrency <reference/transactions-and-concurrency>`
* :doc:`Filters <reference/filters>`
* :doc:`NamingStrategy <reference/namingstrategy>`
* :doc:`Improving Performance <reference/improving-performance>`
* :doc:`Caching <reference/caching>`
* :doc:`Partial Objects <reference/partial-objects>`
* :doc:`Change Tracking Policies <reference/change-tracking-policies>`
* :doc:`Best Practices <reference/best-practices>`
* :doc:`Metadata Drivers <reference/metadata-drivers>`
* :doc:`Batch Processing <reference/batch-processing>`
* :doc:`Second Level Cache <reference/second-level-cache>`

Tutorials
---------

* :doc:`Indexed associations <tutorials/working-with-indexed-associations>`
* :doc:`Extra Lazy Associations <tutorials/extra-lazy-associations>`
* :doc:`Composite Primary Keys <tutorials/composite-primary-keys>`
* :doc:`Ordered associations <tutorials/ordered-associations>`
* :doc:`Pagination <tutorials/pagination>`
* :doc:`Override Field/Association Mappings In Subclasses <tutorials/override-field-association-mappings-in-subclasses>`
* :doc:`Embeddables <tutorials/embeddables>`

Changelogs
----------

* `Upgrade <https://github.com/doctrine/doctrine2/blob/master/UPGRADE.md>`_

Cookbook
--------

* **Patterns**:
  :doc:`Aggregate Fields <cookbook/aggregate-fields>` |
  :doc:`Decorator Pattern <cookbook/decorator-pattern>` |
  :doc:`Strategy Pattern <cookbook/strategy-cookbook-introduction>`

* **DQL Extension Points**:
  :doc:`DQL Custom Walkers <cookbook/dql-custom-walkers>` |
  :doc:`DQL User-Defined-Functions <cookbook/dql-user-defined-functions>`

* **Implementation**:
  :doc:`Array Access <cookbook/implementing-arrayaccess-for-domain-objects>` |
  :doc:`Notify ChangeTracking Example <cookbook/implementing-the-notify-changetracking-policy>` |
  :doc:`Using Wakeup Or Clone <cookbook/implementing-wakeup-or-clone>` |
  :doc:`Working with DateTime <cookbook/working-with-datetime>` |
  :doc:`Validation <cookbook/validation-of-entities>` |
  :doc:`Entities in the Session <cookbook/entities-in-session>` |
  :doc:`Keeping your Modules independent <cookbook/resolve-target-entity-listener>`

* **Hidden Gems**
  :doc:`Prefixing Table Name <cookbook/sql-table-prefixes>`

* **Custom Datatypes**
  :doc:`MySQL Enums <cookbook/mysql-enums>`
  :doc:`Advanced Field Value Conversion <cookbook/advanced-field-value-conversion-using-custom-mapping-types>`

.. include:: toc.rst
