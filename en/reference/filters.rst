Filters
=======

Doctrine 2.2 features a filter system that allows the developer to add SQL to
the conditional clauses of queries, regardless the place where the SQL is
generated (e.g. from a DQL query, or by loading associated entities).

The filter functionality works on SQL level. Whether an SQL query is generated
in a Persister, during lazy loading, in extra lazy collections or from DQL.
Each time the system iterates over all the enabled filters, adding a new SQL
part as a filter returns.

By adding SQL to the conditional clauses of queries, the filter system filters
out rows belonging to the entities at the level of the SQL result set. This
means that the filtered entities are never hydrated (which can be expensive).

To give you an idea on how it works, the next section contains an example of a
filter.

Example filter class
--------------------
Throughout this document the example ``MyLocaleFilter`` class will be used to
illustrate how the filter feature works. A filter class should extend the base
``Doctrine\ORM\Query\Filter\SQLFilter`` class and implement the ``addFilterConstraint``
method. The method receives the ``ClassMetadata`` of the filtered entity and the
table alias of the SQL table of the entity.

Parameters for the query should be set on the filter object by
``SQLFilter::setParameter()``. Only parameters set via this function used in
the filters.  The ``SQLFilter::getParameter()`` function takes care of the
proper quoting of parameters.

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

            return $targetTableAlias.'.locale = ' . $this->getParameter('locale'); // Automatically quoted
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

.. warning::
    Disabling and enabling filters does not have effect on objects that you
    already have. If you want to reload an object after you disabled, enabled
    or changed a filter, then you should clear the EM and re-fetch the object
    so the appropriate SQL will be executed.

Disabling Filters
-----------------
.. code-block:: php
    $filter = $em->getFilters()->disable("locale");
