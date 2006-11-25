<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */
/**
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Paul Cooper <pgc@ucecom.com>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_DataDict_Pgsql extends Doctrine_DataDict {
    /**
     * @param array $reservedKeyWords     an array of reserved keywords by pgsql
     */
    protected static $reservedKeyWords = array(
                                        'abort',
                                        'absolute',
                                        'access',
                                        'action',
                                        'add',
                                        'after',
                                        'aggregate',
                                        'all',
                                        'alter',
                                        'analyse',
                                        'analyze',
                                        'and',
                                        'any',
                                        'as',
                                        'asc',
                                        'assertion',
                                        'assignment',
                                        'at',
                                        'authorization',
                                        'backward',
                                        'before',
                                        'begin',
                                        'between',
                                        'bigint',
                                        'binary',
                                        'bit',
                                        'boolean',
                                        'both',
                                        'by',
                                        'cache',
                                        'called',
                                        'cascade',
                                        'case',
                                        'cast',
                                        'chain',
                                        'char',
                                        'character',
                                        'characteristics',
                                        'check',
                                        'checkpoint',
                                        'class',
                                        'close',
                                        'cluster',
                                        'coalesce',
                                        'collate',
                                        'column',
                                        'comment',
                                        'commit',
                                        'committed',
                                        'constraint',
                                        'constraints',
                                        'conversion',
                                        'convert',
                                        'copy',
                                        'create',
                                        'createdb',
                                        'createuser',
                                        'cross',
                                        'current_date',
                                        'current_time',
                                        'current_timestamp',
                                        'current_user',
                                        'cursor',
                                        'cycle',
                                        'database',
                                        'day',
                                        'deallocate',
                                        'dec',
                                        'decimal',
                                        'declare',
                                        'default',
                                        'deferrable',
                                        'deferred',
                                        'definer',
                                        'delete',
                                        'delimiter',
                                        'delimiters',
                                        'desc',
                                        'distinct',
                                        'do',
                                        'domain',
                                        'double',
                                        'drop',
                                        'each',
                                        'else',
                                        'encoding',
                                        'encrypted',
                                        'end',
                                        'escape',
                                        'except',
                                        'exclusive',
                                        'execute',
                                        'exists',
                                        'explain',
                                        'external',
                                        'extract',
                                        'false',
                                        'fetch',
                                        'float',
                                        'for',
                                        'force',
                                        'foreign',
                                        'forward',
                                        'freeze',
                                        'from',
                                        'full',
                                        'function',
                                        'get',
                                        'global',
                                        'grant',
                                        'group',
                                        'handler',
                                        'having',
                                        'hour',
                                        'ilike',
                                        'immediate',
                                        'immutable',
                                        'implicit',
                                        'in',
                                        'increment',
                                        'index',
                                        'inherits',
                                        'initially',
                                        'inner',
                                        'inout',
                                        'input',
                                        'insensitive',
                                        'insert',
                                        'instead',
                                        'int',
                                        'integer',
                                        'intersect',
                                        'interval',
                                        'into',
                                        'invoker',
                                        'is',
                                        'isnull',
                                        'isolation',
                                        'join',
                                        'key',
                                        'lancompiler',
                                        'language',
                                        'leading',
                                        'left',
                                        'level',
                                        'like',
                                        'limit',
                                        'listen',
                                        'load',
                                        'local',
                                        'localtime',
                                        'localtimestamp',
                                        'location',
                                        'lock',
                                        'match',
                                        'maxvalue',
                                        'minute',
                                        'minvalue',
                                        'mode',
                                        'month',
                                        'move',
                                        'names',
                                        'national',
                                        'natural',
                                        'nchar',
                                        'new',
                                        'next',
                                        'no',
                                        'nocreatedb',
                                        'nocreateuser',
                                        'none',
                                        'not',
                                        'nothing',
                                        'notify',
                                        'notnull',
                                        'null',
                                        'nullif',
                                        'numeric',
                                        'of',
                                        'off',
                                        'offset',
                                        'oids',
                                        'old',
                                        'on',
                                        'only',
                                        'operator',
                                        'option',
                                        'or',
                                        'order',
                                        'out',
                                        'outer',
                                        'overlaps',
                                        'overlay',
                                        'owner',
                                        'partial',
                                        'password',
                                        'path',
                                        'pendant',
                                        'placing',
                                        'position',
                                        'precision',
                                        'prepare',
                                        'primary',
                                        'prior',
                                        'privileges',
                                        'procedural',
                                        'procedure',
                                        'read',
                                        'real',
                                        'recheck',
                                        'references',
                                        'reindex',
                                        'relative',
                                        'rename',
                                        'replace',
                                        'reset',
                                        'restrict',
                                        'returns',
                                        'revoke',
                                        'right',
                                        'rollback',
                                        'row',
                                        'rule',
                                        'schema',
                                        'scroll',
                                        'second',
                                        'security',
                                        'select',
                                        'sequence',
                                        'serializable',
                                        'session',
                                        'session_user',
                                        'set',
                                        'setof',
                                        'share',
                                        'show',
                                        'similar',
                                        'simple',
                                        'smallint',
                                        'some',
                                        'stable',
                                        'start',
                                        'statement',
                                        'statistics',
                                        'stdin',
                                        'stdout',
                                        'storage',
                                        'strict',
                                        'substring',
                                        'sysid',
                                        'table',
                                        'temp',
                                        'template',
                                        'temporary',
                                        'then',
                                        'time',
                                        'timestamp',
                                        'to',
                                        'toast',
                                        'trailing',
                                        'transaction',
                                        'treat',
                                        'trigger',
                                        'trim',
                                        'true',
                                        'truncate',
                                        'trusted',
                                        'type',
                                        'unencrypted',
                                        'union',
                                        'unique',
                                        'unknown',
                                        'unlisten',
                                        'until',
                                        'update',
                                        'usage',
                                        'user',
                                        'using',
                                        'vacuum',
                                        'valid',
                                        'validator',
                                        'values',
                                        'varchar',
                                        'varying',
                                        'verbose',
                                        'version',
                                        'view',
                                        'volatile',
                                        'when',
                                        'where',
                                        'with',
                                        'without',
                                        'work',
                                        'write',
                                        'year',
                                        'zone' 
                                        );

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.     
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    public function getNativeDeclaration(array $field) {
        switch ($field['type']) {
            case 'string':
            case 'array':
            case 'object':
            case 'varchar':
            case 'char':
                $length = !empty($field['length'])
                    ? $field['length'] : $db->options['default_text_field_length'];

                $fixed = !empty($field['fixed']) ? $field['fixed'] : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');

            case 'clob':
                return 'TEXT';
            case 'blob':
                return 'BYTEA';
            case 'integer':
                if (!empty($field['autoincrement'])) {
                    if (!empty($field['length'])) {
                        $length = $field['length'];
                        if ($length > 4) {
                            return 'BIGSERIAL PRIMARY KEY';
                        }
                    }
                    return 'SERIAL PRIMARY KEY';
                }
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3 || $length == 4) {
                        return 'INT';
                    } elseif ($length > 4) {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'BOOLEAN';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME without time zone';
            case 'timestamp':
                return 'TIMESTAMP without time zone';
            case 'float':
                return 'FLOAT8';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                return 'NUMERIC(' . $length . ',' . $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES) . ')';
            default:
                throw new Doctrine_DataDict_Pgsql_Exception('Unknown field type '. $field['type']);
        }
    }
    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param array  $field native field description
     *
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getDoctrineDeclaration(array $field) {

        $length = $field['length'];
        if ($length == '-1' && !empty($field['atttypmod'])) {
            $length = $field['atttypmod'] - 4;
        }
        if ((int)$length <= 0) {
            $length = null;
        }
        $type = array();
        $unsigned = $fixed = null;
        switch (strtolower($field['type'])) {
            case 'smallint':
            case 'int2':
                $type[] = 'integer';
                $unsigned = false;
                $length = 2;
                if ($length == '2') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'int':
            case 'int4':
            case 'integer':
            case 'serial':
            case 'serial4':
                $type[] = 'integer';
                $unsigned = false;
                $length = 4;
                break;
            case 'bigint':
            case 'int8':
            case 'bigserial':
            case 'serial8':
                $type[] = 'integer';
                $unsigned = false;
                $length = 8;
                break;
            case 'bool':
            case 'boolean':
                $type[] = 'boolean';
                $length = 1;
                break;
            case 'text':
            case 'varchar':
                $fixed = false;
            case 'unknown':
            case 'char':
            case 'bpchar':
                $type[] = 'text';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($db_type, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
                $type[] = 'date';
                $length = null;
                break;
            case 'datetime':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type[] = 'time';
                $length = null;
                break;
            case 'float':
            case 'double':
            case 'real':
                $type[] = 'float';
                break;
            case 'decimal':
            case 'money':
            case 'numeric':
                $type[] = 'decimal';
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
            case 'bytea':
                $type[] = 'blob';
                $length = null;
                break;
            case 'oid':
                $type[] = 'blob';
                $type[] = 'clob';
                $length = null;
                break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
                break;
            default:
                throw new Doctrine_DataDict_Pgsql_Exception('unknown database attribute type: '.$db_type);
        }

        return array($type, $length, $unsigned, $fixed);
    }
    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       unsigned
     *           Boolean flag that indicates whether the field should be
     *           declared as unsigned integer if possible.
     *
     *       default
     *           Integer value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     */
    public function getIntegerDeclaration($name, $field) {
        /**
        if (!empty($field['unsigned'])) {
            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        */

        if( ! empty($field['autoincrement'])) {
            $name = $this->conn->quoteIdentifier($name, true);
            return $name.' '.$this->getTypeDeclaration($field);
        }

        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->quote($field['default'], 'integer');
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $name = $this->conn->quoteIdentifier($name, true);
        return $name . ' ' . $this->getTypeDeclaration($field) . $default . $notnull;
    }
    /**
     * listDatabases
     * lists all databases
     *
     * @return array
     */
    public function listDatabases() {
        $query = 'SELECT datname FROM pg_database';
        
        return $this->conn->fetchColumn($query);
    }
    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions() {
        $query = "
            SELECT
                proname
            FROM
                pg_proc pr,
                pg_type tp
            WHERE
                tp.oid = pr.prorettype
                AND pr.proisagg = FALSE
                AND tp.typname <> 'trigger'
                AND pr.pronamespace IN
                    (SELECT oid FROM pg_namespace WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";

        return $this->conn->fetchColumn($query);
    }
    /**
     * lists all database triggers
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null) {

    }
    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null) { 
        $query = "SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace IN";
        $query.= "(SELECT oid FROM pg_namespace WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";
        
        return $this->conn->fetchColumn($query);
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table) {
        $table = $db->quote($table, 'text');
        $subquery = "SELECT indexrelid FROM pg_index, pg_class";
        $subquery.= " WHERE pg_class.relname=$table AND pg_class.oid=pg_index.indrelid AND (indisunique = 't' OR indisprimary = 't')";
        $query = "SELECT relname FROM pg_class WHERE oid IN ($subquery)";
        
        return $this->conn->fetchColumn($query);
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table) { 
        $sql = "SELECT a.attnum, a.attname AS field, t.typname AS type, format_type(a.atttypid, a.atttypmod) AS complete_type, "
             . "a.attnotnull AS isnotnull, "
             . "( SELECT 't' "
             . "FROM pg_index "
             . "WHERE c.oid = pg_index.indrelid "
             . "AND pg_index.indkey[0] = a.attnum "
             . "AND pg_index.indisprimary = 't') AS pri, "
             . "(SELECT pg_attrdef.adsrc "
             . "FROM pg_attrdef "
             . "WHERE c.oid = pg_attrdef.adrelid "
             . "AND pg_attrdef.adnum=a.attnum) AS default "
             . "FROM pg_attribute a, pg_class c, pg_type t "
             . "WHERE c.relname = '" . $table . "' "
             . "AND a.attnum > 0 "
             . "AND a.attrelid = c.oid "
             . "AND a.atttypid = t.oid "
             . "ORDER BY a.attnum ";
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $columns     = array();
        foreach ($result as $key => $val) {
            if ($val['type'] === 'varchar') {
                // need to add length to the type so we are compatible with
                // Zend_Db_Adapter_Pdo_Pgsql!
                $length = preg_replace('~.*\(([0-9]*)\).*~', '$1', $val['complete_type']);
                $val['type'] .= '(' . $length . ')';
            }
            $description = array(
                'name'    => $val['field'],
                'type'    => $val['type'],
                'notnull' => ($val['isnotnull'] == ''),
                'default' => $val['default'],
                'primary' => ($val['pri'] == 't'),
            );
            $columns[$val['field']] = new Doctrine_Schema_Column($description);
        }
        return $columns;
    }
    /**
     * list all indexes in a table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table) {
        $table = $db->quote($table, 'text');
        $subquery = "SELECT indexrelid FROM pg_index, pg_class";
        $subquery.= " WHERE pg_class.relname=$table AND pg_class.oid=pg_index.indrelid AND indisunique != 't' AND indisprimary != 't'";
        $query    = "SELECT relname FROM pg_class WHERE oid IN ($subquery)";

        return $this->conn->fetchColumn($query);
    }
    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null) {
        $sql = "SELECT c.relname AS table_name "
             . "FROM pg_class c, pg_user u "
             . "WHERE c.relowner = u.usesysid AND c.relkind = 'r' "
             . "AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) "
             . "AND c.relname !~ '^(pg_|sql_)' "
             . "UNION "
             . "SELECT c.relname AS table_name "
             . "FROM pg_class c "
             . "WHERE c.relkind = 'r' "
             . "AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) "
             . "AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner) "
             . "AND c.relname !~ '^pg_'";
        
        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table) { 
    
    }
    /**
     * list the views in the database that reference a given table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table) { 
        $query = 'SELECT viewname FROM pg_views';

        return $this->conn->fetchColumn($query);
    }
    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers() {
        $query = 'SELECT usename FROM pg_user';

        return $this->conn->fetchColumn($query);
    }
    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null) {
        $query  = 'SELECT viewname FROM pg_views';

        return $this->conn->fetchColumn($query);
    }
}
