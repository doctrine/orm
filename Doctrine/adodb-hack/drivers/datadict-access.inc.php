<?php

/**
  V4.80 8 Mar 2006  (c) 2000-2006 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
	
  Set tabs to 4 for best viewing.
 
*/

// security - hide paths
if (!defined('ADODB_DIR')) die();

class ADODB2_access extends ADODB_DataDict {
	
	var $databaseType = 'access';
	var $seqField = false;
	
 	
 	function ActualType($meta)
	{
		switch($meta) {
		case 'C': return 'TEXT';
		case 'XL':
		case 'X': return 'MEMO'; 
		
		case 'C2': return 'TEXT'; // up to 32K
		case 'X2': return 'MEMO';
		
		case 'B': return 'BINARY';
			
		case 'D': return 'DATETIME';
		case 'T': return 'DATETIME';
		
		case 'L': return 'BYTE';
		case 'I': return 'INTEGER';
		case 'I1': return 'BYTE';
		case 'I2': return 'SMALLINT';
		case 'I4': return 'INTEGER';
		case 'I8': return 'INTEGER';
		
		case 'F': return 'DOUBLE';
		case 'N': return 'NUMERIC';
		default:
			return $meta;
		}
	}
	
	// return string must begin with space
	function _CreateSuffix($fname, &$ftype, $fnotnull,$fdefault,$fautoinc,$fconstraint)
	{
		if ($fautoinc) {
			$ftype = 'COUNTER';
			return '';
		}
		if (substr($ftype,0,7) == 'DECIMAL') $ftype = 'DECIMAL';
		$suffix = '';
		if (strlen($fdefault)) {
			//$suffix .= " DEFAULT $fdefault";
			if ($this->debug) ADOConnection::outp("Warning: Access does not supported DEFAULT values (field $fname)");
		}
		if ($fnotnull) $suffix .= ' NOT NULL';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}
	
	function CreateDatabase($dbname,$options=false)
	{
		return array();
	}
	
	
	function SetSchema($schema)
	{
	}

	function AlterColumnSQL($tabname, $flds)
	{
		if ($this->debug) ADOConnection::outp("AlterColumnSQL not supported");
		return array();
	}
	
	
	function DropColumnSQL($tabname, $flds)
	{
		if ($this->debug) ADOConnection::outp("DropColumnSQL not supported");
		return array();
	}
	
}


?>