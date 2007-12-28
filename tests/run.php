f<?php
error_reporting(E_ALL | E_STRICT);
ini_set('max_execution_time', 900);
ini_set("date.timezone", "GMT+0");

require_once(dirname(__FILE__) . '/DoctrineTest.php');
require_once dirname(__FILE__) . '/../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register(array('DoctrineTest','autoload'));

$test = new DoctrineTest();
//TICKET test cases
$tickets = new GroupTest('Tickets tests', 'tickets');
$tickets->addTestCase(new Doctrine_Ticket_Njero_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_428_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_480_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_587_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_576_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_583_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_626B_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_626C_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_642_TestCase());
//If you write a ticket testcase add it here like shown above!
$tickets->addTestCase(new Doctrine_Ticket_438_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_638_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_673_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_626D_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_697_TestCase());
$test->addTestCase($tickets);

// Connection drivers (not yet fully tested)
$driver = new GroupTest("Driver tests", 'driver');
$driver->addTestCase(new Doctrine_Connection_Pgsql_TestCase());
$driver->addTestCase(new Doctrine_Connection_Oracle_TestCase());
$driver->addTestCase(new Doctrine_Connection_Sqlite_TestCase());
$driver->addTestCase(new Doctrine_Connection_Mssql_TestCase()); 
$driver->addTestCase(new Doctrine_Connection_Mysql_TestCase());
$driver->addTestCase(new Doctrine_Connection_Firebird_TestCase());
$driver->addTestCase(new Doctrine_Connection_Informix_TestCase());
$test->addTestCase($driver);

// Transaction module (FULLY TESTED)
$transaction = new GroupTest("Transaction tests", 'transaction');
$transaction->addTestCase(new Doctrine_Transaction_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Firebird_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Informix_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Mysql_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Mssql_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Pgsql_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Oracle_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Sqlite_TestCase());
$test->addTestCase($transaction);

// DataDict module (FULLY TESTED)
$data_dict = new GroupTest('DataDict tests', 'data_dict');
$data_dict->addTestCase(new Doctrine_DataDict_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Firebird_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Informix_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Mysql_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Mssql_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Pgsql_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Oracle_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Sqlite_TestCase());
$test->addTestCase($data_dict);

// Sequence module (not yet fully tested)
$sequence = new GroupTest('Sequence tests','sequence');
$sequence->addTestCase(new Doctrine_Sequence_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Firebird_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Informix_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Mysql_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Mssql_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Pgsql_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Oracle_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Sqlite_TestCase());
$test->addTestCase($sequence);

// Export module (not yet fully tested)
$export = new GroupTest('Export tests','export');
//$export->addTestCase(new Doctrine_Export_Reporter_TestCase());
$export->addTestCase(new Doctrine_Export_Firebird_TestCase());
$export->addTestCase(new Doctrine_Export_Informix_TestCase());
$export->addTestCase(new Doctrine_Export_TestCase());
$export->addTestCase(new Doctrine_Export_Mssql_TestCase());
$export->addTestCase(new Doctrine_Export_Pgsql_TestCase());
$export->addTestCase(new Doctrine_Export_Oracle_TestCase());
$export->addTestCase(new Doctrine_Export_Record_TestCase());
$export->addTestCase(new Doctrine_Export_Mysql_TestCase());
$export->addTestCase(new Doctrine_Export_Sqlite_TestCase());
$test->addTestCase($export);

//$test->addTestCase(new Doctrine_CascadingDelete_TestCase());

// Import module (not yet fully tested)
$import = new GroupTest('Import tests','import');
//$import->addTestCase(new Doctrine_Import_TestCase());
$import->addTestCase(new Doctrine_Import_Firebird_TestCase());
$import->addTestCase(new Doctrine_Import_Informix_TestCase());
$import->addTestCase(new Doctrine_Import_Mysql_TestCase());
$import->addTestCase(new Doctrine_Import_Mssql_TestCase());
$import->addTestCase(new Doctrine_Import_Pgsql_TestCase());
$import->addTestCase(new Doctrine_Import_Oracle_TestCase());
$import->addTestCase(new Doctrine_Import_Sqlite_TestCase());
$test->addTestCase($import);

// Expression module (not yet fully tested)
$expression = new GroupTest('Expression tests','expression');
$expression->addTestCase(new Doctrine_Expression_TestCase());
$expression->addTestCase(new Doctrine_Expression_Driver_TestCase());
$expression->addTestCase(new Doctrine_Expression_Firebird_TestCase());
$expression->addTestCase(new Doctrine_Expression_Informix_TestCase());
$expression->addTestCase(new Doctrine_Expression_Mysql_TestCase());
$expression->addTestCase(new Doctrine_Expression_Mssql_TestCase());
$expression->addTestCase(new Doctrine_Expression_Pgsql_TestCase());
$expression->addTestCase(new Doctrine_Expression_Oracle_TestCase());
$expression->addTestCase(new Doctrine_Expression_Sqlite_TestCase());
$test->addTestCase($expression);

