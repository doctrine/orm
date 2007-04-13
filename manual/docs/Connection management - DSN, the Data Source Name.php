In order to connect to a database through Doctrine, you have to create a valid DSN - data source name. 

Doctrine supports both PEAR DB/MDB2 like data source names as well as PDO style data source names. The following section deals with PEAR like data source names. If you need more info about the PDO-style data source names see http://www.php.net/manual/en/function.PDO-construct.php.

The DSN consists in the following parts:

**phptype**: Database backend used in PHP (i.e. mysql , pgsql etc.)
**dbsyntax**: Database used with regards to SQL syntax etc.
**protocol**: Communication protocol to use ( i.e. tcp, unix etc.)
**hostspec**: Host specification (hostname[:port])
**database**: Database to use on the DBMS server
**username**: User name for login
**password**: Password for login
**proto_opts**: Maybe used with protocol
**option**: Additional connection options in URI query string format. options get separated by &. The Following table shows a non complete list of options:


**List of options**

|| //Name// || //Description// ||
|| charset || Some backends support setting the client charset.||
|| new_link || Some RDBMS do not create new connections when connecting to the same host multiple times. This option will attempt to force a new connection. ||

The DSN can either be provided as an associative array or as a string. The string format of the supplied DSN is in its fullest form:
`` phptype(dbsyntax)://username:password@protocol+hostspec/database?option=value  ``



Most variations are allowed:


phptype://username:password@protocol+hostspec:110//usr/db_file.db
phptype://username:password@hostspec/database
phptype://username:password@hostspec
phptype://username@hostspec
phptype://hostspec/database
phptype://hostspec
phptype:///database
phptype:///database?option=value&anotheroption=anothervalue
phptype(dbsyntax)
phptype



The currently supported database backends are: 
||//fbsql//||  -> FrontBase ||
||//ibase//||  -> InterBase / Firebird (requires PHP 5)  ||
||//mssql//||  -> Microsoft SQL Server (NOT for Sybase. Compile PHP --with-mssql) ||
||//mysql//||  -> MySQL  ||
||//mysqli//|| -> MySQL (supports new authentication protocol) (requires PHP 5) ||
||//oci8 //||  -> Oracle 7/8/9/10    ||
||//pgsql//||  -> PostgreSQL  ||
||//querysim//|| -> QuerySim   ||
||//sqlite//|| -> SQLite 2 ||
 


A second DSN format is supported phptype(syntax)://user:pass@protocol(proto_opts)/database
 


If your database, option values, username or password contain characters used to delineate DSN parts, you can escape them via URI hex encodings:  
``: = %3a``
``/ = %2f``
``@ = %40``
``+ = %2b``
``( = %28``
``) = %29``
``? = %3f``
``= = %3d``
``& = %26``
 



Warning 
Please note, that some features may be not supported by all database backends. 
 

Example
**Example 1.** Connect to database through a socket

mysql://user@unix(/path/to/socket)/pear


**Example 2.** Connect to database on a non standard port

pgsql://user:pass@tcp(localhost:5555)/pear


**Example 3.** Connect to SQLite on a Unix machine using options

sqlite:////full/unix/path/to/file.db?mode=0666


**Example 4.** Connect to SQLite on a Windows machine using options

sqlite:///c:/full/windows/path/to/file.db?mode=0666


**Example 5.** Connect to MySQLi using SSL

mysqli://user:pass@localhost/pear?key=client-key.pem&cert=client-cert.pem

 

