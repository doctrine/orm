<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Event\LifecycleEventArgs;

class FlexUltraContractSubscriber
{
    static public $prePersistCalls;
    static public $instances;

    public function __construct()
    {
        self::$instances[] = $this;
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler1(CompanyContract $contract, LifecycleEventArgs $args)
    {
        self::$prePersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler2(CompanyContract $contract, LifecycleEventArgs $args)
    {
        self::$prePersistCalls[] = func_get_args();
    }
}
