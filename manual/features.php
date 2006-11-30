<pre>
FEATURES:

GENERAL FEATURES
    - Fully object-oriented following best practices and design patterns
    - Multiple databases
    - Database connection pooling with connection-record -registry
    - Runtime configuration (no XML needed!)
    - Very modular structure (only uses the needed features)
    - The whole framework can be compiled into a single file
    - Leveled configuration (attributes can be set at global, connection and table levels)

DATABASE ABSTRACTION:
    - A DSN (data source name) or array format for specifying database servers
    - Datatype abstraction and on demand datatype conversion
    - supports PDO
    - Database query profiling
    - Query caching
    - Sequence / autoincrement emulation
    - Replace emulation
    - RDBMS management methods (creating, dropping, altering)
    - SQL function call abstraction
    - SQL expression abstraction
    - Pattern matching abstraction
    - Portable error codes
    - Nested transactions
    - Transaction isolation abstraction
    - Transaction savepoint abstraction
    - Index/Unique Key/Primary Key support
    - Ability to read the information schema
    - Reverse engineering schemas from an existing database
    - LIMIT / OFFSET emulation


OBJECT RELATIONAL MAPPING:
    General features:
        - Validators
        - Transactional errorStack for easy retrieval of all errors
        - EventListeners
        - UnitOfWork pattern (easy saving of all pending objects)
        - Uses ActiveRecord pattern
        - State-wise records and transactions
        - Importing existing database schemas to Doctrine ActiveRecord objects
        - Exporting Doctrine ActiveRecords to database (= automatic table creation)

    Mapping:
        - Composite, Natural, Autoincremented and Sequential identifiers
        - PHP Array / Object data types for columns (automatic serialization/unserialization) 
        - Gzip datatype for all databases
        - Emulated enum datatype for all databases
        - Datatype abstraction
        - Column aggregation inheritance
        - One-class-one-table inheritance as well as One-table
        - One-to-many, many-to-one, one-to-one and many-to-many relations
        - Self-referencing relations even for association table relations
        - Relation aliases

    Object population:
        - DQL (Doctrine Query Language), an EJB 3 spec compliant OQL
        - <b>The limit-subquery-algorithm</b>
        - OO-style query API for both DQL and raw SQL
        - Object population from database views
        - Object population through raw SQL

    Transactions and locking:
        - Pessimistic offline locking
        - Savepoints, transaction isolation levels and nested transactions
        - Transactional query optimization (gathering of DELETE statements)
</pre>
