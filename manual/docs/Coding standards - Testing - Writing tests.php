**CLASSES**



* 
All test classes should be referring to a class or specific testing aspect of some class.



For example //Doctrine_Record_TestCase// is a valid name since its referring to class named
//Doctrine_Record//.



//Doctrine_Record_State_TestCase// is also a valid name since its referring to testing the state aspect
of the Doctrine_Record class.



However something like //Doctrine_PrimaryKey_TestCase// is not valid since its way too generic.



*  Every class should have atleast one TestCase equivalent
*  All testcase classes should inherit Doctrine_UnitTestCase




**METHODS**



* All methods should support agile documentation; if some method failed it should be evident from the name of the test method what went wrong.
Also the test method names should give information of the system they test.


For example //Doctrine_Export_Pgsql_TestCase::testCreateTableSupportsAutoincPks()// is a valid test method name. Just by looking at it we know
what it is testing. Also we can run agile documentation tool to get little up-to-date system information.



NOTE: Commonly used testing method naming convention TestCase::test[methodName] is *NOT* allowed in Doctrine. So in this case
<b class='title'>Doctrine_Export_Pgsql_TestCase::testCreateTable()** would not be allowed!



* Test method names can often be long. However the content within the methods should rarely be more than dozen lines long. If you need several assert-calls
divide the method into smaller methods.

**ASSERTIONS**



* There should never be assertions within any loops and rarely within functions.

