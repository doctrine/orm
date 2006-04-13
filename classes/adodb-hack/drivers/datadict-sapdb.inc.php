<?php

/**
  V4.50 6 July 2004  (c) 2000-2006 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
	
  Set tabs to 4 for best viewing.
  
  Modified from datadict-generic.inc.php for sapdb by RalfBecker-AT-outdoor-training.de
*/

// security - hide paths
if (!defined('ADODB_DIR')) die();

class ADODB2_sapdb extends ADODB_DataDict {
	
	var $databaseType = 'sapdb';
	var $seqField = false;	
	var $renameColumn = 'RENAME COLUMN %s.%s TO %s';
 	
 	function ActualType($meta)
	{
		switch($meta) {
		case 'C': return 'VARCHAR';
		case 'XL':
		case 'X': return 'LONG';
		
		case 'C2': return 'VARCHAR UNICODE';
		case 'X2': return 'LONG UNICODE';
		
		case 'B': return 'LONG';
			
		case 'D': return 'DATE';
		case 'T': return 'TIMESTAMP';
		
		case 'L': return 'BOOLEAN';
		case 'I': return 'INTEGER';
		case 'I1': return 'FIXED(3)';
		case 'I2': return 'SMALLINT';
		case 'I4': return 'INTEGER';
		case 'I8': return 'FIXED(20)';
		
		case 'F': return 'FLOAT(38)';
		case 'N': return 'FIXED';
		default:
			return $meta;
		}
	}
	
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		static $maxdb_type2adodb = array(
			'VARCHAR'	=> 'C',
			'CHARACTER'	=> 'C',
			'LONG'		=> 'X',		// no way to differ between 'X' and 'B' :-(
			'DATE'		=> 'D',
			'TIMESTAMP'	=> 'T',
			'BOOLEAN'	=> 'L',
			'INTEGER'	=> 'I4',
			'SMALLINT'	=> 'I2',
			'FLOAT'		=> 'F',
			'FIXED'		=> 'N',
		);
		$type = isset($maxdb_type2adodb[$t]) ? $maxdb_type2adodb[$t] : 'C';

		// convert integer-types simulated with fixed back to integer
		if ($t == 'FIXED' && !$fieldobj->scale && ($len == 20 || $len == 3)) {
			$type = $len == 20 ? 'I8' : 'I1';
		}
		if ($fieldobj->auto_increment) $type = 'R';

		return $type;
	}
	
	// return string must begin with space
	function _CreateSuffix($fname,$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned)
	{	
		$suffix = '';
		if ($funsigned) $suffix .= ' UNSIGNED';
		if ($fnotnull) $suffix .= ' NOT NULL';
		if ($fautoinc) $suffix .= ' DEFAULT SERIAL';
		elseif (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}

	function AddColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$sql = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		return array( 'ALTER TABLE ' . $tabname . ' ADD (' . implode(', ',$lines) . ')' );
	}
	
	function AlterColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$sql = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		return array( 'ALTER TABLE ' . $tabname . ' MODIFY (' . implode(', ',$lines) . ')' );
	}

	function DropColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		if (!is_array($flds)) $flds = explode(',',$flds);
		foreach($flds as $k => $v) {
			$flds[$k] = $this->NameQuote($v);
		}
		return array( 'ALTER TABLE ' . $tabname . ' DROP (' . implode(', ',$flds) . ')' );
	}	
}

?>