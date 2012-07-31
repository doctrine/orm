<?php

namespace Doctrine\Tests\Models\Company;

class ContractSubscriber
{
    static public $postPersistCalls;
    static public $prePersistCalls;
    
    static public $postUpdateCalls;
    static public $preUpdateCalls;
    
    static public $postRemoveCalls;
    static public $preRemoveCalls;

    static public $preFlushCalls;
    
    static public $postLoadCalls;
    
    static public $instances;

    public function __construct()
    {
        self::$instances[] = $this;
    }

    /**
     * @PostPersist
     */
    public function postPersistHandler(CompanyContract $contract)
    {
        self::$postPersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler(CompanyContract $contract)
    {
        self::$prePersistCalls[] = func_get_args();
    }

    /**
     * @PostUpdate
     */
    public function postUpdateHandler(CompanyContract $contract)
    {
        self::$postUpdateCalls[] = func_get_args();
    }

    /**
     * @PreUpdate
     */
    public function preUpdateHandler(CompanyContract $contract)
    {
        self::$preUpdateCalls[] = func_get_args();
    }

    /**
     * @PostRemove
     */
    public function postRemoveHandler(CompanyContract $contract)
    {
        self::$postRemoveCalls[] = func_get_args();
    }

    /**
     * @PreRemove
     */
    public function preRemoveHandler(CompanyContract $contract)
    {
        self::$preRemoveCalls[] = func_get_args();
    }

    /**
     * @PreFlush
     */
    public function preFlushHandler(CompanyContract $contract)
    {
        self::$preFlushCalls[] = func_get_args();
    }

    /**
     * @PostLoad
     */
    public function postLoadHandler(CompanyContract $contract)
    {
        self::$postLoadCalls[] = func_get_args();
    }

}
