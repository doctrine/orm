Doctrine_Connection is a wrapper for database connection. It handles several things: 

*  Handles database portability things missing from PDO (eg. LIMIT / OFFSET emulation)

*  Keeps track of Doctrine_Table objects

*  Keeps track of records

*  Keeps track of records that need to be updated / inserted / deleted

*  Handles transactions and transaction nesting

*  Handles the actual querying of the database in the case of INSERT / UPDATE / DELETE operations

*  Can query the database using the DQL API (see Doctrine_Query)

*  Optionally validates transactions using Doctrine_Validator and gives
full information of possible errors.




