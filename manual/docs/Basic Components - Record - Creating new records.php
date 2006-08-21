There are couple of ways for creating new records. Propably the easiest is using
native php new -operator. The other ways are calling Doctrine_Table::create() or Doctrine_Connection::create().
The last two exists only for backward compatibility. The recommended way of creating new objects is the new operator.
