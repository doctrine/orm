Switching between sessions in Doctrine is very easy, you just call Doctrine_Manager::setCurrentSession() method.
You can access the session by calling Doctrine_Manager::getSession() or Doctrine_Manager::getCurrentSession() if you only 
want to get the current session.
