In Doctrine you may express your queries in the native SQL dialect of your database. 
This is useful if you want to use the full power of your database vendor's features (like query hints or the CONNECT keyword in Oracle).



It should be noted that not all the sql is portable. So when you make database portable applications you might want to use the DQL API instead.

