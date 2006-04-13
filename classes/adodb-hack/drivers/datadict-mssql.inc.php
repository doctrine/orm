<?php

/**
  V4.80 8 Mar 2006  (c) 2000-2006 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
	
  Set tabs to 4 for best viewing.
 
*/

/*
In ADOdb, named quotes for MS SQL Server use ". From the MSSQL Docs:

	Note Delimiters are for identifiers only. Delimiters cannot be used for keywords, 
	whether or not they are marked as reserved in SQL Server.
	
	Quoted identifiers are delimited by double quotation marks ("):
	SELECT * FROM "Blanks in Table Name"
	
	Bracketed identifiers are delimited by brackets ([ ]):
	SELECT * FROM [Blanks In Table Name]
	
	Quoted identifiers are valid only when the QUOTED_IDENTIFIER option is set to ON. By default, 
	the Microsoft OLE DB Provider for SQL Server and SQL Server ODBC driver set QUOTED_IDENTIFIER ON 
	when they connect. 
	
	In Transact-SQL, the option can be set at various levels using SET QUOTED_IDENTIFIER, 
	the quoted identifier option of sp_dboption, or the user options option of sp_configure.
	
	When SET ANSI_DEFAULTS is ON, SET QUOTED_IDENTIFIER is enabled.
	
	Syntax
	
		SET QUOTED_IDENTIFIER { ON | OFF }


*/

// security - hide paths
if (!defined('ADODB_DIR')) die();

class ADODB2_mssql extends ADODB_DataDict {
	var $databaseType = 'mssql';
	var $dropIndex = 'DROP INDEX %2$s.%1$s';
	var $renameTable = "EXEC sp_rename '%s','%s'";
	var $renameColumn = "EXEC sp_rename '%s.%s','%s'";

	var $typeX = 'TEXT';  ## Alternatively, set it to VARCHAR(4000)
	var $typeXL = 'TEXT';
	
