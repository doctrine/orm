Filters
=======

Doctrine 2.2 features a filter system that allows the developer to add SQL to
the conditional clauses of queries, regardless the place where the SQL is
generated (e.g. from a DQL query, or by loading associated entities). To give
you an idea on how it works, this chapter starts with an example of a filter.


Example filter class
--------------------
Throughout this document the example ``MyLocaleFilter`` class will be used to
illustrate how the filter feature works. A filter class should extend the base
``Doctrine\ORM\Query\Filter\SQLFilter`` class and implement the ``addFilterConstraint``
method. The method receives the ClassMetadata of the filtered entity and the
table alias of the table of the entity.

.. code-block:: php
    <?php
    namespace Example;
    use Doctrine\ORM\Mapping\ClassMetaData,
        Doctrine\ORM\Query\Filter\SQLFilter;

    class MyLocaleFilter extends SQLFilter
    {
        public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
        {
            // Check if the entity implements the LocalAware interface
            if (!in_array("LocaleAware", $targetEntity->reflClass->getInterfaceNames())) {
                return "";
            }

            return $targetTableAlias.'.locale = ' . $this->getParameter('locale');
        }
    }




Configuration
-------------
Filter classes are added to the configuration as following:

.. code-block:: php
    <?php
    $config->addFilter("locale", "\Doctrine\Tests\ORM\Functional\MyLocaleFilter");


The ``addFilter()`` method takes a name for the filter and the name of the
class responsible for the actual filtering.


Enabling Filters and Setting Parameters
---------------------------------------------------
Filters can be enabled via the ``FilterCollection`` that is available in the
``EntityManager``. The ``enable`` function will return the filter object. This
object can be used to set certain parameters for the filter.

.. code-block:: php
    <?php
    $filter = $em->getFilters()->enable("locale");
    $filter->setParameter('locale', 'en');


Disabling Filters
-----------------
.. code-block:: php
    $filter = $em->getFilters()->disable("locale");
