<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

class CompanyFlexUltraContractListener
{
    public $prePersistCalls;

    /**
     * @PrePersist
     */
    #[ORM\PrePersist]
    public function prePersistHandler1(CompanyContract $contract, LifecycleEventArgs $args)
    {
        $this->prePersistCalls[] = func_get_args();
    }

    /**
     * @PrePersist
     */
    #[ORM\PrePersist]
    public function prePersistHandler2(CompanyContract $contract, LifecycleEventArgs $args)
    {
        $this->prePersistCalls[] = func_get_args();
    }
}
