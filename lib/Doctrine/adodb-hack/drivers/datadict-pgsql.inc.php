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

class ADODB2_pgsql extends ADODB_DataDict {
	
	var $databaseType = 'postgres';
	var $seqField = false;
	var $seqPrefix = 'SEQ_';
	var $addCol = ' ADD COLUMN';
	var $quote = '"';
	var $renameTable = 'ALTER TABLE %s RENAME TO %s'; // at least since 7.1
	var $dropTable = 'DROP TABLE %s CASCADE';
	
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		$is_serial = is_object($fieldobj) && $fieldobj->primary_key && $fieldobj->unique && 
			$fieldobj->has_default && substr($fieldobj->default_value,0,8) == 'nextval(';
		
		switch (strtoupper($t)) {
			case 'INTERVAL':
			case 'CHAR':
			case 'CHARACTER':
			case 'VARCHAR':
			case 'NAME':
	   		case 'BPCHAR':
				if ($len <= $this->blobSize) return 'C';
			
			case 'TEXT':
				return 'X';
	
			case 'IMAGE': // user defined type
			case 'BLOB': // user defined type
			case 'BIT':	// This is a bit string, not a single bit, so don't return 'L'
			case 'VARBIT':
			case 'BYTEA':
				return 'B';
			
			case 'BOOL':
			case 'BOOLEAN':
				return 'L';
			
			case 'DATE':
				return 'D';
			
			case 'TIME':
			case 'DATETIME':
			case 'TIMESTAMP':
			case 'TIMESTAMPTZ':
				return 'T';
			
			case 'INTEGER': return !$is_serial ? 'I' : 'R';
			case 'SMALLINT': 
			case 'INT2': return !$is_serial ? 'I2' : 'R';
			case 'INT4': return !$is_serial ? 'I4' : 'R';
			case 'BIGINT': 
			case 'INT8': return !$is_serial ? 'I8' : 'R';
				
			case 'OID':
			case 'SERIAL':
				return 'R';
			
			case 'FLOAT4':
			case 'FLOAT8':
			case 'DOUBLE PRECISION':
			case 'REAL':
				return 'F';
				
			 default:
			 	return 'N';
		}
	}
 	
 	function ActualType($meta)
	{
		switch($meta) {
		case 'C': return 'VARCHAR';
		case 'XL':
		case 'X': return 'TEXT';
		
		case 'C2': return 'VARCHAR';
		case 'X2': return 'TEXT';
		
		case 'B': return 'BYTEA';
			
		case 'D': return 'DATE';
		case 'T': return 'TIMESTAMP';
		
		case 'L': return 'BOOLEAN';
		case 'I': return 'INTEGER';
		case 'I1': return 'SMALLINT';
		case 'I2': return 'INT2';
		case 'I4': return 'INT4';
		case 'I8': return 'INT8';
		
		case 'F': return 'FLOAT8';
		case 'N': return 'NUMERIC';
		default:
			return $meta;
		}
	}
	
	/**
	 * Adding a new Column 
	 *
	 * reimplementation of the default function as postgres does NOT allow to set the default in the same statement
	 *
	 * @param string $tabname table-name
	 * @param string $flds column-names and types for the changed columns
	 * @return array with SQL strings
	 */
	function AddColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$sql = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		$alter = 'ALTER TABLE ' . $tabname . $this->addCol . ' ';
		foreach($lines as $v) {
			if (($not_null = preg_match('/NOT NULL/i',$v))) {
				$v = preg_replace('/NOT NULL/i','',$v);
			}
			if (preg_match('/^([^ ]+) .*DEFAULT ([^ ]+)/',$v,$matches)) {
				list(,$colname,$default) = $matches;
				$sql[] = $alter . str_replace('DEFAULT '.$default,'',$v);
				$sql[] = 'UPDATE '.$tabname.' SET '.$colname.'='.$default;
				$sql[] = 'ALTER TABLE '.$tabname.' ALTER COLUMN '.$colname.' SET DEFAULT ' . $default;
			} else {				
				$sql[] = $alter . $v;
			}
			if ($not_null) {
				list($colname) = explode(' ',$v);
				$sql[] = 'ALTER TABLE '.$tabname.' ALTER COLUMN '.$colname.' SET NOT NULL';
			}
		}
		return $sql;
	}
	
	/**
	 * Change the definition of one column
	 *
	 * Postgres can't do that on it's own, you need to supply the complete defintion of the new table,
	 * to allow, recreating the table and copying the content over to the new table
	 * @param string $tabname table-name
	 * @param string $flds column-name and type for the changed column
	 * @param string $tableflds complete defintion of the new table, eg. for postgres, default ''
	 * @param array/ $tableoptions options for the new table see CreateTableSQL, default ''
	 * @return array with SQL strings
	 */
	function AlterColumnSQL($tabname, $flds, $tableflds='',$tableoptions='')
	{
		if (!$tableflds) {
			if ($this->debug) ADOConnection::outp("AlterColumnSQL needs a complete table-definiton for PostgreSQL");
			return array();
		}
		return $this->_recreate_copy_table($tabname,False,$tableflds,$tableoptions);
	}
	
	/**
	 * Drop one column
	 *
	 * Postgres < 7.3 can't do that on it's own, you need to supply the complete defintion of the new table,
	 * to allow, recreating the table and copying the content over to the new table
	 * @param string $tabname table-name
	 * @param string $flds column-name and type for the changed column
	 * @param string $tableflds complete defintion of the new table, eg. for postgres, default ''
	 * @param array/ $tableoptions options for the new table see CreateTableSQL, default ''
	 * @return array with SQL strings
	 */
	function DropColumnSQL($tabname, $flds, $tableflds='',$tableoptions='')
	{
		$has_drop_column = 7.3 <= (float) @$this->serverInfo['version'];
		if (!$has_drop_column && !$tableflds) {
			if ($this->debug) ADOConnection::outp("DropColumnSQL needs complete table-definiton for PostgreSQL < 7.3");
		return array();
	}
		if ($has_drop_column) {
			return ADODB_DataDict::DropColumnSQL($tabname, $flds);
		}
		return $this->_recreate_copy_table($tabname,$flds,$tableflds,$tableoptions);
	}
	
	/**
	 * Save the content into a temp. table, drop and recreate the original table and copy the content back in
	 *
	 * We also take care to set the values of the sequenz and recreate the indexes.
	 * All this is done in a transaction, to not loose the content of the table, if something went wrong!
	 * @internal
	 * @param string $tabname table-name
	 * @param string $dropflds column-names to drop
	 * @param string $tableflds complete defintion of the new table, eg. for postgres
	 * @param array/string $tableoptions options for the new table see CreateTableSQL, default ''
	 * @return array with SQL strings
	 */
	function _recreate_copy_table($tabname,$dropflds,$tableflds,$tableoptions='')
	{
		if ($dropflds && !is_array($dropflds)) $dropflds = explode(',',$dropflds);
		$copyflds = array();
		foreach($this->MetaColumns($tabname) as $fld) {
			if (!$dropflds || !in_array($fld->name,$dropflds)) {
				// we need to explicit convert varchar to a number to be able to do an AlterColumn of a char column to a nummeric one
				if (preg_match('/'.$fld->name.' (I|I2|I4|I8|N|F)/i',$tableflds,$matches) && 
					in_array($fld->type,array('varchar','char','text','bytea'))) {
					$copyflds[] = "to_number($fld->name,'S9999999999999D99')";
				} else {
					$copyflds[] = $fld->name;
				}
				// identify the sequence name and the fld its on
				if ($fld->primary_key && $fld->has_default && 
					preg_match("/nextval\('([^']+)'::text\)/",$fld->default_value,$matches)) {
					$seq_name = $matches[1];
					$seq_fld = $fld->name;
				}
			}
		}
		$copyflds = implode(', ',$copyflds);
		
		$tempname = $tabname.'_tmp';
		$aSql[] = 'BEGIN';		// we use a transaction, to make sure not to loose the content of the table
		$aSql[] = "SELECT * INTO TEMPORARY TABLE $tempname FROM $tabname";
		$aSql = array_merge($aSql,$this->DropTableSQL($tabname));
		$aSql = array_merge($aSql,$this->CreateTableSQL($tabname,$tableflds,$tableoptions));
		$aSql[] = "INSERT INTO $tabname SELECT $copyflds FROM $tempname";
		if ($seq_name && $seq_fld) {	// if we have a sequence we need to set it again
			$seq_name = $tabname.'_'.$seq_fld.'_seq';	// has to be the name of the new implicit sequence
			$aSql[] = "SELECT setval('$seq_name',MAX($seq_fld)) FROM $tabname";
		}
		$aSql[] = "DROP TABLE $tempname";
		// recreate the indexes, if they not contain one of the droped columns
		foreach($this->MetaIndexes($tabname) as $idx_name => $idx_data)
		{
			if (substr($idx_name,-5) != '_pkey' && (!$dropflds || !count(array_intersect($dropflds,$idx_data['columns'])))) {
				$aSql = array_merge($aSql,$this->CreateIndexSQL($idx_name,$tabname,$idx_data['columns'],
					$idx_data['unique'] ? array('UNIQUE') : False));
			}
		}
		$aSql[] = 'COMMIT';
		return $aSql;
	}
	
	function DropTableSQL($tabname)
	{
		$sql = ADODB_DataDict::DropTableSQL($tabname);
		
		$drop_seq = $this->_DropAutoIncrement($tabname);
		if ($drop_seq) $sql[] = $drop_seq;
		
		return $sql;
	}

	// return string must begin with space
	function _CreateSuffix($fname, &$ftype, $fnotnull,$fdefault,$fautoinc,$fconstraint)
	{
		if ($fautoinc) {
			$ftype = 'SERIAL';
			return '';
		}
		$suffix = '';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fnotnull) $suffix .= ' NOT NULL';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}
	
	// search for a sequece for the given table (asumes the seqence-name contains the table-name!)
	// if yes return sql to drop it
	// this is still necessary if postgres < 7.3 or the SERIAL was created on an earlier version!!!
	function _DropAutoIncrement($tabname)
	{
		$tabname = $this->connection->quote('%'.$tabname.'%');

		$seq = $this->connection->GetOne("SELECT relname FROM pg_class WHERE NOT relname ~ 'pg_.*' AND relname LIKE $tabname AND relkind='S'");

		// check if a tables depends on the sequenz and it therefor cant and dont need to be droped separatly
		if (!$seq || $this->connection->GetOne("SELECT relname FROM pg_class JOIN pg_depend ON pg_class.relfilenode=pg_depend.objid WHERE relname='$seq' AND relkind='S' AND deptype='i'")) {
			return False;
		}
		return "DROP SEQUENCE ".$seq;
	}
	
	/*
	CREATE [ [ LOCAL ] { TEMPORARY | TEMP } ] TABLE table_name (
	{ column_name data_type [ DEFAULT default_expr ] [ column_constraint [, ... ] ]
	| table_constraint } [, ... ]
	)
	[ INHERITS ( parent_table [, ... ] ) ]
	[ WITH OIDS | WITHOUT OIDS ]
	where column_constraint is:
	[ CONSTRAINT constraint_name ]
	{ NOT NULL | NULL | UNIQUE | PRIMARY KEY |
	CHECK (expression) |
	REFERENCES reftable [ ( refcolumn ) ] [ MATCH FULL | MATCH PARTIAL ]
	[ ON DELETE action ] [ ON UPDATE action ] }
	[ DEFERRABLE | NOT DEFERRABLE ] [ INITIALLY DEFERRED | INITIALLY IMMEDIATE ]
	and table_constraint is:
	[ CONSTRAINT constraint_name ]
	{ UNIQUE ( column_name [, ... ] ) |
	PRIMARY KEY ( column_name [, ... ] ) |
	CHECK ( expression ) |
	FOREIGN KEY ( column_name [, ... ] ) REFERENCES reftable [ ( refcolumn [, ... ] ) ]
	[ MATCH FULL | MATCH PARTIAL ] [ ON DELETE action ] [ ON UPDATE action ] }
	[ DEFERRABLE | NOT DEFERRABLE ] [ INITIALLY DEFERRED | INITIALLY IMMEDIATE ]
	*/
	
	
	/*
	CREATE [ UNIQUE ] INDEX index_name ON table
[ USING acc_method ] ( column [ ops_name ] [, ...] )
[ WHERE predicate ]
CREATE [ UNIQUE ] INDEX index_name ON table
[ USING acc_method ] ( func_name( column [, ... ]) [ ops_name ] )
[ WHERE predicate ]
	*/
	function _IndexSQL($idxname, $tabname, $flds, $idxoptions)
	{
		$sql = array();
		
		if ( isset($idxoptions['REPLACE']) || isset($idxoptions['DROP']) ) {
			$sql[] = sprintf ($this->dropIndex, $idxname, $tabname);
			if ( isset($idxoptions['DROP']) )
				return $sql;
		}
		
		if ( empty ($flds) ) {
			return $sql;
		}
		
		$unique = isset($idxoptions['UNIQUE']) ? ' UNIQUE' : '';
		
		$s = 'CREATE' . $unique . ' INDEX ' . $idxname . ' ON ' . $tabname . ' ';
		
		if (isset($idxoptions['HASH']))
			$s .= 'USING HASH ';
		
		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];
		
		if ( is_array($flds) )
			$flds = implode(', ',$flds);
		$s .= '(' . $flds . ')';
		$sql[] = $s;
		
		return $sql;
	}
	
	function _GetSize($ftype, $ty, $fsize, $fprec)
	{
		if (strlen($fsize) && $ty != 'X' && $ty != 'B' && $ty  != 'I' && strpos($ftype,'(') === false) {
			$ftype .= "(".$fsize;
			if (strlen($fprec)) $ftype .= ",".$fprec;
			$ftype .= ')';
		}
		return $ftype;
	}
}

