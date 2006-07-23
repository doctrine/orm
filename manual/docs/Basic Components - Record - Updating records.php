Updating objects is very easy, you just call the Doctrine_Record::save() method. The other way
(perhaps even easier) is to call Doctrine_Session::flush() which saves all objects. It should be noted though
that flushing is a much heavier operation than just calling save method.
