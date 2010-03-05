<?php

namespace Doctrine\DBAL\Schema;

class SchemaException extends \Doctrine\DBAL\DBALException
{
    const TABLE_DOESNT_EXIST = 10;
    const TABLE_ALREADY_EXISTS = 20;
    const COLUMN_DOESNT_EXIST = 30;
    const COLUMN_ALREADY_EXISTS = 40;
    const INDEX_DOESNT_EXIST = 50;
    const INDEX_ALREADY_EXISTS = 60;
    const SEQUENCE_DOENST_EXIST = 70;
    const SEQUENCE_ALREADY_EXISTS = 80;
    const INDEX_INVALID_NAME = 90;
    const FOREIGNKEY_DOESNT_EXIST = 100;

    /**
     * @param string $tableName
     * @return SchemaException
     */
    static public function tableDoesNotExist($tableName)
    {
        return new self("There is no table with name '".$tableName."' in the schema.", self::TABLE_DOESNT_EXIST);
    }

    /**
     * @param string $indexName
     * @return SchemaException
     */
    static public function indexNameInvalid($indexName)
    {
        return new self("Invalid index-name $indexName given, has to be [a-zA-Z0-9_]", self::INDEX_INVALID_NAME);
    }

    /**
     * @param string $indexName
     * @return SchemaException
     */
    static public function indexDoesNotExist($indexName)
    {
        return new self("Index '".$indexName."' does not exist.", self::INDEX_DOESNT_EXIST);
    }

    /**
     * @param string $indexName
     * @return SchemaException
     */
    static public function indexAlreadyExists($indexName)
    {
        return new self("An index with name $indexName was already defined.", self::INDEX_ALREADY_EXISTS);
    }

    /**
     * @param string $columnName
     * @return SchemaException
     */
    static public function columnDoesNotExist($columnName)
    {
        return new self("An unknown column-name $columnName was given.", self::COLUMN_DOESNT_EXIST);
    }

    /**
     *
     * @param  string $tableName
     * @return SchemaException
     */
    static public function tableAlreadyExists($tableName)
    {
        return new self("The table with name '".$tableName."' already exists.", self::TABLE_ALREADY_EXISTS);
    }

    /**
     *
     * @param string $tableName
     * @param string $columnName
     * @return SchemaException
     */
    static public function columnAlreadyExists($tableName, $columnName)
    {
        return new self(
            "The column '".$columnName."' on table '".$tableName."' already exists.", self::COLUMN_ALREADY_EXISTS
        );
    }

    /**
     * @param string $sequenceName
     * @return SchemaException
     */
    static public function sequenceAlreadyExists($sequenceName)
    {
        return new self("The sequence '".$sequenceName."' already exists.", self::SEQUENCE_ALREADY_EXISTS);
    }

    /**
     * @param string $sequenceName
     * @return SchemaException
     */
    static public function sequenceDoesNotExist($sequenceName)
    {
        return new self("There exists no sequence with the name '".$sequenceName."'.", self::SEQUENCE_DOENST_EXIST);
    }

    /**
     * @param  string $fkName
     * @return SchemaException
     */
    static public function foreignKeyDoesNotExist($fkName)
    {
        return new self("There exists no foreign key with the name '".$fkName."'.", self::FOREIGNKEY_DOESNT_EXIST);
    }

    static public function namedForeignKeyRequired($localTable, $foreignKey)
    {
        return new self(
            "The performed schema operation on ".$localTable->getName()." requires a named foreign key, ".
            "but the given foreign key from (".implode(", ", $foreignKey->getColumns()).") onto foreign table ".
            "'".$foreignKey->getForeignTableName()."' (".implode(", ", $foreignKey->getForeignColumns()).") is currently ".
            "unnamed."
        );
    }

    static public function alterTableChangeNotSupported($changeName) {
        return new self ("Alter table change not supported, given '$changeName'");
    }
}