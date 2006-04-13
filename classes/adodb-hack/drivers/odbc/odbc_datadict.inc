<?php
/**
  V4.65 22 July 2005  (c) 2000-2005 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
	
  Set tabs to 4 for best viewing.
  
  Modified 28 August, 2005 for use with ADOdb Lite by Mark Dickenson
  
*/

// security - hide paths
if (!defined('ADODB_DIR')) die();

class ADODB2_odbc extends ADODB_DataDict {

	var $dbtype = 'odbc';
	var $seqField = false;
	
 	
 	function ActualType($meta)
	{
		switch($meta) {
		case 'C': return 'VARCHAR';
		case 'XL':
		case 'X': return 'VARCHAR(250)';
		
		case 'C2': return 'VARCHAR';
		case 'X2': return 'VARCHAR(250)';
		
		case 'B': return 'VARCHAR';
			
		case 'D': return 'DATE';
		case 'T': return 'DATE';
		
		case 'L': return 'DECIMAL(1)';
		case 'I': return 'DECIMAL(10)';
		case 'I1': return 'DECIMAL(3)';
		case 'I2': return 'DECIMAL(5)';
		case 'I4': return 'DECIMAL(10)';
		case 'I8': return 'DECIMAL(20)';
		
		case 'F': return 'DECIMAL(32,8)';
		case 'N': return 'DECIMAL';
		default:
			return $meta;
		}
	}

	function AlterColumnSQL($tabname, $flds)
	{
		if ($this->debug) $this->outp("AlterColumnSQL not supported");
		return array();
	}
	
	
	function DropColumnSQL($tabname, $flds)
	{
		if ($this->debug) $this->outp("DropColumnSQL not supported");
		return array();
	}

//	function MetaType($t,$len=-1,$fieldobj=false)
//	{
//	}

//	function &MetaTables($ttype=false,$showSchema=false,$mask=false) 
//	{
//		global $ADODB_FETCH_MODE;
//	}

//	function &MetaColumns($table,$upper=true) 
//	{
//		global $ADODB_FETCH_MODE;
//	}

//	function MetaPrimaryKeys($table, $owner=false)
//	{
//	}

//     function &MetaIndexes($table, $primary = false, $owner = false)
//     {
//     }

}

?>