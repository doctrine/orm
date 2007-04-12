Doctrine_Connection is a wrapper for database connection. It handles several things: 
<ul>
<li \> Handles database portability things missing from PDO (eg. LIMIT / OFFSET emulation)

<li \> Keeps track of Doctrine_Table objects

<li \> Keeps track of records

<li \> Keeps track of records that need to be updated / inserted / deleted

<li \> Handles transactions and transaction nesting

<li \> Handles the actual querying of the database in the case of INSERT / UPDATE / DELETE operations

<li \> Can query the database using the DQL API (see Doctrine_Query)

<li \> Optionally validates transactions using Doctrine_Validator and gives
full information of possible errors.

</ul>


