Extending DQL in Doctrine ORM: Custom AST Walkers
===============================================

.. sectionauthor:: Benjamin Eberlei <kontakt@beberlei.de>

The Doctrine Query Language (DQL) is a proprietary sql-dialect that
substitutes tables and columns for Entity names and their fields.
Using DQL you write a query against the database using your
entities. With the help of the metadata you can write very concise,
compact and powerful queries that are then translated into SQL by
the Doctrine ORM.

In Doctrine 1 the DQL language was not implemented using a real
parser. This made modifications of the DQL by the user impossible.
Doctrine ORM in contrast has a real parser for the DQL language,
which transforms the DQL statement into an
`Abstract Syntax Tree <https://en.wikipedia.org/wiki/Abstract_syntax_tree>`_
and generates the appropriate SQL statement for it. Since this
process is deterministic Doctrine heavily caches the SQL that is
generated from any given DQL query, which reduces the performance
overhead of the parsing process to zero.

You can modify the Abstract syntax tree by hooking into DQL parsing
process by adding a Custom Tree Walker. A walker is an interface
that walks each node of the Abstract syntax tree, thereby
generating the SQL statement.

There are two types of custom tree walkers that you can hook into
the DQL parser:


-  An output walker. This one actually generates the SQL, and there
   is only ever one of them. We implemented the default SqlWalker
   implementation for it.
-  A tree walker. There can be many tree walkers, they cannot
   generate the SQL, however they can modify the AST before its
   rendered to SQL.

Now this is all awfully technical, so let me come to some use-cases
fast to keep you motivated. Using walker implementation you can for
example:

-  Modify the Output walker to get the raw SQL via ``Query->getSQL()``
   with interpolated parameters.
-  Modify the AST to generate a Count Query to be used with a
   paginator for any given DQL query.
-  Modify the Output Walker to generate vendor-specific SQL
   (instead of ANSI).
-  Modify the AST to add additional where clauses for specific
   entities (example ACL, country-specific content...)
-  Modify the Output walker to pretty print the SQL for debugging
   purposes.

In this cookbook-entry I will show examples of the first three
points. There are probably much more use-cases.

Generic count query for pagination
----------------------------------

Say you have a blog and posts all with one category and one author.
A query for the front-page or any archive page might look something
like:

.. code-block:: sql

    SELECT p, c, a FROM BlogPost p JOIN p.category c JOIN p.author a WHERE ...

Now in this query the blog post is the root entity, meaning it's the
one that is hydrated directly from the query and returned as an
array of blog posts. In contrast the comment and author are loaded
for deeper use in the object tree.

A pagination for this query would want to approximate the number of
posts that match the WHERE clause of this query to be able to
predict the number of pages to show to the user. A draft of the DQL
query for pagination would look like:

.. code-block:: sql

    SELECT count(DISTINCT p.id) FROM BlogPost p JOIN p.category c JOIN p.author a WHERE ...

Now you could go and write each of these queries by hand, or you
can use a tree walker to modify the AST for you. Let's see how the
API would look for this use-case:

.. code-block:: php

    <?php
    $pageNum = 1;
    $query = $em->createQuery($dql);
    $query->setFirstResult( ($pageNum-1) * 20)->setMaxResults(20);

    $totalResults = Paginate::count($query);
    $results = $query->getResult();

The ``Paginate::count(Query $query)`` looks like:

.. code-block:: php

    <?php
    class Paginate
    {
        static public function count(Query $query)
        {
            /** @var Query $countQuery */
            $countQuery = clone $query;

            $countQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('DoctrineExtensions\Paginate\CountSqlWalker'));
            $countQuery->setFirstResult(null)->setMaxResults(null);

            return $countQuery->getSingleScalarResult();
        }
    }

It clones the query, resets the limit clause first and max results
and registers the ``CountSqlWalker`` custom tree walker which
will modify the AST to execute a count query. The walkers
implementation is:

.. code-block:: php

    <?php
    class CountSqlWalker extends TreeWalkerAdapter
    {
        /**
         * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
         *
         * @return string The SQL.
         */
        public function walkSelectStatement(SelectStatement $AST)
        {
            $parent = null;
            $parentName = null;
            foreach ($this->_getQueryComponents() as $dqlAlias => $qComp) {
                if ($qComp['parent'] === null && $qComp['nestingLevel'] == 0) {
                    $parent = $qComp;
                    $parentName = $dqlAlias;
                    break;
                }
            }

            $pathExpression = new PathExpression(
                PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $parentName,
                $parent['metadata']->getSingleIdentifierFieldName()
            );
            $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

            $AST->selectClause->selectExpressions = array(
                new SelectExpression(
                    new AggregateExpression('count', $pathExpression, true), null
                )
            );
        }
    }

