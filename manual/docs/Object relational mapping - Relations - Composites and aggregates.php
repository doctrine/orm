Doctrine supports aggregates and composites. When binding composites you can use methods Doctrine_Record::ownsOne() and Doctrine_Record::ownsMany(). When binding
aggregates you can use methods Doctrine_Record::hasOne() and Doctrine_Record::hasMany(). Basically using the owns* methods is like adding a database level ON CASCADE DELETE
constraint on related component with an exception that doctrine handles the deletion in application level.
<br \><br \>
In Doctrine if you bind an Email to a User using ownsOne or ownsMany methods, everytime User record calls delete the associated
Email record is also deleted.
<br \><br \>
Then again if you bind an Email to a User using hasOne or hasMany methods, everytime User record calls delete the associated
Email record is NOT deleted.
