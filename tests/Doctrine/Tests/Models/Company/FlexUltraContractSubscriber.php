<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Event\LifecycleEventArgs;

class FlexUltraContractSubscriber
{
    static public $prePersistCalls;
    static public $postPersisCalls;
    static public $instances;

    public function __construct()
    {
        self::$instances[] = $this;
    }

    /**
     * @PrePersist
     */
    public function postPersistHandler1(CompanyContract $contract, LifecycleEventArgs $args)
    {
        self::$postPersisCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function postPersistHandler2(CompanyContract $contract, LifecycleEventArgs $args)
    {
        self::$postPersisCalls[] = func_get_args();
    }
}