	//var $alterCol = ' ALTER COLUMN ';
	
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		
		$len = -1; // mysql max_length is not accurate
		switch (strtoupper($t)) {
		case 'R':
		case 'INT': 
		case 'INTEGER': return  'I';
		case 'BIT':
		case 'TINYINT': return  'I1';
		case 'SMALLINT': return 'I2';
		case 'BIGINT':  return  'I8';
		
		case 'REAL':
		case 'FLOAT': return 'F';
		default: return parent::MetaType($t,$len,$fieldobj);
		}
	}
	
	function ActualType($meta)
	{
		switch(strtoupper($meta)) {

		case 'C': return 'VARCHAR';
		case 'XL': return (isset($this)) ? $this->typeXL : 'TEXT';
		case 'X': return (isset($this)) ? $this->typeX : 'TEXT'; ## could be varchar(8000), but we want compat with oracle
		case 'C2': return 'NVARCHAR';
		case 'X2': return 'NTEXT';
		
		case 'B': return 'IMAGE';
			
		case 'D': return 'DATETIME';
		case 'T': return 'DATETIME';
		case 'L': return 'BIT';
		
		case 'R':		
		case 'I': return 'INT'; 
		case 'I1': return 'TINYINT';
		case 'I2': return 'SMALLINT';
		case 'I4': return 'INT';
		case 'I8': return 'BIGINT';
		
		case 'F': return 'REAL';
		case 'N': return 'NUMERIC';
		default:
			return $meta;
		}
	}
	
	
	function AddColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$f = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		$s = "ALTER TABLE $tabname $this->addCol";
		foreach($lines as $v) {
			$f[] = "\n $v";
		}
		$s .= implode(', ',$f);
		$sql[] = $s;
		return $sql;
	}
	
	/*
	function AlterColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$sql = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		foreach($lines as $v) {
			$sql[] = "ALTER TABLE $tabname $this->alterCol $v";
		}

		return $sql;
	}
	*/
	
	function DropColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		if (!is_array($flds))
			$flds = explode(',',$flds);
		$f = array();
		$s = 'ALTER TABLE ' . $tabname;
		foreach($flds as $v) {
			$f[] = "\n$this->dropCol ".$this->NameQuote($v);
		}
		$s .= implode(', ',$f);
		$sql[] = $s;
		return $sql;
	}
	
	// return string must begin with space
	function _CreateSuffix($fname,$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint)
	{	
		$suffix = '';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fautoinc) $suffix .= ' IDENTITY(1,1)';
		if ($fnotnull) $suffix .= ' NOT NULL';
		else if ($suffix == '') $suffix .= ' NULL';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}
	
	/*
CREATE TABLE 
    [ database_name.[ owner ] . | owner. ] table_name 
    ( { < column_definition > 
        | column_name AS computed_column_expression 
        | < table_constraint > ::= [ CONSTRAINT constraint_name ] }

            | [ { PRIMARY KEY | UNIQUE } [ ,...n ] 
    ) 

[ ON { filegroup | DEFAULT } ] 
[ TEXTIMAGE_ON { filegroup | DEFAULT } ] 

< column_definition > ::= { column_name data_type } 
    [ COLLATE < collation_name > ] 
    [ [ DEFAULT constant_expression ] 
        | [ IDENTITY [ ( seed , increment ) [ NOT FOR REPLICATION ] ] ]
    ] 
    [ ROWGUIDCOL] 
    [ < column_constraint > ] [ ...n ] 

< column_constraint > ::= [ CONSTRAINT constraint_name ] 
    { [ NULL | NOT NULL ] 
        | [ { PRIMARY KEY | UNIQUE } 
            [ CLUSTERED | NONCLUSTERED ] 
            [ WITH FILLFACTOR = fillfactor ] 
            [ON {filegroup | DEFAULT} ] ] 
        ] 
        | [ [ FOREIGN KEY ] 
            REFERENCES ref_table [ ( ref_column ) ] 
            [ ON DELETE { CASCADE | NO ACTION } ] 
            [ ON UPDATE { CASCADE | NO ACTION } ] 
            [ NOT FOR REPLICATION ] 
        ] 
        | CHECK [ NOT FOR REPLICATION ] 
        ( logical_expression ) 
    } 

< table_constraint > ::= [ CONSTRAINT constraint_name ] 
    { [ { PRIMARY KEY | UNIQUE } 
        [ CLUSTERED | NONCLUSTERED ] 
        { ( column [ ASC | DESC ] [ ,...n ] ) } 
        [ WITH FILLFACTOR = fillfactor ] 
        [ ON { filegroup | DEFAULT } ] 
    ] 
    | FOREIGN KEY 
        [ ( column [ ,...n ] ) ] 
        REFERENCES ref_table [ ( ref_column [ ,...n ] ) ] 
        [ ON DELETE { CASCADE | NO ACTION } ] 
        [ ON UPDATE { CASCADE | NO ACTION } ] 
        [ NOT FOR REPLICATION ] 
    | CHECK [ NOT FOR REPLICATION ] 
        ( search_conditions ) 
    } 


	*/
	
	/*
	CREATE [ UNIQUE ] [ CLUSTERED | NONCLUSTERED ] INDEX index_name 
    ON { table | view } ( column [ ASC | DESC ] [ ,...n ] ) 
		[ WITH < index_option > [ ,...n] ] 
		[ ON filegroup ]
		< index_option > :: = 
		    { PAD_INDEX | 
		        FILLFACTOR = fillfactor | 
		        IGNORE_DUP_KEY | 
		        DROP_EXISTING | 
		    STATISTICS_NORECOMPUTE | 
		    SORT_IN_TEMPDB  
		}
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
		$clustered = isset($idxoptions['CLUSTERED']) ? ' CLUSTERED' : '';
		
		if ( is_array($flds) )
			$flds = implode(', ',$flds);
		$s = 'CREATE' . $unique . $clustered . ' INDEX ' . $idxname . ' ON ' . $tabname . ' (' . $flds . ')';
		
		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];
		

		$sql[] = $s;
		
		return $sql;
	}
	
	
	function _GetSize($ftype, $ty, $fsize, $fprec)
	{
		switch ($ftype) {
		case 'INT':
		case 'SMALLINT':
		case 'TINYINT':
		case 'BIGINT':
			return $ftype;
		}
    	if ($ty == 'T') return $ftype;
    	return parent::_GetSize($ftype, $ty, $fsize, $fprec);    

	}
}
?>