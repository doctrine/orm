<?php

namespace Doctrine\Tests\Models\Company;

class ContractSubscriber
{
    static public $prePersistCalls;
    static public $postPersisCalls;
    static public $instances;

    public function __construct()
    {
        self::$instances[] = $this;
    }

    /**
     * @PostPersist
     */
    public function postPersist(CompanyContract $contract)
    {
        self::$postPersisCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersist(CompanyContract $contract)
    {
        self::$prePersistCalls[] = func_get_args();
    }
}