// Core
$core = new GroupTest('Core tests: Access, Configurable, Manager, Connection, Table, UnitOfWork, Collection, Hydrate, Tokenizer','core');
$core->addTestCase(new Doctrine_Access_TestCase());
//$core->addTestCase(new Doctrine_Configurable_TestCase());
$core->addTestCase(new Doctrine_Manager_TestCase());
$core->addTestCase(new Doctrine_Connection_TestCase());
$core->addTestCase(new Doctrine_Table_TestCase());
$core->addTestCase(new Doctrine_UnitOfWork_TestCase());
//$core->addTestCase(new Doctrine_Collection_TestCase());
$core->addTestCase(new Doctrine_Collection_Snapshot_TestCase());
$core->addTestCase(new Doctrine_Hydrate_FetchMode_TestCase());
$core->addTestCase(new Doctrine_Tokenizer_TestCase());
//$core->addTestCase(new Doctrine_Collection_Offset_TestCase());
//$core->addTestCase(new Doctrine_BatchIterator_TestCase());
$core->addTestCase(new Doctrine_Hydrate_TestCase());
$test->addTestCase($core);

// Relation handling
$relation = new GroupTest('Relation tests: includes TreeStructure','relation');
$relation->addTestCase(new Doctrine_TreeStructure_TestCase());
$relation->addTestCase(new Doctrine_Relation_TestCase());
//$relation->addTestCase(new Doctrine_Relation_Access_TestCase());
//$relation->addTestCase(new Doctrine_Relation_ManyToMany_TestCase());
$relation->addTestCase(new Doctrine_Relation_ManyToMany2_TestCase());
$relation->addTestCase(new Doctrine_Relation_OneToMany_TestCase());
$relation->addTestCase(new Doctrine_Relation_Nest_TestCase());
$relation->addTestCase(new Doctrine_Relation_OneToOne_TestCase());
$relation->addTestCase(new Doctrine_Relation_Parser_TestCase());
$test->addTestCase($relation);

// Datatypes
$data_types = new GroupTest('DataTypes tests: Enum and Boolean','data_types');
$data_types->addTestCase(new Doctrine_DataType_Enum_TestCase());
$data_types->addTestCase(new Doctrine_DataType_Boolean_TestCase());
$test->addTestCase($data_types);

// Utility components
$plugins = new GroupTest('Plugin tests: View, Validator, Hook','plugins');
//$utility->addTestCase(new Doctrine_PessimisticLocking_TestCase());
//$plugins->addTestCase(new Doctrine_Plugin_TestCase());
$plugins->addTestCase(new Doctrine_View_TestCase());
$plugins->addTestCase(new Doctrine_AuditLog_TestCase());
$plugins->addTestCase(new Doctrine_Validator_TestCase());
$plugins->addTestCase(new Doctrine_Validator_Future_TestCase());
$plugins->addTestCase(new Doctrine_Validator_Past_TestCase());
$plugins->addTestCase(new Doctrine_Hook_TestCase());
$plugins->addTestCase(new Doctrine_I18n_TestCase());
$test->addTestCase($plugins);

// Db component
$db = new GroupTest('Db tests: Db and Profiler','db');
$db->addTestCase(new Doctrine_Db_TestCase());
$db->addTestCase(new Doctrine_Connection_Profiler_TestCase());
$test->addTestCase($db);

// Eventlisteners
$event_listener = new GroupTest('EventListener tests','event_listener');
$event_listener->addTestCase(new Doctrine_EventListener_TestCase());
$event_listener->addTestCase(new Doctrine_EventListener_Chain_TestCase());
$test->addTestCase($event_listener);