This will delete any given select expressions and replace them with
a distinct count query for the root entities primary key. This will
only work if your entity has only one identifier field (composite
keys won't work).

Modify the Output Walker to generate Vendor specific SQL
--------------------------------------------------------

Most RMDBS have vendor-specific features for optimizing select
query execution plans. You can write your own output walker to
introduce certain keywords using the Query Hint API. A query hint
can be set via ``Query::setHint($name, $value)`` as shown in the
previous example with the ``HINT_CUSTOM_TREE_WALKERS`` query hint.

We will implement a custom Output Walker that allows to specify the
``SQL_NO_CACHE`` query hint.

.. code-block:: php

    <?php
    $dql = "SELECT p, c, a FROM BlogPost p JOIN p.category c JOIN p.author a WHERE ...";
    $query = $m->createQuery($dql);
    $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'DoctrineExtensions\Query\MysqlWalker');
    $query->setHint("mysqlWalker.sqlNoCache", true);
    $results = $query->getResult();

Our ``MysqlWalker`` will extend the default ``SqlWalker``. We will
modify the generation of the SELECT clause, adding the
``SQL_NO_CACHE`` on those queries that need it:

.. code-block:: php

    <?php
    class MysqlWalker extends SqlWalker
    {
         /**
         * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
         *
         * @param $selectClause
         * @return string The SQL.
         */
        public function walkSelectClause($selectClause)
        {
            $sql = parent::walkSelectClause($selectClause);

            if ($this->getQuery()->getHint('mysqlWalker.sqlNoCache') === true) {
                if ($selectClause->isDistinct) {
                    $sql = str_replace('SELECT DISTINCT', 'SELECT DISTINCT SQL_NO_CACHE', $sql);
                } else {
                    $sql = str_replace('SELECT', 'SELECT SQL_NO_CACHE', $sql);
                }
            }

            return $sql;
        }
    }

Writing extensions to the Output Walker requires a very deep
understanding of the DQL Parser and Walkers, but may offer your
huge benefits with using vendor specific features. This would still
allow you write DQL queries instead of NativeQueries to make use of
vendor specific features.

Modify the Output Walker to get the raw SQL with interpolated parameters
--------------------------------------------------------

Sometimes we may want to log or trace the raw SQL being generated from its DQL,
``$query->getSQL()`` will give us the prepared statment being passed to database
with all values of SQL params being replaced by positional ``?`` or named ``:name``
as params are innerpolated into prepared statment by the database while executing the SQL.
``$query->getParameters()`` will give us details about SQL params that we've provided.
So we can create an output walker to interpolate all SQL params that will be
passed into prepared statement in PHP before database handle them internally:

.. code-block:: php

    <?php
    use Doctrine\DBAL\ArrayParameterType;
    use Doctrine\DBAL\ParameterType;
    use Doctrine\DBAL\Types\BooleanType;
    use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
    use Doctrine\DBAL\Types\Type;
    use Doctrine\ORM\Query\AST;
    use Doctrine\ORM\Query\SqlOutputWalker;

    class InterpolateParametersSQLOutputWalker extends SqlOutputWalker
    {
        /** {@inheritdoc} */
        public function walkInputParameter(AST\InputParameter $inputParam): string
        {
            $parameter = $this->getQuery()->getParameter($inputParam->name);
            if ($parameter === null) {
                return '?';
            }

            $value = $parameter->getValue();
            /** @var ParameterType|ArrayParameterType|int|string $typeName */
            /** @see \Doctrine\ORM\Query\ParameterTypeInferer::inferType() */
            $typeName = $parameter->getType();
            $platform = $this->getConnection()->getDatabasePlatform();
            $processParameterType = static fn(ParameterType $type) => static fn($value): string =>
                (match ($type) { /** @see Type::getBindingType() */
                    ParameterType::NULL => 'NULL',
                    ParameterType::INTEGER => $value,
                    ParameterType::BOOLEAN => (new BooleanType())->convertToDatabaseValue($value, $platform),
                    ParameterType::STRING, ParameterType::ASCII => $platform->quoteStringLiteral($value),
                    default => throw new ValueNotConvertible($value, $type->name)
                });

            if (is_string($typeName) && Type::hasType($typeName)) {
                return Type::getType($typeName)->convertToDatabaseValue($value, $platform);
            }
            if ($typeName instanceof ParameterType) {
                return $processParameterType($typeName)($value);
            }
            if ($typeName instanceof ArrayParameterType && is_array($value)) {
                $type = ArrayParameterType::toElementParameterType($typeName);
                return implode(', ', array_map($processParameterType($type), $value));
            }

            throw new ValueNotConvertible($value, $typeName);
        }
    }

Then you may get the raw SQL with this output walker:

.. code-block:: php

    <?php
    $query
        ->where('t.int IN (:ints)')->setParameter(':ints', [1, 2])
        ->orWhere('t.string IN (?0)')->setParameter(0, ['3', '4'])
        ->orWhere("t.bool = ?1")->setParameter('?1', true)
        ->orWhere("t.string = :string")->setParameter(':string', 'ABC')
        ->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER, InterpolateParametersSQLOutputWalker::class)
        ->getSQL();

The where clause of the returned SQL should be like:

.. code-block:: sql

    WHERE t0_.int IN (1, 2)
       OR t0_.string IN ('3', '4')
       OR t0_.bool = 1
       OR t0_.string = 'ABC'
