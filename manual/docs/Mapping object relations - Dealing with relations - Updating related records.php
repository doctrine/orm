You can update the related records by calling save for each related object / collection individually or by calling
save on the object that owns the other objects. You can also call Doctrine_Session::flush which saves all pending objects.
