<?php
class Doctrine_Record_Iterator extends ArrayIterator
{
    /**
     * @var Doctrine_Record $record
     */
    private $record;
    /**
     * @var Doctrine_Null $null
     */
    private static $null;
    /**
     * constructor
     *
     * @param Doctrine_Record $record
     */
    public function __construct(Doctrine_Record $record)
    {
        $this->record = $record;
        parent::__construct($record->getData());
    }
    /**
     * initNullObject
     *
     * @param Doctrine_Null $null
     */
    public static function initNullObject(Doctrine_Null $null)
    {
        self::$null = $null;
    }
    /**
     * current
     *
     * @return mixed
     */
    public function current()
    {
        $value = parent::current();

        if ($value === self::$null) {
            return null;
        } else {
            return $value;
        }
    }
}
