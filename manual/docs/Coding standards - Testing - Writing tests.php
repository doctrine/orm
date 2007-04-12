<b>CLASSES</b><br \><br \>
<ul>
<li \>
All test classes should be referring to a class or specific testing aspect of some class.
<br \><br \>
For example <i>Doctrine_Record_TestCase</i> is a valid name since its referring to class named
<i>Doctrine_Record</i>.
<br \><br \>
<i>Doctrine_Record_State_TestCase</i> is also a valid name since its referring to testing the state aspect
of the Doctrine_Record class.
<br \><br \>
However something like <i>Doctrine_PrimaryKey_TestCase</i> is not valid since its way too generic.
<br \><br \>
<li \> Every class should have atleast one TestCase equivalent
<li \> All testcase classes should inherit Doctrine_UnitTestCase
</ul>
<br \><br \>
<b>METHODS</b><br \><br \>
<ul>
<li \>All methods should support agile documentation; if some method failed it should be evident from the name of the test method what went wrong.
Also the test method names should give information of the system they test.<br \><br \>
For example <i>Doctrine_Export_Pgsql_TestCase::testCreateTableSupportsAutoincPks()</i> is a valid test method name. Just by looking at it we know
what it is testing. Also we can run agile documentation tool to get little up-to-date system information.
<br \><br \>
NOTE: Commonly used testing method naming convention TestCase::test[methodName] is *NOT* allowed in Doctrine. So in this case
<b class='title'>Doctrine_Export_Pgsql_TestCase::testCreateTable()</b> would not be allowed!
<br \><br \>
<li \>Test method names can often be long. However the content within the methods should rarely be more than dozen lines long. If you need several assert-calls
divide the method into smaller methods.
</ul>
<b>ASSERTIONS</b><br \><br \>
<ul>
<li \>There should never be assertions within any loops and rarely within functions.

