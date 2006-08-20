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

class ADODB2_sqlite extends ADODB_DataDict {

	var $dbtype = 'sqlite';
	var $seqField = false;

	var $metaTablesSQL = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name";

 	function ActualType($meta)
	{
		switch($meta) {
		case 'C': return 'TEXT';
		case 'XL':
		case 'X': return 'TEXT';
		
		case 'C2': return 'TEXT';
		case 'X2': return 'TEXT';

		case 'B': return 'BLOB';
			
		case 'D': return 'DATE';
		case 'T': return 'DATE';
		
		case 'L': return 'REAL';
		case 'I': return 'INTEGER';
		case 'I1': return 'INTEGER';
		case 'I2': return 'INTEGER';
		case 'I4': return 'INTEGER';
		case 'I8': return 'INTEGER';
		
		case 'F': return 'REAL';
		case 'N': return 'DECIMAL';
		default:
			return $meta;
		}
	}
	// return string must begin with space

	function _CreateSuffix($fname,$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned = null)
	{	
		$suffix = '';
		if ($funsigned) $suffix .= ' UNSIGNED';
		if ($fnotnull) $suffix .= ' NOT NULL';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";

		if ($fautoinc) $suffix .= ' PRIMARY KEY AUTOINCREMENT';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}

	function _TableSQL($tabname,$lines,$pkey,$tableoptions)
	{
		$sql = array();

		if (isset($tableoptions['REPLACE']) || isset ($tableoptions['DROP'])) {
			$sql[] = sprintf($this->dropTable,$tabname);
			if ($this->autoIncrement) {
				$sInc = $this->_DropAutoIncrement($tabname);
				if ($sInc) $sql[] = $sInc;
			}
			if ( isset ($tableoptions['DROP']) ) {
				return $sql;
			}
		}
		$s = "CREATE TABLE $tabname (\n";
		$s .= implode(",\n", $lines);

		if (sizeof($pkey)>0 && ! $this->autoIncrement) {
			$s .= ",\n				 PRIMARY KEY (";
			$s .= implode(", ",$pkey).")";
		}

        if (isset($tableoptions['CONSTRAINTS']))
			$s .= "\n".$tableoptions['CONSTRAINTS'];

		if (isset($tableoptions[$this->upperName.'_CONSTRAINTS'])) 
			$s .= "\n".$tableoptions[$this->upperName.'_CONSTRAINTS'];

		$s .= "\n)";
		if (isset($tableoptions[$this->upperName])) $s .= $tableoptions[$this->upperName];
		$sql[] = $s;

		return $sql;
	}

	function AlterColumnSQL($tabname, $flds, $tableflds='',$tableoptions='')
	{
		if ($this->debug) $this->outp("AlterColumnSQL not supported");
		return array();
	}
	
	
	function DropColumnSQL($tabname, $flds, $tableflds='',$tableoptions='')
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
