<?php

namespace Shitty\Tests\Models\Company;

use Shitty\ORM\Event\LifecycleEventArgs;

class CompanyFlexUltraContractListener
{
    public $prePersistCalls;

    /**
     * @PrePersist
     */
    public function prePersistHandler1(CompanyContract $contract, LifecycleEventArgs $args)
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    public function prePersistHandler2(CompanyContract $contract, LifecycleEventArgs $args)
    {
        $this->prePersistCalls[] = func_get_args();
    }
}
