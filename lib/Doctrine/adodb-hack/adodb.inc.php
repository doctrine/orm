<?php

/**
 * ADOdb Lite is a PHP class to encapsulate multiple database APIs and is compatible with 
 * a subset of the ADODB Command Syntax. 
 * Currently supports Frontbase, MaxDB, miniSQL, MSSQL, MSSQL Pro, MySQLi, MySQLt, MySQL, PostgresSQL,
 * PostgresSQL64, PostgresSQL7, PostgresSQL8, SqLite, SqLite Pro, Sybase and Sybase ASE.
 * 
 */

if (!defined('_ADODB_LAYER'))
	define('_ADODB_LAYER',1);

if (!defined('ADODB_DIR'))
	define('ADODB_DIR', dirname(__FILE__));

$ADODB_vers = 'V1.15 ADOdb Lite 25 March 2006  (c) 2005, 2006 Mark Dickenson. All rights reserved. Released LGPL.';

define('ADODB_FETCH_DEFAULT',0);
define('ADODB_FETCH_NUM',1);
define('ADODB_FETCH_ASSOC',2);
define('ADODB_FETCH_BOTH',3);

GLOBAL $ADODB_FETCH_MODE;
$ADODB_FETCH_MODE = ADODB_FETCH_DEFAULT;	// DEFAULT, NUM, ASSOC or BOTH. Default follows native driver default...


function NewDataDictionary($conn) {

	$dbtype = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);

	include_once ADODB_DIR . '/adodb-datadict.inc.php';
	include_once ADODB_DIR . '/drivers/datadict-' . $dbtype . '.inc.php';

	$class = "ADODB2_$dbtype";
	$dict = new $class();
	$dict->connection = $conn;
	$dict->upperName = strtoupper($dbtype);
	//$dict->quote = $conn->nameQuote;
	//$dict->debug_echo = $conn->debug_echo;

	return $dict;
}
class ADOFieldObject {
	public $name = '';
	public $max_length=0;
	public $type="";
}


