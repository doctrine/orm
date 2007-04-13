All variables must satisfy the following conditions:


* Variable names may only contain alphanumeric characters. Underscores are not permitted. Numbers are permitted in variable names but are discouraged.



* Variable names must always start with a lowercase letter and follow the "camelCaps" capitalization convention.



* Verbosity is encouraged. Variables should always be as verbose as practical. Terse variable names such as "$i" and "$n" are discouraged for anything other than the smallest loop contexts. If a loop contains more than 20 lines of code, the variables for the indices need to have more descriptive names.



* Within the framework certain generic object variables should always use the following names:
    

    *  Doctrine_Connection  -> //$conn//
    *  Doctrine_Collection  -> //$coll//
    *  Doctrine_Manager     -> //$manager//
    *  Doctrine_Query       -> //$query//
    *  Doctrine_Db          -> //$db//

    
    There are cases when more descriptive names are more appropriate (for example when multiple objects of the same class are used in same context),
    in that case it is allowed to use different names than the ones mentioned.