// Query tests
$query_tests = new GroupTest('Query tests','query_test');
$query_tests->addTestCase(new Doctrine_Query_Condition_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MultiJoin_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MultiJoin2_TestCase());
$query_tests->addTestCase(new Doctrine_Query_ReferenceModel_TestCase());
$query_tests->addTestCase(new Doctrine_Query_ComponentAlias_TestCase());
$query_tests->addTestCase(new Doctrine_Query_ShortAliases_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Expression_TestCase());
$query_tests->addTestCase(new Doctrine_Query_OneToOneFetching_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Check_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Limit_TestCase());
//$query_tests->addTestCase(new Doctrine_Query_IdentifierQuoting_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Update_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Delete_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Join_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Having_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Orderby_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Subquery_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Driver_TestCase());
$query_tests->addTestCase(new Doctrine_Record_Hook_TestCase());
$query_tests->addTestCase(new Doctrine_Query_AggregateValue_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Where_TestCase());
$query_tests->addTestCase(new Doctrine_Query_From_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Select_TestCase());
$query_tests->addTestCase(new Doctrine_Query_JoinCondition_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MultipleAggregateValue_TestCase());
$query_tests->addTestCase(new Doctrine_Query_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MysqlSubquery_TestCase());
$query_tests->addTestCase(new Doctrine_Query_PgsqlSubquery_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MysqlSubqueryHaving_TestCase());
$query_tests->addTestCase(new Doctrine_Query_SelectExpression_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Registry_TestCase());
$test->addTestCase($query_tests);

// Record
$record = new GroupTest('Record tests','record');
$record->addTestCase(new Doctrine_Record_Filter_TestCase());
$record->addTestCase(new Doctrine_Record_TestCase());
$record->addTestCase(new Doctrine_Record_State_TestCase());
$record->addTestCase(new Doctrine_Record_SerializeUnserialize_TestCase());
// This test used to segfault php because of infinite recursion in Connection/UnitOfWork
$record->addTestCase(new Doctrine_Record_Lock_TestCase());
$record->addTestCase(new Doctrine_Record_ZeroValues_TestCase());
//$record->addTestCase(new Doctrine_Record_SaveBlankRecord_TestCase());
$record->addTestCase(new Doctrine_Record_Inheritance_TestCase());
$record->addTestCase(new Doctrine_Record_Synchronize_TestCase());
$test->addTestCase($record);

$test->addTestCase(new Doctrine_CustomPrimaryKey_TestCase());
$test->addTestCase(new Doctrine_CustomResultSetOrder_TestCase());

$test->addTestCase(new Doctrine_CtiColumnAggregation_TestCase());
$test->addTestCase(new Doctrine_ColumnAggregationInheritance_TestCase());
$test->addTestCase(new Doctrine_ClassTableInheritance_TestCase());
$test->addTestCase(new Doctrine_ColumnAlias_TestCase());


$test->addTestCase(new Doctrine_RawSql_TestCase());

$test->addTestCase(new Doctrine_NewCore_TestCase());

$test->addTestCase(new Doctrine_Template_TestCase());

//$test->addTestCase(new Doctrine_Import_Builder_TestCase());
$test->addTestCase(new Doctrine_NestedSet_SingleRoot_TestCase());

// Search tests
$search = new GroupTest('Search tests','search');
$search->addTestCase(new Doctrine_Search_TestCase());
$search->addTestCase(new Doctrine_Search_Query_TestCase());
$search->addTestCase(new Doctrine_Search_File_TestCase());

$test->addTestCase($search);

// Cache tests
$cache = new GroupTest('Cache tests','cache');
$cache->addTestCase(new Doctrine_Query_Cache_TestCase());
$cache->addTestCase(new Doctrine_Cache_Apc_TestCase());
//$cache->addTestCase(new Doctrine_Cache_Memcache_TestCase());
//$cache->addTestCase(new Doctrine_Cache_Sqlite_TestCase());
//$cache->addTestCase(new Doctrine_Cache_Query_SqliteTestCase());
//$cache->addTestCase(new Doctrine_Cache_FileTestCase());
//$cache->addTestCase(new Doctrine_Cache_SqliteTestCase());
//$cache->addTestCase(new Doctrine_Cache_TestCase());
$test->addTestCase($cache);

// Migration Tests
$migration = new GroupTest('Migration tests','migration');
$migration->addTestCase(new Doctrine_Migration_TestCase());
$migration->addTestCase(new Doctrine_Migration_Mysql_TestCase());
$test->addTestCase($migration);

$test->addTestCase(new Doctrine_Query_ApplyInheritance_TestCase());

$parser = new GroupTest('Parser tests', 'parser');
$parser->addTestCase(new Doctrine_Parser_TestCase());
$test->addTestCase($parser);

$schemaFiles = new GroupTest('Schema files', 'schema_files');
$schemaFiles->addTestCase(new Doctrine_Import_Schema_TestCase());
$schemaFiles->addTestCase(new Doctrine_Export_Schema_TestCase());
$test->addTestCase($schemaFiles);

$data = new GroupTest('Data exporting/importing fixtures', 'data_fixtures');
$data->addTestCase(new Doctrine_Data_Import_TestCase());
$data->addTestCase(new Doctrine_Data_Export_TestCase());
$test->addTestCase($data);

$test->run();

echo memory_get_peak_usage() / 1024 . "\n";
