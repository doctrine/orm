<?php
include("top.php");
?>


<table width="100%" cellspacing=0 cellpadding=0>
    <tr>
        <td width=50>
        <td>
        <td align="left" valign="top">
            <table width="100%" cellspacing=1 cellpadding=1>
            <tr>
                <td bgcolor="white">
                <img src="images/logo.jpg" align="left"><b class="title">Doctrine - PHP Data Persistence and ORM Tool</b>
                <hr>
                </td>
            </tr>
            <tr>
            <td>


<ul>
<b class='title'>GENERAL FEATURES</b>
<ul>
<li \> Fully object-oriented following best practices and design patterns
<li \>Multiple databases
<li \>Database connection pooling with connection-record -registry
<li \>Runtime configuration (no XML needed!)
<li \>Very modular structure (only uses the needed features)
<li \>The whole framework can be compiled into a single file
<li \>Leveled configuration (attributes can be set at global, connection and table levels)
</ul>
<br \>
<b class='title'>DATABASE ABSTRACTION</b>
<ul>
<li \>A DSN (data source name) or array format for specifying database servers
<li \>Datatype abstraction and on demand datatype conversion
<li \>supports PDO
<li \>Database query profiling
<li \>Query caching
<li \>Sequence / autoincrement emulation
<li \>Replace emulation
<li \>RDBMS management methods (creating, dropping, altering)
<li \>SQL function call abstraction
<li \>SQL expression abstraction
<li \>Pattern matching abstraction
<li \>Portable error codes
<li \>Nested transactions
<li \>Transaction isolation abstraction
<li \>Transaction savepoint abstraction
<li \>Index/Unique Key/Primary Key support
<li \>Ability to read the information schema
<li \>Reverse engineering schemas from an existing database
<li \>LIMIT / OFFSET emulation
</ul>
<br \>
<b class='title'>OBJECT RELATIONAL MAPPING</b>:
<ul>
    <b class='title'>General features</b>
    <li \>Validators
    <li \>Transactional errorStack for easy retrieval of all errors
    <li \>EventListeners
    <li \>UnitOfWork pattern (easy saving of all pending objects)
    <li \>Uses ActiveRecord pattern
    <li \>State-wise records and transactions
    <li \>Importing existing database schemas to Doctrine ActiveRecord objects
    <li \>Exporting Doctrine ActiveRecords to database (= automatic table creation)
    <br \><br \>
    <b class='title'>Mapping</b>
    <li \>Composite, Natural, Autoincremented and Sequential identifiers
    <li \>PHP Array / Object data types for columns (automatic serialization/unserialization) 
    <li \>Gzip datatype for all databases
    <li \>Emulated enum datatype for all databases
    <li \>Datatype abstraction
    <li \>Column aggregation inheritance
    <li \>One-class-one-table inheritance as well as One-table
    <li \>One-to-many, many-to-one, one-to-one and many-to-many relations
    <li \>Self-referencing relations even for association table relations
    <li \>Relation aliases
    <br \><br \>
    <b class='title'>Object population</b>
    <li \>DQL (Doctrine Query Language), an EJB 3 spec compliant OQL
    <li \><b>The limit-subquery-algorithm</b>
    <li \>OO-style query API for both DQL and raw SQL
    <li \>Object population from database views
    <li \>Object population through raw SQL
    <br \><br \>
    <b class='title'>Transactions and locking</b>
    <li \>Pessimistic offline locking
    <li \>Savepoints, transaction isolation levels and nested transactions
    <li \>Transactional query optimization (gathering of DELETE statements)
 </ul>
</ul>
        </td>
        </tr>
    </tr>
</table>
