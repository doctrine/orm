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
