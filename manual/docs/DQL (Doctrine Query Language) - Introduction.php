Doctrine Query Language(DQL) is an Object Query Language created for helping users in complex object retrieval.
You should always consider using DQL(or raw SQL) when retrieving relational data efficiently (eg. when fetching users and their phonenumbers).
<br \><br \>
When compared to using raw SQL, DQL has several benefits: <br \>
    <ul>
    <li \>From the start it has been designed to retrieve records(objects) not result set rows
    </ul>
    <ul>
    <li \>DQL understands relations so you don't have to type manually sql joins and join conditions
    </ul>
    <ul>
    <li \>DQL has some very complex built-in algorithms like (the record limit algorithm) which can help
    developer to efficiently retrieve objects
    </ul>
    <ul>
    <li \>It supports some many functions that help dealing with one-to-many, many-to-many relational data with conditional fetching.
    </ul>

If the power of DQL isn't enough, you should consider using the rawSql API for object population.

Standard DQL query consists of the following parts:
    <ul>
    <li \> a FROM clause, which provides declarations that designate the domain to which the expressions
specified in the other clauses of the query apply.
    </ul>
    <ul>
    <li \> an optional WHERE clause, which may be used to restrict the results that are returned by the
query.
    </ul>
    <ul>
    <li \> an optional GROUP BY clause, which allows query results to be aggregated in terms of
groups.
    </ul>
    <ul>
    <li \> an optional HAVING clause, which allows filtering over aggregated groups.
    </ul>
    <ul>
    <li \> an optional ORDER BY clause, which may be used to order the results that are returned by the
query.
    </ul>
<br \>
In BNF syntax, a select statement is defined as:
          select_statement :: = select_clause from_clause [where_clause] [groupby_clause]
                                [having_clause] [orderby_clause]
<br \>
A select statement must always have a SELECT and a FROM clause. The square brackets [] indicate
that the other clauses are optional.
