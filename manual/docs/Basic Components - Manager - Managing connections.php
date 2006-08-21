Switching between connections in Doctrine is very easy, you just call Doctrine_Manager::setCurrentConnection() method.
You can access the connection by calling Doctrine_Manager::getConnection() or Doctrine_Manager::getCurrentConnection() if you only
want to get the current connection.
