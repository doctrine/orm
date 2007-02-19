Getting started
    Requirements
    Installation
    Compiling
    Starting new project
    Working with existing databases
        Introduction
        Making the first import
        Import options
Connection management
    Opening a new connection
    Lazy-connecting to database
    Managing connections
    Connection-component binding
Object relational mapping
    Introduction
    Table and class naming
    Columns
        Column naming
        Column aliases
        Default values
        Data types
            Introduction
            Type modifiers
            Boolean
            Integer
            Float
            String
            Array
            Object
            Blob
            Clob
            Timestamp
            Time
            Date
            Enum
            Gzip
        About type conversion
    Constraints and validators
        Notnull
        Max - Min
    Record identifiers
        Introduction
        Autoincremented
        Natural
        Composite
        Sequence
    Indexes
        Introduction
        Adding indexes
        Index options
        Special indexes
    Relations
        Introduction
        Relation aliases
        Foreign key associations
            One-to-One
            One-to-Many, Many-to-One
            Tree structure
        Join table associations
            One-to-One
            One-to-Many, Many-to-One
            Many-to-Many
            Self-referencing
        Inheritance
            One table many classes
            One table one class
            Column aggregation
    Hierarchical data
        Introduction
            About
            Setting up
            Node interface
            Tree interface
            Traversing or Walking Trees
            Read me
        Adjacency list
            Introduction
        Nested set
            Introduction
            Setting up
            Tree options
            Node support
            Tree support
            Read me
        Materialized path
            Introduction
        Examples
Working with objects
    Dealing with relations
        Creating related records
        Retrieving related records
        Updating related records
        Deleting related records
        Working with associations
    Component overview
        Manager
            Introduction
            Opening a new connection
            Managing connections
        Connection
            Introduction
            Available drivers
            Getting a table object
            Flushing the connection
            Querying the database
            Getting connection state
        Record
            Introduction
            Creating new records
            Retrieving existing records
            Accessing properties
            Updating records
            Deleting records
            Getting record state
            Getting object copy
            Serializing
            Checking Existence
            Callbacks
        Collection
            Introduction
            Accessing elements
            Adding new elements
            Getting collection count
            Saving the collection
            Deleting collection
            Key mapping
            Loading related records
            Collection expanding
        Table
            Introduction
            Getting table information
            Finder methods
            Custom table classes
            Custom finders
            Getting relation objects
    Fetching objects    
Configuration
    Introduction
    Levels of configuration
    Setting attributes
        Portability
        Identifier quoting
        Table creation
        Fetching strategy
        Batch size
        Session lockmode
        Event listener
        Validation
        Offset collection limit
Advanced components
    Eventlisteners
        Introduction
        Creating new listener
        List of events
        Listening events
        Chaining
        AccessorInvoker
        Creating a logger
    Validators
        Introduction
        More Validation
        Valid or Not Valid
        List of predefined validators
    View
        Intoduction
        Managing views
        Using views
    Cache
        Introduction
        Query cache
    Locking Manager
        Introduction
        Examples
        Planned
        Technical Details
        Maintainer
    Db_Profiler
        Introduction
        Basic usage
        Advanced usage
    Hook
        Introduction
        Building queries
        List of parsers
    Query
        Introduction
        selecting tables
        limiting the query results
        setting query conditions
        HAVING conditions
        sorting query results
    RawSql
        Introduction
        Using SQL
        Adding components
        Method overloading
    Db
        Introduction
        Connecting to a database
        Using event listeners
        Chaining listeners
    Exceptions
        Overview
        List of exceptions
DQL (Doctrine Query Language)
    Introduction
    SELECT queries
        DISTINCT keyword
        Aggregate values
    UPDATE queries
    DELETE queries
    FROM clause
    WHERE clause
    Conditional expressions
        Literals
        Input parameters
        Operators and operator precedence
        Between expressions
        In expressions
        Like Expressions
        Null Comparison Expressions
        Empty Collection Comparison Expressions
        Collection Member Expressions
        Exists Expressions
        All and Any Expressions
        Subqueries
    Functional Expressions
        String functions
        Arithmetic functions
        Datetime functions
        Collection functions
    GROUP BY, HAVING clauses
    ORDER BY clause
        Introduction
        Sorting by an aggregate value
        Using random order
    LIMIT and OFFSET clauses
        Introduction
        Driver portability
        The limit-subquery-algorithm
    Examples
    BNF
Native SQL
    Scalar queries
    Component queries
    Fetching multiple components
Transactions
    Introduction
    Unit of work
    Nesting
    Savepoints
    Locking strategies
        Pessimistic locking
        Optimistic locking
    Lock modes
    Isolation levels
    Deadlocks
Caching
    Introduction
    Availible options
    Drivers
        Memcache
        APC
        Sqlite
Database abstraction
    Modules
        Export
            Introduction
            Creating new table
            Altering table
        Import
            Introduction
            Getting table info
            Getting foreign key info
            Getting view info
        Util
            Using explain
        DataDict
            Getting portable type
            Getting database declaration
            Reserved keywords
    Drivers
        Oracle
            Making unsuported functions work
        Mysql
            Tips and tricks
Technology
    Architecture
    Design patterns used
    Speed
    Internal optimizations
        DELETE
        INSERT
        UPDATE
Real world examples
    User management system
    Forum application
    Album lister
Coding standards
    Overview
        Scope
        Goals
    PHP File Formatting
        General
        Indentation
        Maximum line length
        Line termination
    Naming Conventions
        Classes
        Interfaces
        Filenames
        Functions and methods
        Variables
        Constants
        Record columns
    Coding Style
        PHP code demarcation
        Strings
        Arrays
        Classes
        Functions and methods
        Control statements
        Inline documentation
    Testing
        Writing tests

