<?php

namespace Doctrine\DBAL\Platforms;

/**
 * Enter description here...
 *
 * @since 2.0
 */
class InformixPlatform extends AbstractPlatform
{

    public function __construct()
    {
        parent::__construct();
    }
    
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
     * @override
     */
    public function getNativeDeclaration($field)
    {
        if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
        }
        switch ($field['type']) {
            case 'char':
            case 'varchar':
            case 'array':
            case 'object':
            case 'string':
                if (empty($field['length']) && array_key_exists('default', $field)) {
                    $field['length'] = $this->conn->varchar_max_length;
                }

                $length = ( ! empty($field['length'])) ? $field['length'] : false;
                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR(255)')
                    : ($length ? 'VARCHAR('.$length.')' : 'NVARCHAR');
            case 'clob':
                return 'TEXT';
            case 'blob':
                return 'BLOB';
            case 'integer':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 1) {
                        return 'SMALLINT';
                    } elseif ($length == 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3 || $length == 4) {
                        return 'INTEGER';
                    } elseif ($length > 4) {
                        return 'DECIMAL(20)';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'SMALLINT';
            case 'date':
                return 'DATE';
            case 'time':
                return 'DATETIME YEAR TO SECOND';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
                return 'FLOAT';
            case 'decimal':
                return 'DECIMAL';
        }
        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }
    
}

?>