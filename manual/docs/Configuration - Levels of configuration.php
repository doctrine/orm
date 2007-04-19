Doctrine has a three-level configuration structure. You can set configuration attributes in global, connection and table level.
If the same attribute is set on both lower level and upper level, the uppermost attribute will always be used. So for example
if user first sets default fetchmode in global level to Doctrine::FETCH_BATCH and then sets 'example' table fetchmode to Doctrine::FETCH_LAZY,
the lazy fetching strategy will be used whenever the records of 'example' table are being fetched.




*  Global level
    
        The attributes set in global level will affect every connection and every table in each connection.
    
*  Connection level
    
        The attributes set in connection level will take effect on each table in that connection.
    
*  Table level
    
        The attributes set in table level will take effect only on that table.
    

<code type="php">
// setting a global level attribute
$manager = Doctrine_Manager::getInstance();

$manager->setAttribute(Doctrine::ATTR_VLD, false);

// setting a connection level attribute
// (overrides the global level attribute on this connection)

$conn = $manager->openConnection(new PDO('dsn', 'username', 'pw'));

$conn->setAttribute(Doctrine::ATTR_VLD, true);

// setting a table level attribute
// (overrides the connection/global level attribute on this table)

$table = $conn->getTable('User');

$table->setAttribute(Doctrine::ATTR_LISTENER, new UserListener());
</code>
