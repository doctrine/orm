Doctrine supports aggregates and composites. When binding composites you can use methods Doctrine_Table::ownsOne() and Doctrine_Table::ownsMany(). When binding
aggregates you can use methods Doctrine_Table::hasOne() and Doctrine_Table::hasMany().
<br \><br \>
In Doctrine if you bind an Email to a User using ownsOne or ownsMany methods, everytime User record calls save or delete the associated
Email record is also saved/deleted.
<br \><br \>
Then again if you bind an Email to a User using hasOne or hasMany methods, everytime User record calls save or delete the associated
Email record is NOT saved/deleted.
